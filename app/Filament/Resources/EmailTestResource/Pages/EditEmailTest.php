<?php

namespace App\Filament\Resources\EmailTestResource\Pages;

use App\Filament\Resources\EmailTestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailTest extends EditRecord
{
    protected static string $resource = EmailTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
