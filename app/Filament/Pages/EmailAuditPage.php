<?php

namespace App\Filament\Pages;

use App\Models\EmailEvent;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class EmailAuditPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    
    protected static ?string $navigationGroup = 'Email Management';
    
    protected static ?string $navigationLabel = 'Email Audit Log';
    
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'filament.pages.email-audit-page';
    
    protected static ?string $slug = 'email-audit';
    
    protected static ?string $title = 'Email Audit Log';
    
    protected function getTableQuery(): Builder
    {
        return EmailEvent::query()->latest();
    }
    
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('email')
                ->searchable()
                ->sortable(),
            
            Tables\Columns\TextColumn::make('event_type')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'sent' => 'success',
                    'queued' => 'gray',
                    'delivered' => 'success',
                    'opened' => 'info',
                    'clicked' => 'warning',
                    'bounced', 'complained', 'rejected' => 'danger',
                    default => 'gray',
                })
                ->sortable(),
            
            Tables\Columns\TextColumn::make('email_type')
                ->searchable()
                ->sortable(),
            
            Tables\Columns\TextColumn::make('created_at')
                ->label('Event Time')
                ->dateTime()
                ->sortable(),
        ];
    }
    
    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('event_type')
                ->options([
                    'sent' => 'Sent',
                    'queued' => 'Queued',
                    'delivered' => 'Delivered',
                    'opened' => 'Opened',
                    'clicked' => 'Clicked',
                    'bounced' => 'Bounced',
                    'complained' => 'Complained',
                    'rejected' => 'Rejected',
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
            Tables\Actions\ViewAction::make(),
        ];
    }
    
    protected function getTableBulkActions(): array
    {
        return [];
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