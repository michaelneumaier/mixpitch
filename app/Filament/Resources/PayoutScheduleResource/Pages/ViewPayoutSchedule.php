<?php

namespace App\Filament\Resources\PayoutScheduleResource\Pages;

use App\Filament\Resources\PayoutScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPayoutSchedule extends ViewRecord
{
    protected static string $resource = PayoutScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
} 