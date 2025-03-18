<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class FilesRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('File Name'),
                    
                Forms\Components\FileUpload::make('path')
                    ->required()
                    ->acceptedFileTypes(['audio/mpeg', 'audio/wav', 'audio/aiff', 'audio/mp3', 'audio/flac', 'audio/ogg', 'application/zip', 'application/x-zip-compressed'])
                    ->directory('project-files')
                    ->visibility('private')
                    ->maxSize(200 * 1024), // 200MB
                    
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                    
                Tables\Columns\IconColumn::make('type')
                    ->icon(fn (string $state): string => match ($state) {
                        'audio' => 'heroicon-o-musical-note',
                        'stems' => 'heroicon-o-squares-2x2',
                        'project_file' => 'heroicon-o-document',
                        'sheet_music' => 'heroicon-o-document-text',
                        'lyrics' => 'heroicon-o-document-text',
                        'artwork' => 'heroicon-o-photo',
                        default => 'heroicon-o-document',
                    })
                    ->label('File Type'),
                    
                Tables\Columns\TextColumn::make('formatted_size')
                    ->label('Size')
                    ->getStateUsing(function ($record): string {
                        $bytes = $record->size ?? 0;
                        if ($bytes >= 1073741824) {
                            return number_format($bytes / 1073741824, 2) . ' GB';
                        } elseif ($bytes >= 1048576) {
                            return number_format($bytes / 1048576, 2) . ' MB';
                        } elseif ($bytes >= 1024) {
                            return number_format($bytes / 1024, 2) . ' KB';
                        }
                        return $bytes . ' B';
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if (isset($data['path']) && is_array($data['path'])) {
                            $path = $data['path'][0];
                            $data['size'] = filesize(storage_path('app/public/' . $path));
                            $data['path'] = $path;
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => route('project.file.download', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 