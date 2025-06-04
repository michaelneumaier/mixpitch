<?php

namespace App\Filament\Resources\SubscriptionLimitResource\Pages;

use App\Filament\Resources\SubscriptionLimitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionLimit extends EditRecord
{
    protected static string $resource = SubscriptionLimitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
