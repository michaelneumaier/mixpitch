<?php

namespace App\Filament\Plugins\Billing\Widgets;

use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class PaymentMethodWidget extends Widget implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $view = 'filament.widgets.billing.payment-method-widget';

    protected int|string|array $columnSpan = 'full';

    public ?array $paymentMethod = null;

    public bool $hasPaymentMethod = false;

    public string $setupIntent = '';

    public bool $showPaymentMethodForm = false;

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

        $intent = $user->createSetupIntent();
        $this->setupIntent = $intent->client_secret;
    }

    public function togglePaymentMethodForm(): void
    {
        $this->showPaymentMethodForm = ! $this->showPaymentMethodForm;
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
        } else {
            $this->paymentMethod = null;
        }

        $this->showPaymentMethodForm = false;

        Notification::make()
            ->title('Payment method updated')
            ->success()
            ->send();
    }

    public function removePaymentMethod(): void
    {
        $user = Auth::user();

        try {
            $paymentMethod = $user->defaultPaymentMethod();

            if ($paymentMethod) {
                $paymentMethod->delete();
                $this->paymentMethod = null;
                $this->hasPaymentMethod = false;

                Notification::make()
                    ->title('Payment method removed')
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error removing payment method')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function updatePaymentMethod(string $paymentMethodId): void
    {
        $user = Auth::user();

        try {
            // Update the default payment method
            $user->updateDefaultPaymentMethod($paymentMethodId);

            // Refresh payment method data
            $this->refreshPaymentMethod();

            // Dispatch event to update other components
            $this->dispatch('payment-method-updated');

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error updating payment method')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
