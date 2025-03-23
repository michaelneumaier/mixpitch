<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SyncStripeInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:sync-invoices {--user=} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync invoices from Stripe to ensure local data is up to date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user');
        $syncAll = $this->option('all');
        
        if (!$userId && !$syncAll) {
            $this->error('You must specify either a user ID with --user or --all to sync all users.');
            return 1;
        }
        
        if ($userId) {
            // Sync for a specific user
            $user = User::find($userId);
            
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
            
            $this->info("Syncing invoices for user {$user->name} (ID: {$user->id})...");
            
            try {
                $result = $this->syncUserInvoices($user);
                
                if ($result['success']) {
                    $this->info("Successfully synced invoices for user {$user->name}");
                    $this->table(
                        ['Metric', 'Value'],
                        [
                            ['Total Charges', $result['results']['charges']],
                            ['Total Invoices', $result['results']['invoices']],
                            ['Orphaned Charges', $result['results']['orphaned_charges']],
                            ['New Invoices Created', $result['results']['new_invoices']],
                            ['Payment Intents', $result['results']['payment_intents']],
                        ]
                    );
                    
                    // List the actual invoices
                    $this->info("Listing invoices for user {$user->name}:");
                    $invoices = $user->invoices();
                    
                    if (count($invoices) > 0) {
                        $invoiceData = [];
                        foreach ($invoices as $invoice) {
                            $invoiceData[] = [
                                $invoice->id,
                                $invoice->number ?? 'N/A',
                                '$' . number_format($invoice->total() / 100, 2),
                                $invoice->date()->format('Y-m-d'),
                                $invoice->paid ? 'Paid' : 'Unpaid'
                            ];
                        }
                        
                        $this->table(
                            ['ID', 'Number', 'Amount', 'Date', 'Status'],
                            $invoiceData
                        );
                    } else {
                        $this->warn("No invoices found for this user.");
                    }
                } else {
                    $this->error("Failed to sync invoices: " . ($result['reason'] ?? 'Unknown error'));
                }
            } catch (\Exception $e) {
                $this->error("Error syncing invoices: " . $e->getMessage());
                return 1;
            }
        } else {
            // Sync for all users with a Stripe ID
            $users = User::whereNotNull('stripe_id')->get();
            $total = $users->count();
            $processed = 0;
            $successCount = 0;
            $totalInvoices = 0;
            $totalCharges = 0;
            $newInvoicesCreated = 0;
            
            if ($total === 0) {
                $this->info('No users with Stripe IDs found.');
                return 0;
            }
            
            $this->info("Syncing invoices for {$total} users...");
            $progress = $this->output->createProgressBar($total);
            $progress->start();
            
            foreach ($users as $user) {
                try {
                    $result = $this->syncUserInvoices($user);
                    $processed++;
                    
                    if ($result['success']) {
                        $successCount++;
                        $totalInvoices += $result['results']['invoices'];
                        $totalCharges += $result['results']['charges'];
                        $newInvoicesCreated += $result['results']['new_invoices'];
                    }
                } catch (\Exception $e) {
                    Log::error("Error syncing invoices for user {$user->id}: {$e->getMessage()}");
                }
                
                $progress->advance();
            }
            
            $progress->finish();
            $this->newLine(2);
            
            $this->info("Completed: Synced invoices for {$successCount} out of {$total} users.");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Users Processed', $processed],
                    ['Users Successfully Synced', $successCount],
                    ['Total Invoices Found', $totalInvoices],
                    ['Total Charges Found', $totalCharges],
                    ['New Invoices Created', $newInvoicesCreated],
                ]
            );
        }
        
        return 0;
    }
    
    /**
     * Sync invoices for a specific user
     *
     * @param User $user
     * @return array
     */
    protected function syncUserInvoices(User $user)
    {
        if (!$user->stripe_id) {
            return ['success' => false, 'reason' => 'no_stripe_id'];
        }
        
        try {
            $results = [
                'charges' => 0,
                'invoices' => 0,
                'orphaned_charges' => 0,
                'new_invoices' => 0
            ];
            
            // 1. Sync charges first
            $charges = $user->stripeClient()->charges->all([
                'customer' => $user->stripe_id,
                'limit' => 100,
            ]);
            
            $results['charges'] = count($charges->data);
            
            // Track orphaned charges (successful charges without invoices)
            foreach ($charges->data as $charge) {
                if (!empty($charge->invoice) || $charge->status !== 'succeeded') {
                    continue;
                }
                
                $results['orphaned_charges']++;
                
                try {
                    // Create invoice item
                    $user->stripeClient()->invoiceItems->create([
                        'customer' => $user->stripe_id,
                        'amount' => $charge->amount,
                        'currency' => $charge->currency,
                        'description' => $charge->description ?? 'Charge ' . $charge->id,
                    ]);
                    
                    // Create and finalize the invoice
                    $invoice = $user->stripeClient()->invoices->create([
                        'customer' => $user->stripe_id,
                        'auto_advance' => true,
                    ]);
                    
                    // Mark it as paid from this charge
                    $user->stripeClient()->invoices->pay($invoice->id, [
                        'paid_out_of_band' => true,
                    ]);
                    
                    $results['new_invoices']++;
                    
                    Log::info('Created invoice for orphaned charge', [
                        'user_id' => $user->id,
                        'charge_id' => $charge->id,
                        'invoice_id' => $invoice->id,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to create invoice for charge: ' . $e->getMessage(), [
                        'user_id' => $user->id,
                        'charge_id' => $charge->id,
                    ]);
                }
            }
            
            // 2. Get all invoices
            $stripeInvoices = $user->stripeClient()->invoices->all([
                'customer' => $user->stripe_id,
                'limit' => 100,
            ]);
            
            $results['invoices'] = count($stripeInvoices->data);
            
            // 3. Force a refresh of the invoice data in Laravel Cashier
            $invoices = $user->invoices(true); // Pass true to force refresh
            
            // 4. Check for payment intents that aren't associated with invoices
            $paymentIntents = $user->stripeClient()->paymentIntents->all([
                'customer' => $user->stripe_id,
                'limit' => 100,
            ]);
            
            $results['payment_intents'] = count($paymentIntents->data);
            
            // Log success
            Log::info('Successfully synced invoices for user', [
                'user_id' => $user->id,
                'results' => $results
            ]);
            
            // Return results
            return [
                'success' => true,
                'results' => $results
            ];
        } catch (\Exception $e) {
            Log::error("Failed to sync invoices from Stripe for user {$user->id}: " . $e->getMessage(), [
                'exception' => $e
            ]);
            
            throw $e;
        }
    }
} 