<?php

namespace App\Filament\Resources\EmailEventResource\Pages;

use App\Filament\Resources\EmailEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmailEvents extends ListRecords
{
    protected static string $resource = EmailEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for audit log
        ];
    }
}
