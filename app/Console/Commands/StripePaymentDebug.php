<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class StripePaymentDebug extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:debug-payment {--payment=} {--user=} {--create-missing-invoices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug Stripe payment data and optionally create missing invoices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $paymentId = $this->option('payment');
        $userId = $this->option('user');
        $createMissing = $this->option('create-missing-invoices');
        
        if (!$paymentId && !$userId) {
            $this->error('You must specify either a payment ID with --payment or a user ID with --user.');
            return 1;
        }
        
        if ($paymentId) {
            $this->debugPaymentIntent($paymentId, $createMissing);
        } else {
            $this->debugUserPayments($userId, $createMissing);
        }
        
        return 0;
    }
    
    /**
     * Debug a specific payment intent
     *
     * @param string $paymentId
     * @param bool $createMissing
     * @return void
     */
    protected function debugPaymentIntent($paymentId, $createMissing = false)
    {
        $this->info("Debugging payment intent: {$paymentId}");
        
        try {
            // Get a user with Stripe access
            $user = User::whereNotNull('stripe_id')->first();
            
            if (!$user) {
                $this->error("No users with Stripe ID found. Cannot make API calls.");
                return;
            }
            
            // Get payment intent details
            $paymentIntent = $user->stripeClient()->paymentIntents->retrieve($paymentId);
            
            $this->info("Payment Intent Details:");
            $this->table(
                ['Attribute', 'Value'],
                [
                    ['ID', $paymentIntent->id],
                    ['Amount', '$' . number_format($paymentIntent->amount / 100, 2)],
                    ['Currency', strtoupper($paymentIntent->currency)],
                    ['Status', $paymentIntent->status],
                    ['Customer', $paymentIntent->customer ?? 'N/A'],
                    ['Created', date('Y-m-d H:i:s', $paymentIntent->created)],
                    ['Description', $paymentIntent->description ?? 'N/A'],
                    ['Invoice', $paymentIntent->invoice ?? 'No invoice associated'],
                    ['Payment Method', $paymentIntent->payment_method ?? 'N/A'],
                ]
            );
            
            // Check if there's a customer associated
            if ($paymentIntent->customer) {
                $customerUser = User::where('stripe_id', $paymentIntent->customer)->first();
                
                if ($customerUser) {
                    $this->info("Payment belongs to user: {$customerUser->name} (ID: {$customerUser->id})");
                } else {
                    $this->warn("Customer exists in Stripe but no matching user found in the database.");
                }
                
                // Get more information about the customer
                $customer = $user->stripeClient()->customers->retrieve($paymentIntent->customer);
                $this->info("Customer Details:");
                $this->table(
                    ['Attribute', 'Value'],
                    [
                        ['ID', $customer->id],
                        ['Email', $customer->email ?? 'N/A'],
                        ['Name', $customer->name ?? 'N/A'],
                        ['Created', date('Y-m-d H:i:s', $customer->created)],
                    ]
                );
            }
            
            // Check for related charges
            $charges = $user->stripeClient()->charges->all([
                'payment_intent' => $paymentId,
            ]);
            
            if (count($charges->data) > 0) {
                $this->info("Related Charges:");
                $chargeData = [];
                
                foreach ($charges->data as $charge) {
                    $chargeData[] = [
                        $charge->id,
                        '$' . number_format($charge->amount / 100, 2),
                        $charge->status,
                        $charge->invoice ?? 'No invoice',
                        date('Y-m-d H:i:s', $charge->created),
                    ];
                }
                
                $this->table(
                    ['ID', 'Amount', 'Status', 'Invoice', 'Created'],
                    $chargeData
                );
                
                // Check if we need to create missing invoices
                $missingInvoices = 0;
                
                foreach ($charges->data as $charge) {
                    if (empty($charge->invoice) && $charge->status === 'succeeded' && $charge->paid && $createMissing) {
                        $this->info("Creating invoice for charge: {$charge->id}");
                        
                        try {
                            // Get the customer
                            $stripeCustomer = $charge->customer;
                            $dbUser = User::where('stripe_id', $stripeCustomer)->first();
                            
                            if (!$dbUser) {
                                $this->warn("Cannot create invoice: No user found for customer {$stripeCustomer}");
                                continue;
                            }
                            
                            // Create an invoice item
                            $dbUser->stripeClient()->invoiceItems->create([
                                'customer' => $stripeCustomer,
                                'amount' => $charge->amount,
                                'currency' => $charge->currency,
                                'description' => $charge->description ?? "Payment {$charge->id}",
                            ]);
                            
                            // Create and finalize the invoice
                            $invoice = $dbUser->stripeClient()->invoices->create([
                                'customer' => $stripeCustomer,
                                'auto_advance' => true,
                            ]);
                            
                            // Mark it as paid
                            $dbUser->stripeClient()->invoices->pay($invoice->id, [
                                'paid_out_of_band' => true,
                            ]);
                            
                            $this->info("Created invoice {$invoice->id} for charge {$charge->id}");
                            $missingInvoices++;
                        } catch (\Exception $e) {
                            $this->error("Failed to create invoice: " . $e->getMessage());
                        }
                    }
                }
                
                if ($createMissing) {
                    $this->info("Created {$missingInvoices} missing invoices.");
                } else if ($missingInvoices > 0) {
                    $this->warn("Found {$missingInvoices} charges without invoices. Use --create-missing-invoices to create them.");
                }
            } else {
                $this->warn("No charges found for this payment intent.");
            }
            
        } catch (\Exception $e) {
            $this->error("Error retrieving payment data: " . $e->getMessage());
        }
    }
    
    /**
     * Debug all payments for a specific user
     *
     * @param int $userId
     * @param bool $createMissing
     * @return void
     */
    protected function debugUserPayments($userId, $createMissing = false)
    {
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return;
        }
        
        $this->info("Debugging payments for user: {$user->name}");
        
        if (!$user->stripe_id) {
            $this->error("This user does not have a Stripe customer ID.");
            return;
        }
        
        try {
            // Get payment intents
            $paymentIntents = $user->stripeClient()->paymentIntents->all([
                'customer' => $user->stripe_id,
                'limit' => 100,
            ]);
            
            if (count($paymentIntents->data) > 0) {
                $this->info("Payment Intents:");
                $intentData = [];
                
                foreach ($paymentIntents->data as $intent) {
                    $intentData[] = [
                        $intent->id,
                        '$' . number_format($intent->amount / 100, 2),
                        $intent->status,
                        $intent->invoice ?? 'No invoice',
                        date('Y-m-d H:i:s', $intent->created),
                    ];
                }
                
                $this->table(
                    ['ID', 'Amount', 'Status', 'Invoice', 'Created'],
                    $intentData
                );
            } else {
                $this->warn("No payment intents found for this user.");
            }
            
            // Get charges
            $charges = $user->stripeClient()->charges->all([
                'customer' => $user->stripe_id,
                'limit' => 100,
            ]);
            
            if (count($charges->data) > 0) {
                $this->info("Charges:");
                $chargeData = [];
                
                foreach ($charges->data as $charge) {
                    $chargeData[] = [
                        $charge->id,
                        '$' . number_format($charge->amount / 100, 2),
                        $charge->status,
                        $charge->invoice ?? 'No invoice',
                        date('Y-m-d H:i:s', $charge->created),
                    ];
                }
                
                $this->table(
                    ['ID', 'Amount', 'Status', 'Invoice', 'Created'],
                    $chargeData
                );
            } else {
                $this->warn("No charges found for this user.");
            }
            
            // Get invoices
            $invoices = $user->invoices();
            
            if (count($invoices) > 0) {
                $this->info("Invoices:");
                $invoiceData = [];
                
                foreach ($invoices as $invoice) {
                    $invoiceData[] = [
                        $invoice->id,
                        $invoice->number ?? 'N/A',
                        '$' . number_format($invoice->total() / 100, 2),
                        $invoice->date()->format('Y-m-d'),
                        $invoice->paid ? 'Paid' : 'Unpaid',
                    ];
                }
                
                $this->table(
                    ['ID', 'Number', 'Amount', 'Date', 'Status'],
                    $invoiceData
                );
            } else {
                $this->warn("No invoices found for this user.");
            }
            
            // Create missing invoices if needed
            if ($createMissing) {
                $missingInvoices = 0;
                
                foreach ($charges->data as $charge) {
                    if (empty($charge->invoice) && $charge->status === 'succeeded' && $charge->paid) {
                        $this->info("Creating invoice for charge: {$charge->id}");
                        
                        try {
                            // Create an invoice item
                            $user->stripeClient()->invoiceItems->create([
                                'customer' => $user->stripe_id,
                                'amount' => $charge->amount,
                                'currency' => $charge->currency,
                                'description' => $charge->description ?? "Payment {$charge->id}",
                            ]);
                            
                            // Create and finalize the invoice
                            $invoice = $user->stripeClient()->invoices->create([
                                'customer' => $user->stripe_id,
                                'auto_advance' => true,
                            ]);
                            
                            // Mark it as paid
                            $user->stripeClient()->invoices->pay($invoice->id, [
                                'paid_out_of_band' => true,
                            ]);
                            
                            $this->info("Created invoice {$invoice->id} for charge {$charge->id}");
                            $missingInvoices++;
                        } catch (\Exception $e) {
                            $this->error("Failed to create invoice: " . $e->getMessage());
                        }
                    }
                }
                
                if ($missingInvoices > 0) {
                    $this->info("Created {$missingInvoices} missing invoices.");
                    
                    // Show updated invoices
                    $updatedInvoices = $user->invoices(true); // Force refresh
                    
                    if (count($updatedInvoices) > 0) {
                        $this->info("Updated Invoices List:");
                        $invoiceData = [];
                        
                        foreach ($updatedInvoices as $invoice) {
                            $invoiceData[] = [
                                $invoice->id,
                                $invoice->number ?? 'N/A',
                                '$' . number_format($invoice->total() / 100, 2),
                                $invoice->date()->format('Y-m-d'),
                                $invoice->paid ? 'Paid' : 'Unpaid',
                            ];
                        }
                        
                        $this->table(
                            ['ID', 'Number', 'Amount', 'Date', 'Status'],
                            $invoiceData
                        );
                    }
                }
            }
            
        } catch (\Exception $e) {
            $this->error("Error retrieving payment data: " . $e->getMessage());
        }
    }
} 