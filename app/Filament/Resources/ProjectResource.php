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
                    ->square()
                    ->defaultImageUrl(fn ($record) => "https://ui-avatars.com/api/?name=" . urlencode($record->name) . "&color=6366f1&background=e0e7ff"),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Owner')
                    ->formatStateUsing(fn ($state, $record) => $state . ' (' . $record->user->email . ')'),
                
                Tables\Columns\TextColumn::make('workflow_type')
                    ->badge()
                    ->searchable()
                    ->color(fn (string $state): string => match ($state) {
                        'album' => 'success',
                        'single' => 'info',
                        'ep' => 'warning',
                        'remix' => 'danger',
                        'cover' => 'gray',
                        'soundtrack' => 'primary',
                        'direct_hire' => 'purple',
                        'client_management' => 'orange',
                        default => 'secondary',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'in_progress' => 'info',
                        'pending_review' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('budget')
                    ->money('USD')
                    ->sortable()
                    ->color(fn ($state): string => $state > 0 ? 'success' : 'gray')
                    ->badge(fn ($state): bool => $state > 0),
                
                Tables\Columns\TextColumn::make('pitches_count')
                    ->counts('pitches')
                    ->label('Pitches')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray'),
                
                Tables\Columns\TextColumn::make('total_storage_used')
                    ->label('Storage')
                    ->formatStateUsing(fn ($state): string => $state ? number_format($state / 1024 / 1024, 1) . ' MB' : '0 MB')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('deadline')
                    ->date()
                    ->sortable()
                    ->color(function ($state): string {
                        if (!$state) return 'gray';
                        $daysUntil = now()->diffInDays($state, false);
                        return $daysUntil < 0 ? 'danger' : ($daysUntil <= 7 ? 'warning' : 'success');
                    })
                    ->formatStateUsing(function ($state): string {
                        if (!$state) return 'No deadline';
                        $daysUntil = now()->diffInDays($state, false);
                        if ($daysUntil < 0) return 'Overdue by ' . abs($daysUntil) . ' days';
                        if ($daysUntil == 0) return 'Due today';
                        return 'Due in ' . $daysUntil . ' days';
                    })
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'in_progress' => 'In Progress',
                        'pending_review' => 'Pending Review',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('workflow_type')
                    ->label('Workflow Type')
                    ->options([
                        'album' => 'Album',
                        'single' => 'Single',
                        'ep' => 'EP',
                        'remix' => 'Remix',
                        'cover' => 'Cover',
                        'soundtrack' => 'Soundtrack',
                        'direct_hire' => 'Direct Hire',
                        'client_management' => 'Client Management',
                    ])
                    ->multiple(),
                    
                Tables\Filters\Filter::make('is_published')
                    ->label('Published Projects')
                    ->query(fn (Builder $query): Builder => $query->where('is_published', true)),
                    
                Tables\Filters\Filter::make('has_budget')
                    ->label('Paid Projects')
                    ->query(fn (Builder $query): Builder => $query->where('budget', '>', 0)),
                    
                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Projects')
                    ->query(fn (Builder $query): Builder => $query->where('deadline', '<', now())->whereNotIn('status', ['completed', 'cancelled'])),
                    
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created from'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('view_pitches')
                        ->label('View Pitches')
                        ->icon('heroicon-m-musical-note')
                        ->url(fn (Project $record): string => route('filament.admin.resources.pitches.index', ['tableFilters[project_id][value]' => $record->id]))
                        ->color('info'),
                    Tables\Actions\Action::make('toggle_published')
                        ->label(fn (Project $record): string => $record->is_published ? 'Unpublish' : 'Publish')
                        ->icon(fn (Project $record): string => $record->is_published ? 'heroicon-m-eye-slash' : 'heroicon-m-eye')
                        ->color(fn (Project $record): string => $record->is_published ? 'warning' : 'success')
                        ->action(function (Project $record): void {
                            $record->update(['is_published' => !$record->is_published]);
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteAction::make(),
                ])->label('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Publish Selected')
                        ->icon('heroicon-m-eye')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => true]))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('Unpublish Selected')
                        ->icon('heroicon-m-eye-slash')
                        ->color('warning')
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => false]))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('mark_completed')
                        ->label('Mark as Completed')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'completed', 'completed_at' => now()]))
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('60s');
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
