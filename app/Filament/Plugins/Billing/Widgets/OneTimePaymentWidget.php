<?php

namespace App\Filament\Plugins\Billing\Widgets;

use Filament\Widgets\Widget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class OneTimePaymentWidget extends Widget implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $view = 'filament.widgets.billing.one-time-payment-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    public ?array $paymentMethod = null;
    public bool $hasPaymentMethod = false;
    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $this->hasPaymentMethod = $user->hasDefaultPaymentMethod();
        
        if ($this->hasPaymentMethod) {
            $method = $user->defaultPaymentMethod();
            $this->paymentMethod = [
                'id' => $method->id,
                'brand' => $method->card->brand,
                'last4' => $method->card->last4,
                'exp_month' => $method->card->exp_month,
                'exp_year' => $method->card->exp_year,
            ];
        }
        
        $this->form->fill();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        TextInput::make('amount')
                            ->label('Amount (USD)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->step(0.01)
                            ->helperText('Minimum amount is $1.00')
                            ->columnSpan(1),
                        TextInput::make('description')
                            ->label('Description')
                            ->placeholder('What is this payment for?')
                            ->columnSpan(1),
                        Hidden::make('payment_method')
                            ->default(fn () => $this->hasPaymentMethod ? $this->paymentMethod['id'] : null),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }
    
    #[On('payment-method-updated')]
    public function refreshPaymentMethod(): void
    {
        $user = Auth::user();
        
        // Refresh payment method data
        $this->hasPaymentMethod = $user->hasDefaultPaymentMethod();
        
        if ($this->hasPaymentMethod) {
            $method = $user->defaultPaymentMethod();
            $this->paymentMethod = [
                'id' => $method->id,
                'brand' => $method->card->brand,
                'last4' => $method->card->last4,
                'exp_month' => $method->card->exp_month,
                'exp_year' => $method->card->exp_year,
            ];
            
            $this->data['payment_method'] = $method->id;
        } else {
            $this->paymentMethod = null;
            $this->data['payment_method'] = null;
        }
    }
    
    public function processPayment(): void
    {
        $data = $this->form->getState();
        
        // Validate that we have a payment method
        if (empty($data['payment_method'])) {
            Notification::make()
                ->title('No payment method available')
                ->body('Please add a payment method before making a payment.')
                ->danger()
                ->send();
                
            return;
        }
        
        $user = Auth::user();
        $amount = floatval($data['amount']) * 100; // Convert to cents
        $description = $data['description'] ?? 'One-time payment';
        $paymentMethod = $data['payment_method'];
        
        try {
            // Create Stripe customer if one doesn't exist yet
            if (!$user->stripe_id) {
                $user->createAsStripeCustomer();
            }
            
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            
            // First create the invoice
            $invoice = $stripe->invoices->create([
                'customer' => $user->stripe_id,
                'auto_advance' => false, // Don't auto-finalize yet
                'description' => $description,
                'collection_method' => 'charge_automatically',
                'metadata' => [
                    'source' => 'one_time_payment',
                    'user_id' => $user->id,
                    'amount' => $amount
                ]
            ]);
            
            // Then create an invoice item attached to the invoice
            $invoiceItem = $stripe->invoiceItems->create([
                'customer' => $user->stripe_id,
                'amount' => (int)$amount, // Ensure amount is an integer
                'currency' => 'usd',
                'description' => $description,
                'invoice' => $invoice->id, // Attach to the invoice we just created
            ]);
            
            // Finalize the invoice
            $invoice = $stripe->invoices->finalizeInvoice($invoice->id);
            
            // Pay the invoice using the specified payment method
            $payResult = $stripe->invoices->pay($invoice->id, [
                'payment_method' => $paymentMethod,
                'off_session' => true,
            ]);
            
            // Reset the form
            $this->form->fill([
                'amount' => null,
                'description' => null,
                'payment_method' => $this->paymentMethod['id'],
            ]);
            
            // Notify user of success
            Notification::make()
                ->title('Payment processed')
                ->body('Your payment of $' . number_format($amount / 100, 2) . ' was successful.')
                ->success()
                ->send();
                
            // Emit event to refresh invoice list
            $this->dispatch('invoices-updated');
            
        } catch (\Stripe\Exception\CardException $e) {
            Notification::make()
                ->title('Card error')
                ->body($e->getMessage())
                ->danger()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error processing payment')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
} 