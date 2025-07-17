<?php

namespace App\Filament\Resources\StripeTransactionResource\Pages;

use App\Filament\Resources\StripeTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStripeTransaction extends ViewRecord
{
    protected static string $resource = StripeTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
