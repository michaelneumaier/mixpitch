<?php

namespace App\Filament\Resources\FileUploadSettingResource\Pages;

use App\Filament\Resources\FileUploadSettingResource;
use App\Models\FileUploadSetting;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateFileUploadSetting extends CreateRecord
{
    protected static string $resource = FileUploadSettingResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validate the setting before creating
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
        
        // Check for duplicate key-context combination
        $existing = FileUploadSetting::where('key', $data['key'])
            ->where('context', $data['context'])
            ->first();
            
        if ($existing) {
            Notification::make()
                ->title('Setting Already Exists')
                ->body("A setting for '{$data['key']}' in context '{$data['context']}' already exists. Please edit the existing setting instead.")
                ->warning()
                ->send();
                
            $this->halt();
        }
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        // Clear cache after creating a new setting
        FileUploadSetting::clearSettingsCache($this->record->context);
        
        Notification::make()
            ->title('Setting Created')
            ->body('The upload setting has been created and cache has been cleared.')
            ->success()
            ->send();
    }
}