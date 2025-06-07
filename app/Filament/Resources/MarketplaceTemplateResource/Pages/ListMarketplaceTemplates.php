<?php

namespace App\Filament\Resources\MarketplaceTemplateResource\Pages;

use App\Filament\Resources\MarketplaceTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMarketplaceTemplates extends ListRecords
{
    protected static string $resource = MarketplaceTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
