<?php

namespace App\Filament\Pages;

use App\Models\EmailSuppression;
use Filament\Actions;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class EmailSuppressionPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';
    
    protected static ?string $navigationGroup = 'Email Management';
    
    protected static ?string $navigationLabel = 'Suppressed Emails';
    
    protected static ?int $navigationSort = 2;
    
    protected static string $view = 'filament.pages.email-suppression-page';
    
    protected static ?string $slug = 'email-suppressions';
    
    protected static ?string $title = 'Suppressed Emails';
    
    protected function getTableQuery(): Builder
    {
        return EmailSuppression::query()->latest();
    }
    
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('email')
                ->searchable()
                ->sortable(),
            
            Tables\Columns\TextColumn::make('type')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'bounce' => 'danger',
                    'complaint' => 'warning',
                    'manual' => 'info',
                    default => 'gray',
                })
                ->sortable(),
            
            Tables\Columns\TextColumn::make('reason')
                ->searchable()
                ->limit(30),
            
            Tables\Columns\TextColumn::make('created_at')
                ->label('Suppressed At')
                ->dateTime()
                ->sortable(),
        ];
    }
    
    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('type')
                ->options([
                    'bounce' => 'Bounce',
                    'complaint' => 'Complaint',
                    'manual' => 'Manual',
                ]),
            
            Tables\Filters\Filter::make('created_at')
                ->form([
                    DatePicker::make('created_from')
                        ->label('From'),
                    DatePicker::make('created_until')
                        ->label('Until'),
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
        ];
    }
    
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        Notification::make()
                            ->title('Email removed from suppression list')
                            ->success()
                            ->send();
                    }),
            ]),
        ];
    }
    
    protected function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(function (array $data) {
                        $count = count($data['rows']);
                        Notification::make()
                            ->title("$count emails removed from suppression list")
                            ->success()
                            ->send();
                    }),
            ]),
        ];
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->model(EmailSuppression::class)
                ->form([
                    \Filament\Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    
                    \Filament\Forms\Components\Select::make('type')
                        ->options([
                            'bounce' => 'Bounce',
                            'complaint' => 'Complaint',
                            'manual' => 'Manual',
                        ])
                        ->required()
                        ->default('manual'),
                    
                    \Filament\Forms\Components\TextInput::make('reason')
                        ->maxLength(255),
                ]),
        ];
    }
    
    protected function table(Table $table): Table
    {
        return $table
            ->columns($this->getTableColumns())
            ->filters($this->getTableFilters())
            ->actions($this->getTableActions())
            ->bulkActions($this->getTableBulkActions());
    }
} 