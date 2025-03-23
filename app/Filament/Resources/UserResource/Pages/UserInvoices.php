<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\Page;
use App\Models\User;
use Laravel\Cashier\Cashier;
use Carbon\Carbon;

class UserInvoices extends Page
{
    protected static string $resource = UserResource::class;

    protected static string $view = 'filament.resources.user-resource.pages.user-invoices';
    
    public User $record;
    
    public function getInvoices()
    {
        if (!$this->record->stripe_id) {
            return [];
        }
        
        try {
            $stripe = Cashier::stripe();
            $invoices = $stripe->invoices->all([
                'customer' => $this->record->stripe_id,
                'limit' => 100,
            ]);
            
            $formattedInvoices = [];
            
            foreach ($invoices->data as $invoice) {
                $formattedInvoices[] = [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'date' => Carbon::createFromTimestamp($invoice->created)->format('Y-m-d'),
                    'due_date' => $invoice->due_date ? Carbon::createFromTimestamp($invoice->due_date)->format('Y-m-d') : 'N/A',
                    'amount' => $invoice->amount_due / 100, // Convert from cents to dollars
                    'status' => $invoice->status,
                    'url' => $invoice->hosted_invoice_url,
                    'pdf' => $invoice->invoice_pdf,
                ];
            }
            
            return $formattedInvoices;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public function getStatusColor($status)
    {
        return match ($status) {
            'paid' => 'success',
            'open' => 'warning',
            'void' => 'danger',
            'draft' => 'gray',
            default => 'info',
        };
    }
    
    public function getViewData(): array
    {
        return [
            'invoices' => $this->getInvoices(),
        ];
    }
} 