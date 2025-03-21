<?php

namespace App\Filament\Resources\EmailEventResource\Pages;

use App\Filament\Resources\EmailEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailEvent extends EditRecord
{
    protected static string $resource = EmailEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
