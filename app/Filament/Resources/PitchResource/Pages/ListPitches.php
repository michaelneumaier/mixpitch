<?php

namespace App\Filament\Resources\PitchResource\Pages;

use App\Filament\Resources\PitchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPitches extends ListRecords
{
    protected static string $resource = PitchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
