<?php

namespace App\Filament\Resources\EmailTestResource\Pages;

use App\Filament\Resources\EmailTestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEmailTest extends ViewRecord
{
    protected static string $resource = EmailTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('send')
                ->label('Send Test Email')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->action(function () {
                    $record = $this->record;
                    
                    // Get the resource class and invoke the send action
                    $resource = static::getResource();
                    $table = $resource::table(new \Filament\Tables\Table());
                    
                    // Find the send action
                    $sendAction = collect($table->getActions())
                        ->first(fn ($action) => $action->getName() === 'send');
                    
                    if ($sendAction) {
                        $sendAction->call([$record]);
                    }
                })
                ->visible(fn () => $this->record->status !== 'sent'),
        ];
    }
} 