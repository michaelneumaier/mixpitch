<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Laravel\Cashier\Cashier;

class UserPayment extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = UserResource::class;

    protected static string $view = 'filament.resources.user-resource.pages.user-payment';

    public User $record;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        TextInput::make('amount')
                            ->label('Amount ($)')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->helperText('Enter the payment amount in USD.'),

                        TextInput::make('description')
                            ->label('Description')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Service fee, product purchase, etc.'),

                        Textarea::make('notes')
                            ->label('Internal Notes')
                            ->placeholder('Additional information about this payment (not visible to customer)')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function processPayment()
    {
        if (! $this->record->stripe_id) {
            Notification::make()
                ->title('User is not a Stripe customer')
                ->body('Please create a Stripe customer record for this user first.')
                ->danger()
                ->send();

            return;
        }

        $this->form->validate();

        $amount = (float) $this->data['amount'];
        $description = $this->data['description'];
        $notes = $this->data['notes'] ?? '';

        try {
            $stripe = Cashier::stripe();

            // Check if the user has a default payment method
            $customer = $stripe->customers->retrieve($this->record->stripe_id, [
                'expand' => ['default_payment_method'],
            ]);

            if (! $customer->default_payment_method) {
                Notification::make()
                    ->title('No payment method available')
                    ->body('This user doesn\'t have a default payment method set up.')
                    ->danger()
                    ->send();

                return;
            }

            // Create invoice item
            $invoiceItem = $stripe->invoiceItems->create([
                'customer' => $this->record->stripe_id,
                'amount' => (int) ($amount * 100), // Convert to cents
                'currency' => 'usd',
                'description' => $description,
                'metadata' => [
                    'internal_notes' => $notes,
                    'processed_by' => auth()->user()->name,
                    'admin_processed' => 'true',
                ],
            ]);

            // Create and finalize the invoice
            $invoice = $stripe->invoices->create([
                'customer' => $this->record->stripe_id,
                'auto_advance' => true, // Auto-finalize and pay
                'collection_method' => 'charge_automatically',
                'metadata' => [
                    'internal_notes' => $notes,
                    'processed_by' => auth()->user()->name,
                    'admin_processed' => 'true',
                ],
                'description' => $description,
            ]);

            // Manually attempt to pay the invoice now
            $result = $stripe->invoices->pay($invoice->id);

            if ($result->status === 'paid') {
                Notification::make()
                    ->title('Payment successful')
                    ->body("Successfully charged {$amount} USD to {$this->record->name}.")
                    ->success()
                    ->send();

                // Reset the form
                $this->form->fill();
            } else {
                Notification::make()
                    ->title('Payment status: '.$result->status)
                    ->body("The invoice has been created but the payment status is: {$result->status}.")
                    ->info()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Payment failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getPaymentMethods()
    {
        if (! $this->record->stripe_id) {
            return [];
        }

        try {
            $stripe = Cashier::stripe();
            $methods = $stripe->paymentMethods->all([
                'customer' => $this->record->stripe_id,
                'type' => 'card',
            ]);

            $formattedMethods = [];

            foreach ($methods->data as $method) {
                $formattedMethods[] = [
                    'id' => $method->id,
                    'brand' => $method->card->brand,
                    'last4' => $method->card->last4,
                    'exp' => $method->card->exp_month.'/'.$method->card->exp_year,
                    'default' => $method->id === $this->record->pm_type,
                ];
            }

            return $formattedMethods;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getViewData(): array
    {
        return [
            'paymentMethods' => $this->getPaymentMethods(),
        ];
    }
}
