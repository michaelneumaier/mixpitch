<?php

namespace App\Filament\Resources\MarketplaceTemplateResource\Pages;

use App\Filament\Resources\MarketplaceTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarketplaceTemplate extends EditRecord
{
    protected static string $resource = MarketplaceTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
