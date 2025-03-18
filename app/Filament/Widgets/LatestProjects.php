<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Project;
use App\Filament\Resources\ProjectResource;

class LatestProjects extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    
    protected static ?int $sort = 17;
    
    protected function getTableHeading(): string
    {
        return 'Latest Projects';
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Project::query()->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'in_progress' => 'blue',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
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
                    ->url(fn (Project $record): string => ProjectResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ])
            ->emptyStateHeading('No projects yet')
            ->emptyStateDescription('Projects will appear here once they are created.')
            ->emptyStateIcon('heroicon-o-document')
            ->paginated(false);
    }
}
