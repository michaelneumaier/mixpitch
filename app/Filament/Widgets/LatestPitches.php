<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PitchResource;
use App\Models\Pitch;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestPitches extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 16;

    protected function getTableHeading(): string
    {
        return 'Latest Pitches';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Pitch::query()->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Submitted By')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Pitch $record): string => PitchResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ])
            ->emptyStateHeading('No pitches yet')
            ->emptyStateDescription('Pitches will appear here once they are submitted.')
            ->emptyStateIcon('heroicon-o-musical-note')
            ->paginated(false);
    }
}
