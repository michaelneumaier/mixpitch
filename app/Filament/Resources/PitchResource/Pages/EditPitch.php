<?php

namespace App\Filament\Resources\PitchResource\Pages;

use App\Filament\Resources\PitchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPitch extends EditRecord
{
    protected static string $resource = PitchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
