<?php

namespace App\Filament\Resources\FileUploadSettingResource\Pages;

use App\Filament\Resources\FileUploadSettingResource;
use App\Models\FileUploadSetting;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditFileUploadSetting extends EditRecord
{
    protected static string $resource = FileUploadSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    FileUploadSetting::clearSettingsCache($this->record->context);
                    
                    Notification::make()
                        ->title('Setting Deleted')
                        ->body('The setting has been deleted and cache has been cleared.')
                        ->success()
                        ->send();
                }),
                
            Actions\Action::make('reset_to_default')
                ->label('Reset to Default')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Reset Setting to Default')
                ->modalDescription('This will reset this setting to its default value. This action cannot be undone.')
                ->action(function () {
                    $key = $this->record->key;
                    $defaultValue = FileUploadSetting::DEFAULT_VALUES[$key] ?? null;
                    
                    if ($defaultValue !== null) {
                        $this->record->update(['value' => $defaultValue]);
                        FileUploadSetting::clearSettingsCache($this->record->context);
                        
                        Notification::make()
                            ->title('Setting Reset')
                            ->body('The setting has been reset to its default value.')
                            ->success()
                            ->send();
                            
                        $this->fillForm();
                    } else {
                        Notification::make()
                            ->title('No Default Value')
                            ->body('This setting does not have a default value.')
                            ->warning()
                            ->send();
                    }
                }),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validate the setting before saving
        try {
            FileUploadSetting::validateSetting($data['key'], $data['value']);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Validation Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
            
            $this->halt();
        }
        
        // Check for duplicate key-context combination (excluding current record)
        $existing = FileUploadSetting::where('key', $data['key'])
            ->where('context', $data['context'])
            ->where('id', '!=', $this->record->id)
            ->first();
            
        if ($existing) {
            Notification::make()
                ->title('Setting Already Exists')
                ->body("A setting for '{$data['key']}' in context '{$data['context']}' already exists.")
                ->warning()
                ->send();
                
            $this->halt();
        }
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        // Clear cache after updating a setting
        FileUploadSetting::clearSettingsCache($this->record->context);
        
        Notification::make()
            ->title('Setting Updated')
            ->body('The upload setting has been updated and cache has been cleared.')
            ->success()
            ->send();
    }
}