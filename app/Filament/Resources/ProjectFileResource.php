<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectFileResource\Pages;
use App\Models\ProjectFile;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProjectFileResource extends Resource
{
    protected static ?string $model = ProjectFile::class;

    protected static ?string $navigationIcon = 'heroicon-m-document-duplicate';

    protected static ?string $navigationGroup = 'Content Management';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'file_name';

    public static function getNavigationLabel(): string
    {
        return 'Project Files';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Project Files';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('File Details')
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->maxLength(255)
                            ->required()
                            ->label('File Name'),

                        Forms\Components\FileUpload::make('file_path')
                            ->required()
                            ->acceptedFileTypes(['audio/mpeg', 'audio/wav', 'audio/aiff', 'audio/mp3', 'audio/flac', 'audio/ogg', 'application/zip', 'application/x-zip-compressed', 'application/pdf', 'image/jpeg', 'image/png'])
                            ->directory('project-files')
                            ->visibility('private')
                            ->maxSize(200 * 1024) // 200MB
                            ->label('File')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->options([
                                'audio' => 'Audio',
                                'stems' => 'Stems',
                                'project_file' => 'Project File',
                                'sheet_music' => 'Sheet Music',
                                'lyrics' => 'Lyrics',
                                'artwork' => 'Artwork',
                                'other' => 'Other',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Str::title(str_replace('_', ' ', $state)))
                    ->color(fn (string $state): string => match ($state) {
                        'audio' => 'success',
                        'stems' => 'info',
                        'project_file' => 'warning',
                        'sheet_music' => 'gray',
                        'lyrics' => 'purple',
                        'artwork' => 'pink',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('formatted_size')
                    ->label('Size')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('size', $direction)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'audio' => 'Audio',
                        'stems' => 'Stems',
                        'project_file' => 'Project File',
                        'sheet_music' => 'Sheet Music',
                        'lyrics' => 'Lyrics',
                        'artwork' => 'Artwork',
                        'other' => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('project')
                    ->relationship('project', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->url(fn (ProjectFile $record): string => $record->getSignedUrlAttribute())
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectFiles::route('/'),
            'create' => Pages\CreateProjectFile::route('/create'),
            'view' => Pages\ViewProjectFile::route('/{record}'),
            'edit' => Pages\EditProjectFile::route('/{record}/edit'),
        ];
    }
}
