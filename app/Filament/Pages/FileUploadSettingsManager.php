<?php

namespace App\Filament\Pages;

use App\Models\FileUploadSetting;
use App\Services\FileUploadSettingsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class FileUploadSettingsManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-up';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?string $navigationLabel = 'Upload Settings Manager';
    
    protected static string $view = 'filament.pages.file-upload-settings-manager';
    
    protected static ?int $navigationSort = 9;

    public array $globalSettings = [];
    public array $projectsSettings = [];
    public array $pitchesSettings = [];
    public array $clientPortalsSettings = [];

    public function mount(): void
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        $this->globalSettings = $this->getSettingsForContext(FileUploadSetting::CONTEXT_GLOBAL);
        $this->projectsSettings = $this->getSettingsForContext(FileUploadSetting::CONTEXT_PROJECTS);
        $this->pitchesSettings = $this->getSettingsForContext(FileUploadSetting::CONTEXT_PITCHES);
        $this->clientPortalsSettings = $this->getSettingsForContext(FileUploadSetting::CONTEXT_CLIENT_PORTALS);
    }

    protected function getSettingsForContext(string $context): array
    {
        $settings = [];
        $contextSettings = FileUploadSetting::where('context', $context)->pluck('value', 'key')->toArray();
        
        foreach (FileUploadSetting::DEFAULT_VALUES as $key => $defaultValue) {
            $settings[$key] = $contextSettings[$key] ?? null;
        }
        
        return $settings;
    }

    public function getGlobalForm(): Form
    {
        return $this->makeForm()
            ->schema($this->getFormSchema())
            ->statePath('globalSettings');
    }

    public function getProjectsForm(): Form
    {
        return $this->makeForm()
            ->schema($this->getFormSchema())
            ->statePath('projectsSettings');
    }

    public function getPitchesForm(): Form
    {
        return $this->makeForm()
            ->schema($this->getFormSchema())
            ->statePath('pitchesSettings');
    }

    public function getClientPortalsForm(): Form
    {
        return $this->makeForm()
            ->schema($this->getFormSchema())
            ->statePath('clientPortalsSettings');
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make(FileUploadSetting::MAX_FILE_SIZE_MB)
                        ->label('Maximum File Size (MB)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(2048)
                        ->placeholder(FileUploadSetting::DEFAULT_VALUES[FileUploadSetting::MAX_FILE_SIZE_MB])
                        ->helperText('Leave empty to use default or parent context value'),
                        
                    Forms\Components\TextInput::make(FileUploadSetting::CHUNK_SIZE_MB)
                        ->label('Chunk Size (MB)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(50)
                        ->placeholder(FileUploadSetting::DEFAULT_VALUES[FileUploadSetting::CHUNK_SIZE_MB])
                        ->helperText('Leave empty to use default or parent context value'),
                        
                    Forms\Components\TextInput::make(FileUploadSetting::MAX_CONCURRENT_UPLOADS)
                        ->label('Maximum Concurrent Uploads')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(10)
                        ->placeholder(FileUploadSetting::DEFAULT_VALUES[FileUploadSetting::MAX_CONCURRENT_UPLOADS])
                        ->helperText('Leave empty to use default or parent context value'),
                        
                    Forms\Components\TextInput::make(FileUploadSetting::MAX_RETRY_ATTEMPTS)
                        ->label('Maximum Retry Attempts')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(5)
                        ->placeholder(FileUploadSetting::DEFAULT_VALUES[FileUploadSetting::MAX_RETRY_ATTEMPTS])
                        ->helperText('Leave empty to use default or parent context value'),
                        
                    Forms\Components\Toggle::make(FileUploadSetting::ENABLE_CHUNKING)
                        ->label('Enable Chunked Uploads')
                        ->helperText('Leave unchecked to use default or parent context value'),
                        
                    Forms\Components\TextInput::make(FileUploadSetting::SESSION_TIMEOUT_HOURS)
                        ->label('Session Timeout (Hours)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(168)
                        ->placeholder(FileUploadSetting::DEFAULT_VALUES[FileUploadSetting::SESSION_TIMEOUT_HOURS])
                        ->helperText('Leave empty to use default or parent context value'),
                ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save_all')
                ->label('Save All Settings')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('saveAllSettings'),
                
            Action::make('reset_all')
                ->label('Reset All to Defaults')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Reset All Settings')
                ->modalDescription('This will reset all context-specific settings to defaults. This action cannot be undone.')
                ->action('resetAllSettings'),
                
            Action::make('clear_cache')
                ->label('Clear Settings Cache')
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->action('clearCache'),
        ];
    }

    public function saveAllSettings(): void
    {
        try {
            $service = app(FileUploadSettingsService::class);
            
            // Use the service for better caching and event handling
            $service->updateSettings($this->globalSettings, FileUploadSetting::CONTEXT_GLOBAL);
            $service->updateSettings($this->projectsSettings, FileUploadSetting::CONTEXT_PROJECTS);
            $service->updateSettings($this->pitchesSettings, FileUploadSetting::CONTEXT_PITCHES);
            $service->updateSettings($this->clientPortalsSettings, FileUploadSetting::CONTEXT_CLIENT_PORTALS);
            
            Notification::make()
                ->title('Settings Saved')
                ->body('All upload settings have been saved successfully with real-time cache updates.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('Failed to save upload settings', ['error' => $e->getMessage()]);
            
            Notification::make()
                ->title('Save Failed')
                ->body('Failed to save settings: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function saveContextSettings(string $context, array $settings): void
    {
        foreach ($settings as $key => $value) {
            if ($value === null || $value === '') {
                // Remove setting to use default/parent value
                FileUploadSetting::where('context', $context)
                    ->where('key', $key)
                    ->delete();
            } else {
                // Validate the setting
                FileUploadSetting::validateSetting($key, $value);
                
                // Save the setting
                FileUploadSetting::updateOrCreate(
                    ['context' => $context, 'key' => $key],
                    [
                        'value' => $value,
                        'description' => FileUploadSetting::getSettingsSchema()[$key]['description'] ?? null
                    ]
                );
            }
        }
    }

    public function resetAllSettings(): void
    {
        try {
            foreach (FileUploadSetting::getValidContexts() as $context) {
                FileUploadSetting::resetToDefaults($context);
            }
            
            $this->loadSettings();
            
            Notification::make()
                ->title('Settings Reset')
                ->body('All settings have been reset to defaults.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('Failed to reset upload settings', ['error' => $e->getMessage()]);
            
            Notification::make()
                ->title('Reset Failed')
                ->body('Failed to reset settings: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function clearCache(): void
    {
        $service = app(FileUploadSettingsService::class);
        $service->clearAllCache();
        
        Notification::make()
            ->title('Cache Cleared')
            ->body('Settings cache has been cleared using the enhanced caching service.')
            ->success()
            ->send();
    }

    public function getEffectiveSettings(): array
    {
        return [
            'global' => FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_GLOBAL),
            'projects' => FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_PROJECTS),
            'pitches' => FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_PITCHES),
            'client_portals' => FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_CLIENT_PORTALS),
        ];
    }
}