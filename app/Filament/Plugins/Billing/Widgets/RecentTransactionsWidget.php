<?php

namespace App\Filament\Plugins\Billing\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Laravel\Cashier\Cashier;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class RecentTransactionsWidget extends Widget
{
    protected static ?int $sort = 3;
    
    protected static string $view = 'filament.widgets.billing.recent-transactions-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    public function getTransactions(): array
    {
        return Cache::remember('recent_transactions', 60, function () {
            $stripe = Cashier::stripe();
            
            // Fetch charges
            $charges = $stripe->charges->all([
                'limit' => 100,
            ]);
            
            $transactions = [];
            
            foreach ($charges->data as $charge) {
                // Try to get user information
                $customerName = 'Unknown';
                $customerEmail = '';
                
                if (isset($charge->customer)) {
                    $user = User::where('stripe_id', $charge->customer)->first();
                    
                    if ($user) {
                        $customerName = $user->name;
                        $customerEmail = $user->email;
                    } else {
                        try {
                            $customer = $stripe->customers->retrieve($charge->customer);
                            $customerName = $customer->name ?? 'Unknown';
                            $customerEmail = $customer->email ?? '';
                        } catch (\Exception $e) {
                            // Customer not found or error
                        }
                    }
                }
                
                // Format payment method
                $paymentMethod = 'Unknown';
                if (isset($charge->payment_method_details)) {
                    if (isset($charge->payment_method_details->card)) {
                        $card = $charge->payment_method_details->card;
                        $paymentMethod = ucfirst($card->brand) . ' •••• ' . $card->last4;
                    } elseif (isset($charge->payment_method_details->type)) {
                        $paymentMethod = ucfirst($charge->payment_method_details->type);
                    }
                }
                
                $transactions[] = [
                    'id' => $charge->id,
                    'date' => Carbon::createFromTimestamp($charge->created)->format('Y-m-d H:i:s'),
                    'customer' => $customerName . ($customerEmail ? " ($customerEmail)" : ''),
                    'description' => $charge->description ?? 'No description',
                    'amount' => $charge->amount / 100, // Convert from cents to dollars
                    'status' => $charge->status,
                    'payment_method' => $paymentMethod,
                    'invoice_id' => $charge->invoice ?? 'N/A',
                    'receipt_url' => $charge->receipt_url ?? null,
                ];
            }
            
            // Sort by date descending
            usort($transactions, function ($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            return $transactions;
        });
    }
    
    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'succeeded' => 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900/20',
            'pending' => 'text-yellow-600 bg-yellow-100 dark:text-yellow-400 dark:bg-yellow-900/20',
            'failed' => 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-900/20',
            default => 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-900/20',
        };
    }
} 