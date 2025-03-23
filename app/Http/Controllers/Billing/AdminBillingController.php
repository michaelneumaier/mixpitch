<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Filament\Notifications\Notification;

class AdminBillingController extends Controller
{
    /**
     * Create a Stripe customer for a user
     */
    public function createStripeCustomer(Request $request, User $record)
    {
        try {
            // Create customer if one doesn't exist yet
            if (!$record->stripe_id) {
                $record->createAsStripeCustomer([
                    'name' => $record->name,
                    'email' => $record->email,
                    'metadata' => [
                        'user_id' => $record->id,
                        'created_by' => 'admin',
                        'admin_user' => auth()->user()->name,
                    ],
                ]);
                
                Notification::make()
                    ->title('Stripe customer created')
                    ->body("Successfully created Stripe customer for {$record->name}.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Stripe customer already exists')
                    ->body("The user already has a Stripe customer ID: {$record->stripe_id}")
                    ->info()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to create Stripe customer')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
        
        // Redirect back to the previous page
        return back();
    }
} 