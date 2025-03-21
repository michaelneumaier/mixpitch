<?php

namespace App\Filament\Resources\EmailAuditResource\Pages;

use App\Filament\Resources\EmailAuditResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailAudit extends EditRecord
{
    protected static string $resource = EmailAuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
