<?php

namespace App\Filament\Resources\FileUploadSettingResource\Pages;

use App\Filament\Resources\FileUploadSettingResource;
use App\Models\FileUploadSetting;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListFileUploadSettings extends ListRecords
{
    protected static string $resource = FileUploadSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            
            Actions\Action::make('view_current_settings')
                ->label('View Current Settings')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->modalHeading('Current Upload Settings')
                ->modalContent(function () {
                    $contexts = FileUploadSetting::getValidContexts();
                    $content = '<div class="space-y-6">';
                    
                    foreach ($contexts as $context) {
                        $settings = FileUploadSetting::getSettings($context);
                        $contextLabel = match ($context) {
                            FileUploadSetting::CONTEXT_GLOBAL => 'Global (Default)',
                            FileUploadSetting::CONTEXT_PROJECTS => 'Projects',
                            FileUploadSetting::CONTEXT_PITCHES => 'Pitches',
                            FileUploadSetting::CONTEXT_CLIENT_PORTALS => 'Client Portals',
                            default => $context,
                        };
                        
                        $content .= "<div class='border rounded-lg p-4'>";
                        $content .= "<h3 class='font-semibold text-lg mb-3'>{$contextLabel}</h3>";
                        $content .= "<div class='grid grid-cols-2 gap-4'>";
                        
                        foreach ($settings as $key => $value) {
                            $label = match ($key) {
                                FileUploadSetting::MAX_FILE_SIZE_MB => 'Max File Size (MB)',
                                FileUploadSetting::CHUNK_SIZE_MB => 'Chunk Size (MB)',
                                FileUploadSetting::MAX_CONCURRENT_UPLOADS => 'Max Concurrent Uploads',
                                FileUploadSetting::MAX_RETRY_ATTEMPTS => 'Max Retry Attempts',
                                FileUploadSetting::ENABLE_CHUNKING => 'Enable Chunking',
                                FileUploadSetting::SESSION_TIMEOUT_HOURS => 'Session Timeout (Hours)',
                                default => $key,
                            };
                            
                            $displayValue = is_bool($value) ? ($value ? 'Yes' : 'No') : $value;
                            $content .= "<div><strong>{$label}:</strong> {$displayValue}</div>";
                        }
                        
                        $content .= "</div></div>";
                    }
                    
                    $content .= '</div>';
                    return view('filament.modal-content', ['content' => $content]);
                })
                ->modalWidth('4xl'),
                
            Actions\Action::make('seed_defaults')
                ->label('Seed Default Settings')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Seed Default Settings')
                ->modalDescription('This will create default settings for all contexts if they don\'t exist. Existing settings will not be modified.')
                ->action(function () {
                    $created = 0;
                    $contexts = FileUploadSetting::getValidContexts();
                    
                    foreach ($contexts as $context) {
                        foreach (FileUploadSetting::DEFAULT_VALUES as $key => $value) {
                            $existing = FileUploadSetting::where('context', $context)
                                ->where('key', $key)
                                ->first();
                                
                            if (!$existing) {
                                FileUploadSetting::create([
                                    'context' => $context,
                                    'key' => $key,
                                    'value' => $value,
                                    'description' => FileUploadSetting::getSettingsSchema()[$key]['description'] ?? null,
                                ]);
                                $created++;
                            }
                        }
                    }
                    
                    if ($created > 0) {
                        Notification::make()
                            ->title('Default Settings Created')
                            ->body("Created {$created} default settings.")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('No Settings Created')
                            ->body('All default settings already exist.')
                            ->info()
                            ->send();
                    }
                }),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            // Add widgets here if needed
        ];
    }
}