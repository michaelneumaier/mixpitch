<?php

namespace App\Filament\Plugins\Billing\Widgets;

use App\Filament\Plugins\Billing\Pages\InvoiceDetailsPage;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class InvoiceListWidget extends Widget
{
    protected static string $view = 'filament.widgets.billing.invoice-list-widget';

    protected int|string|array $columnSpan = 'full';

    public function getInvoices()
    {
        $user = Auth::user();

        if (! $user->stripe_id) {
            return [];
        }

        try {
            // Fetch invoice data from Stripe directly for accurate totals
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $invoices = $stripe->invoices->all([
                'customer' => $user->stripe_id,
                'limit' => 10,
            ]);

            // Convert Stripe objects to plain arrays with formatted values
            return collect($invoices->data)->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number ?? substr($invoice->id, 0, 8),
                    'date' => Carbon::createFromTimestamp($invoice->created)->format('M d, Y'),
                    'amount' => '$'.number_format($invoice->total / 100, 2),
                    'status' => $invoice->status,
                    'status_color' => $this->getStatusColor($invoice->status),
                    'view_url' => InvoiceDetailsPage::getUrl(['invoice' => $invoice->id]),
                    'download_url' => "/billing/download-invoice/{$invoice->id}",
                ];
            })->toArray();
        } catch (\Exception $e) {
            // Return an empty array if there's an error
            return [];
        }
    }

    protected function getStatusColor($status)
    {
        return match ($status) {
            'paid' => 'success',
            'open' => 'warning',
            'draft' => 'gray',
            default => 'danger',
        };
    }

    public function getHeading()
    {
        return __('Recent Invoices');
    }
}
