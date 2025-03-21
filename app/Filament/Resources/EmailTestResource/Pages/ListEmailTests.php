<?php

namespace App\Filament\Resources\EmailTestResource\Pages;

use App\Filament\Resources\EmailTestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmailTests extends ListRecords
{
    protected static string $resource = EmailTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
