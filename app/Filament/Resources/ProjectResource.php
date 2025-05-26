<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Illuminate\Database\Eloquent\Model;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-m-document';
    
    protected static ?string $navigationGroup = 'Content Management';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Project Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Project Name'),
                                
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->required()
                                    ->label('Project Owner'),
                                
                                Forms\Components\TextInput::make('artist_name')
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('genre')
                                    ->maxLength(255),
                                
                                Forms\Components\Select::make('workflow_type')
                                    ->label('Workflow Type')
                                    ->options(collect(Project::getWorkflowTypes())->mapWithKeys(fn($type) => [$type => Project::getReadableWorkflowTypeAttribute($type)]))
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn (callable $set) => $set('target_producer_id', null)),
                                
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'in_progress' => 'In Progress',
                                        'pending_review' => 'Pending Review',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->required(),
                            ]),
                        
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                
                Section::make('Project Media')
                    ->schema([
                        Forms\Components\FileUpload::make('image_path')
                            ->image()
                            ->directory('project-images')
                            ->visibility('public')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('1200')
                            ->imageResizeTargetHeight('675')
                            ->label('Project Cover Image'),
                        
                        Forms\Components\TextInput::make('preview_track')
                            ->maxLength(255)
                            ->label('Preview Track URL')
                            ->helperText('URL to a preview audio track, if available'),
                    ]),
                
                Section::make('Collaboration Details')
                    ->schema([
                        Forms\Components\TagsInput::make('collaboration_type')
                            ->placeholder('Add collaboration types')
                            ->helperText('Enter collaboration type and press Enter')
                            ->label('Collaboration Types'),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('budget')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0),
                                
                                Forms\Components\DatePicker::make('deadline')
                                    ->label('Project Deadline'),
                            ]),
                        
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->label('Collaboration Notes')
                            ->columnSpanFull(),
                    ]),
                
                Section::make('Publication & Storage')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_published')
                                    ->label('Published')
                                    ->helperText('Make this project visible to the public')
                                    ->default(false),
                                
                                Forms\Components\DateTimePicker::make('completed_at')
                                    ->label('Completion Date'),
                                
                                Forms\Components\TextInput::make('total_storage_used')
                                    ->numeric()
                                    ->suffix('MB')
                                    ->disabled()
                                    ->default(0)
                                    ->label('Total Storage Used'),
                                
                                Forms\Components\TextInput::make('slug')
                                    ->maxLength(255)
                                    ->helperText('URL-friendly name (auto-generated if left empty)')
                                    ->disabled(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Cover')
                    ->square(),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Owner'),
                
                Tables\Columns\TextColumn::make('workflow_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'album' => 'success',
                        'single' => 'info',
                        'ep' => 'warning',
                        'remix' => 'danger',
                        'cover' => 'gray',
                        'soundtrack' => 'primary',
                        default => 'secondary',
                    }),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'in_progress' => 'info',
                        'pending_review' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'secondary',
                    }),
                
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean()
                    ->label('Published'),
                
                Tables\Columns\TextColumn::make('deadline')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'in_progress' => 'In Progress',
                        'pending_review' => 'Pending Review',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                
                Tables\Filters\SelectFilter::make('workflow_type')
                    ->label('Workflow Type')
                    ->options(collect(Project::getWorkflowTypes())->mapWithKeys(fn($type) => [$type => Project::getReadableWorkflowTypeAttribute($type)])),
                
                Tables\Filters\Filter::make('is_published')
                    ->query(fn (Builder $query): Builder => $query->where('is_published', true))
                    ->label('Published Only')
                    ->toggle(),
                
                Tables\Filters\Filter::make('completed')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('completed_at'))
                    ->label('Completed Only')
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markAsCompleted')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-circle')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->update([
                                    'status' => 'completed',
                                    'completed_at' => now(),
                                ]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PitchesRelationManager::class,
            RelationManagers\FilesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description', 'artist_name', 'genre', 'slug'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Artist' => $record->artist_name,
            'Genre' => $record->genre,
            'Status' => $record->status,
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }
}
