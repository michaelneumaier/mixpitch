<?php

namespace App\Filament\Resources\StripeTransactionResource\Pages;

use App\Filament\Resources\StripeTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStripeTransaction extends EditRecord
{
    protected static string $resource = StripeTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
