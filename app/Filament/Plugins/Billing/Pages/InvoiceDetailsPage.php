<?php

namespace App\Filament\Plugins\Billing\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class InvoiceDetailsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.billing.invoice-details';

    protected static ?string $title = 'Invoice Details';

    protected static ?string $slug = 'billing/invoice';

    // Hide from navigation menu since this is accessed via link
    protected static bool $shouldRegisterNavigation = false;

    public string $invoice;

    public $invoiceData = null;

    public $stripeInvoice = null;

    public $customer = null;

    public $paymentMethod = null;

    public function mount(string $invoice): void
    {
        $this->invoice = $invoice;
        $this->loadInvoice();
    }

    public function loadInvoice(): void
    {
        $user = Auth::user();

        try {
            // Retrieve the invoice from Cashier
            $this->invoiceData = $user->findInvoice($this->invoice);

            if (! $this->invoiceData) {
                $this->redirect(BillingDashboard::getUrl());

                return;
            }

            // Fetch the raw invoice data from Stripe for accurate totals
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $this->stripeInvoice = $stripe->invoices->retrieve($this->invoice, [
                'expand' => ['lines.data', 'payment_intent', 'charge', 'customer'],
            ]);

            // Get the customer information
            if ($this->stripeInvoice->customer) {
                $this->customer = $stripe->customers->retrieve($this->stripeInvoice->customer);
            }

            // If invoice is paid, get payment method information
            if ($this->stripeInvoice->paid && $this->stripeInvoice->payment_intent) {
                $paymentIntentId = is_string($this->stripeInvoice->payment_intent)
                    ? $this->stripeInvoice->payment_intent
                    : $this->stripeInvoice->payment_intent->id;

                $paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId);

                if (isset($paymentIntent->payment_method)) {
                    $this->paymentMethod = $stripe->paymentMethods->retrieve($paymentIntent->payment_method);
                }
            }
        } catch (\Exception $e) {
            $this->redirect(BillingDashboard::getUrl());
        }
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Billing Dashboard' => BillingDashboard::getUrl(),
            'Invoice Details' => '#',
        ];
    }
}
