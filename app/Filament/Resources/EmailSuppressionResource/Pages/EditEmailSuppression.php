<?php

namespace App\Filament\Resources\EmailSuppressionResource\Pages;

use App\Filament\Resources\EmailSuppressionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailSuppression extends EditRecord
{
    protected static string $resource = EmailSuppressionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
