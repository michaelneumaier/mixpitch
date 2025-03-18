<?php

namespace App\Filament\Resources\MixResource\Pages;

use App\Filament\Resources\MixResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMix extends EditRecord
{
    protected static string $resource = MixResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
