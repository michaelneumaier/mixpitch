<?php

namespace App\Filament\Resources\PayoutScheduleResource\Pages;

use App\Filament\Resources\PayoutScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayoutSchedule extends EditRecord
{
    protected static string $resource = PayoutScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
} 