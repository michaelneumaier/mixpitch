<?php

namespace App\Filament\Widgets;

use App\Models\ProjectFile;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Str;

class FilesOverview extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 15;

    protected function getTableHeading(): string
    {
        return 'Recent Project Files';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProjectFile::query()
                    ->with('project')
                    ->latest()
                    ->limit(10)
            )
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
            ->actions([
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->url(fn (ProjectFile $record): string => $record->getSignedUrlAttribute())
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('No files uploaded yet')
            ->emptyStateDescription('Files will appear here after they are uploaded to projects.')
            ->emptyStateIcon('heroicon-m-document')
            ->paginated(false);
    }
}
