<?php

namespace App\Filament\Plugins\Billing\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Laravel\Cashier\Cashier;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TopCustomersWidget extends Widget
{
    protected static ?int $sort = 4;
    
    protected static string $view = 'filament.widgets.billing.top-customers-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    public function getTopCustomers(): array
    {
        return Cache::remember('top_customers', 60 * 5, function () {
            $stripe = Cashier::stripe();
            $customers = [];
            
            // Get all users with Stripe IDs
            $users = User::whereNotNull('stripe_id')->get();
            
            foreach ($users as $user) {
                try {
                    // Get all charges for this customer
                    $charges = $stripe->charges->all([
                        'customer' => $user->stripe_id,
                        'status' => 'succeeded',
                        'limit' => 100,
                    ]);
                    
                    $totalSpent = 0;
                    $lastPaymentDate = null;
                    
                    foreach ($charges->data as $charge) {
                        $totalSpent += $charge->amount;
                        
                        if ($lastPaymentDate === null || $charge->created > $lastPaymentDate) {
                            $lastPaymentDate = $charge->created;
                        }
                    }
                    
                    if ($totalSpent > 0) {
                        $customers[] = [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'avatar' => $user->profile_photo_url ?? null,
                            'total_spent' => $totalSpent / 100, // Convert from cents to dollars
                            'last_payment' => $lastPaymentDate ? Carbon::createFromTimestamp($lastPaymentDate)->diffForHumans() : 'Never',
                            'customer_since' => $user->created_at->diffForHumans(),
                        ];
                    }
                } catch (\Exception $e) {
                    // Skip this user if there's an error
                    continue;
                }
            }
            
            // Sort by total spent (descending)
            usort($customers, function ($a, $b) {
                return $b['total_spent'] <=> $a['total_spent'];
            });
            
            // Return top 10
            return array_slice($customers, 0, 10);
        });
    }
    
    public function getTotalCustomerCount(): int
    {
        return User::whereNotNull('stripe_id')->count();
    }
} 