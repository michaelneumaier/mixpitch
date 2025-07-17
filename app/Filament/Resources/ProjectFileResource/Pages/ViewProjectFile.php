<?php

namespace App\Filament\Resources\ProjectFileResource\Pages;

use App\Filament\Resources\ProjectFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProjectFile extends ViewRecord
{
    protected static string $resource = ProjectFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('download')
                ->icon('heroicon-m-arrow-down-tray')
                ->url(fn () => $this->record->getSignedUrlAttribute())
                ->openUrlInNewTab()
                ->label('Download File'),
        ];
    }
}
