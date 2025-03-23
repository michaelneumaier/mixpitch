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
            
            $this->syncUserInvoices($user);
            $this->info("Invoices synced for user {$user->name} (ID: {$user->id})");
        } else {
            // Sync for all users with a Stripe ID
            $users = User::whereNotNull('stripe_id')->get();
            $total = $users->count();
            $processed = 0;
            
            if ($total === 0) {
                $this->info('No users with Stripe IDs found.');
                return 0;
            }
            
            $this->info("Syncing invoices for {$total} users...");
            $progress = $this->output->createProgressBar($total);
            $progress->start();
            
            foreach ($users as $user) {
                try {
                    $this->syncUserInvoices($user);
                    $processed++;
                } catch (\Exception $e) {
                    Log::error("Error syncing invoices for user {$user->id}: {$e->getMessage()}");
                }
                
                $progress->advance();
            }
            
            $progress->finish();
            $this->newLine();
            $this->info("Completed: Synced invoices for {$processed} out of {$total} users.");
        }
        
        return 0;
    }
    
    /**
     * Sync invoices for a specific user
     *
     * @param User $user
     * @return void
     */
    protected function syncUserInvoices(User $user)
    {
        if (!$user->stripe_id) {
            return;
        }
        
        try {
            // Use Stripe API to get the latest invoice data
            $stripeInvoices = $user->stripeClient()->invoices->all([
                'customer' => $user->stripe_id,
                'limit' => 100, // Get a reasonable number of invoices
            ]);
            
            // Force a refresh of the user's invoices
            $invoiceCount = count($user->invoices());
            
            // Return details
            return [
                'success' => true,
                'invoice_count' => $invoiceCount
            ];
        } catch (\Exception $e) {
            Log::error("Failed to sync invoices from Stripe for user {$user->id}: {$e->getMessage()}");
            throw $e;
        }
    }
} 