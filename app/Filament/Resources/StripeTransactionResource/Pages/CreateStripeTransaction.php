<?php

namespace App\Filament\Resources\StripeTransactionResource\Pages;

use App\Filament\Resources\StripeTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStripeTransaction extends CreateRecord
{
    protected static string $resource = StripeTransactionResource::class;
} 