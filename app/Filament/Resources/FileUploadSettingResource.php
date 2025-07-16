<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FileUploadSettingResource\Pages;
use App\Models\FileUploadSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class FileUploadSettingResource extends Resource
{
    protected static ?string $model = FileUploadSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-up';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?string $navigationLabel = 'File Upload Settings';
    
    protected static ?string $modelLabel = 'File Upload Setting';
    
    protected static ?string $pluralModelLabel = 'File Upload Settings';
    
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Setting Configuration')
                    ->schema([
                        Forms\Components\Select::make('context')
                            ->label('Context')
                            ->options([
                                FileUploadSetting::CONTEXT_GLOBAL => 'Global (Default)',
                                FileUploadSetting::CONTEXT_PROJECTS => 'Projects',
                                FileUploadSetting::CONTEXT_PITCHES => 'Pitches',
                                FileUploadSetting::CONTEXT_CLIENT_PORTALS => 'Client Portals',
                            ])
                            ->default(FileUploadSetting::CONTEXT_GLOBAL)
                            ->required()
                            ->helperText('The context where this setting applies. Global settings are used as defaults.'),
                            
                        Forms\Components\Select::make('key')
                            ->label('Setting')
                            ->options([
                                FileUploadSetting::MAX_FILE_SIZE_MB => 'Maximum File Size (MB)',
                                FileUploadSetting::CHUNK_SIZE_MB => 'Chunk Size (MB)',
                                FileUploadSetting::MAX_CONCURRENT_UPLOADS => 'Maximum Concurrent Uploads',
                                FileUploadSetting::MAX_RETRY_ATTEMPTS => 'Maximum Retry Attempts',
                                FileUploadSetting::ENABLE_CHUNKING => 'Enable Chunked Uploads',
                                FileUploadSetting::SESSION_TIMEOUT_HOURS => 'Session Timeout (Hours)',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Set default value and description based on selected key
                                $schema = FileUploadSetting::getSettingsSchema();
                                if (isset($schema[$state])) {
                                    $set('value', $schema[$state]['default']);
                                    $set('description', $schema[$state]['description']);
                                }
                            })
                            ->helperText('The specific setting to configure'),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('value')
                                    ->label('Value')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $get, Forms\Set $set) {
                                        // Validate the value based on the selected key
                                        $key = $get('key');
                                        if ($key) {
                                            try {
                                                FileUploadSetting::validateSetting($key, $state);
                                            } catch (\Exception $e) {
                                                Notification::make()
                                                    ->title('Invalid Value')
                                                    ->body($e->getMessage())
                                                    ->danger()
                                                    ->send();
                                            }
                                        }
                                    })
                                    ->helperText(function ($get) {
                                        $key = $get('key');
                                        if ($key && isset(FileUploadSetting::VALIDATION_RULES[$key])) {
                                            $rule = FileUploadSetting::VALIDATION_RULES[$key];
                                            $default = FileUploadSetting::DEFAULT_VALUES[$key] ?? 'N/A';
                                            return "Validation: {$rule} | Default: {$default}";
                                        }
                                        return 'Enter the setting value';
                                    }),
                                    
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->rows(3)
                                    ->helperText('Optional description for this setting override'),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Setting Information')
                    ->schema([
                        Forms\Components\Placeholder::make('setting_info')
                            ->label('Setting Details')
                            ->content(function ($get) {
                                $key = $get('key');
                                if (!$key) {
                                    return 'Select a setting to see details';
                                }
                                
                                $schema = FileUploadSetting::getSettingsSchema();
                                if (!isset($schema[$key])) {
                                    return 'Unknown setting';
                                }
                                
                                $info = $schema[$key];
                                return "
                                    <div class='space-y-2'>
                                        <div><strong>Description:</strong> {$info['description']}</div>
                                        <div><strong>Type:</strong> {$info['type']}</div>
                                        <div><strong>Default Value:</strong> " . json_encode($info['default']) . "</div>
                                        <div><strong>Validation Rules:</strong> {$info['validation']}</div>
                                    </div>
                                ";
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('context')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        FileUploadSetting::CONTEXT_GLOBAL => 'gray',
                        FileUploadSetting::CONTEXT_PROJECTS => 'blue',
                        FileUploadSetting::CONTEXT_PITCHES => 'green',
                        FileUploadSetting::CONTEXT_CLIENT_PORTALS => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        FileUploadSetting::CONTEXT_GLOBAL => 'Global',
                        FileUploadSetting::CONTEXT_PROJECTS => 'Projects',
                        FileUploadSetting::CONTEXT_PITCHES => 'Pitches',
                        FileUploadSetting::CONTEXT_CLIENT_PORTALS => 'Client Portals',
                        default => $state,
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('key')
                    ->label('Setting')
                    ->formatStateUsing(function (string $state): string {
                        $labels = [
                            FileUploadSetting::MAX_FILE_SIZE_MB => 'Max File Size (MB)',
                            FileUploadSetting::CHUNK_SIZE_MB => 'Chunk Size (MB)',
                            FileUploadSetting::MAX_CONCURRENT_UPLOADS => 'Max Concurrent Uploads',
                            FileUploadSetting::MAX_RETRY_ATTEMPTS => 'Max Retry Attempts',
                            FileUploadSetting::ENABLE_CHUNKING => 'Enable Chunking',
                            FileUploadSetting::SESSION_TIMEOUT_HOURS => 'Session Timeout (Hours)',
                        ];
                        return $labels[$state] ?? $state;
                    })
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('value')
                    ->formatStateUsing(function ($state, $record): string {
                        if (is_bool($state)) {
                            return $state ? 'Yes' : 'No';
                        }
                        if (is_array($state)) {
                            return json_encode($state);
                        }
                        return (string) $state;
                    })
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    })
                    ->placeholder('No description'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('context')
                    ->options([
                        FileUploadSetting::CONTEXT_GLOBAL => 'Global',
                        FileUploadSetting::CONTEXT_PROJECTS => 'Projects',
                        FileUploadSetting::CONTEXT_PITCHES => 'Pitches',
                        FileUploadSetting::CONTEXT_CLIENT_PORTALS => 'Client Portals',
                    ]),
                    
                Tables\Filters\SelectFilter::make('key')
                    ->label('Setting')
                    ->options([
                        FileUploadSetting::MAX_FILE_SIZE_MB => 'Max File Size (MB)',
                        FileUploadSetting::CHUNK_SIZE_MB => 'Chunk Size (MB)',
                        FileUploadSetting::MAX_CONCURRENT_UPLOADS => 'Max Concurrent Uploads',
                        FileUploadSetting::MAX_RETRY_ATTEMPTS => 'Max Retry Attempts',
                        FileUploadSetting::ENABLE_CHUNKING => 'Enable Chunking',
                        FileUploadSetting::SESSION_TIMEOUT_HOURS => 'Session Timeout (Hours)',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        Notification::make()
                            ->title('Setting Deleted')
                            ->body('The setting has been deleted and cache has been cleared.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            FileUploadSetting::clearSettingsCache();
                            Notification::make()
                                ->title('Settings Deleted')
                                ->body('Selected settings have been deleted and cache has been cleared.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('reset_to_defaults')
                    ->label('Reset to Defaults')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Settings to Defaults')
                    ->modalDescription('This will delete all custom settings and revert to system defaults. This action cannot be undone.')
                    ->action(function () {
                        foreach (FileUploadSetting::getValidContexts() as $context) {
                            FileUploadSetting::resetToDefaults($context);
                        }
                        
                        Notification::make()
                            ->title('Settings Reset')
                            ->body('All settings have been reset to defaults.')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('clear_cache')
                    ->label('Clear Cache')
                    ->icon('heroicon-o-trash')
                    ->color('gray')
                    ->action(function () {
                        FileUploadSetting::clearSettingsCache();
                        
                        Notification::make()
                            ->title('Cache Cleared')
                            ->body('Settings cache has been cleared.')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('context')
            ->defaultSort('key');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFileUploadSettings::route('/'),
            'create' => Pages\CreateFileUploadSetting::route('/create'),
            'edit' => Pages\EditFileUploadSetting::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}