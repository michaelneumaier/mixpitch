<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PitchResource\Pages;
use App\Filament\Resources\PitchResource\RelationManagers;
use App\Models\Pitch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;

class PitchResource extends Resource
{
    protected static ?string $model = Pitch::class;

    protected static ?string $navigationIcon = 'heroicon-m-document-text';
    
    protected static ?string $navigationGroup = 'Content Management';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Pitch Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Created By'),
                            
                        Forms\Components\Select::make('status')
                            ->options([
                                'open' => 'Open',
                                'in_progress' => 'In Progress',
                                'pending_review' => 'Pending Review',
                                'revisions_requested' => 'Revisions Requested',
                                'accepted' => 'Accepted',
                                'rejected' => 'Rejected',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'inactive' => 'Inactive',
                                'closed' => 'Closed',
                            ])
                            ->required(),
                            
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                    
                Section::make('Pitch Settings')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('max_files')
                                    ->numeric()
                                    ->default(25)
                                    ->label('Maximum Files Allowed')
                                    ->helperText('Maximum number of files that can be uploaded to this pitch'),
                                    
                                Forms\Components\DateTimePicker::make('completion_date')
                                    ->label('Completion Date'),
                                    
                                Forms\Components\Toggle::make('is_inactive')
                                    ->label('Inactive')
                                    ->helperText('When a pitch is inactive, no new files can be uploaded')
                                    ->default(false),
                                    
                                Forms\Components\TextInput::make('total_storage_used')
                                    ->numeric()
                                    ->suffix('MB')
                                    ->default(0)
                                    ->disabled()
                                    ->label('Total Storage Used'),
                            ]),
                            
                        Forms\Components\Textarea::make('completion_feedback')
                            ->label('Completion Feedback')
                            ->helperText('Feedback provided when marking this pitch as completed')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('project.name')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->label('Project'),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Creator'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'in_progress' => 'info',
                        'pending_review' => 'warning',
                        'revisions_requested' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'inactive' => 'gray',
                        'closed' => 'gray',
                        default => 'secondary',
                    }),
                    
                Tables\Columns\IconColumn::make('is_inactive')
                    ->boolean()
                    ->label('Inactive'),
                    
                Tables\Columns\TextColumn::make('completion_date')
                    ->dateTime()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'pending_review' => 'Pending Review',
                        'revisions_requested' => 'Revisions Requested',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'inactive' => 'Inactive',
                        'closed' => 'Closed',
                    ]),
                    
                Tables\Filters\Filter::make('inactive')
                    ->query(fn (Builder $query): Builder => $query->where('is_inactive', true))
                    ->toggle(),
                    
                Tables\Filters\Filter::make('completed')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('completion_date'))
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
                                    'completion_date' => now(),
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
            RelationManagers\FilesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPitches::route('/'),
            'create' => Pages\CreatePitch::route('/create'),
            'view' => Pages\ViewPitch::route('/{record}'),
            'edit' => Pages\EditPitch::route('/{record}/edit'),
        ];
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description', 'status'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->title;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Project' => $record->project->name,
            'Status' => $record->status,
            'Creator' => $record->user->name,
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }
}
