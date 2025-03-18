<?php

namespace App\Filament\Resources\ProjectFileResource\Pages;

use App\Filament\Resources\ProjectFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectFiles extends ListRecords
{
    protected static string $resource = ProjectFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
