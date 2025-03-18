<?php

namespace App\Filament\Resources\PitchResource\RelationManagers;

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
                Forms\Components\TextInput::make('filename')
                    ->required()
                    ->maxLength(255)
                    ->label('File Name'),
                    
                Forms\Components\FileUpload::make('path')
                    ->required()
                    ->acceptedFileTypes(['audio/mpeg', 'audio/wav', 'audio/aiff', 'audio/mp3', 'audio/flac', 'audio/ogg', 'application/zip', 'application/x-zip-compressed'])
                    ->directory('pitch-files')
                    ->visibility('private')
                    ->maxSize(100 * 1024), // 100MB
                    
                Forms\Components\Textarea::make('note')
                    ->rows(3)
                    ->label('Note')
                    ->helperText('Add a note to this file'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('filename')
            ->columns([
                Tables\Columns\TextColumn::make('filename')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('version')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('waveform_generated')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Generated' : 'Pending')
                    ->toggleable(),
                    
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
                    
                Tables\Columns\TextColumn::make('duration_formatted')
                    ->label('Duration')
                    ->getStateUsing(function ($record): string {
                        $seconds = $record->duration ?? 0;
                        if ($seconds <= 0) {
                            return '—';
                        }
                        
                        $minutes = floor($seconds / 60);
                        $remainingSeconds = $seconds % 60;
                        return sprintf('%d:%02d', $minutes, $remainingSeconds);
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
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
                    ->url(fn ($record) => route('pitch.file.download', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 