<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailEventResource\Pages;
use App\Filament\Resources\EmailEventResource\RelationManagers;
use App\Models\EmailEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmailEventResource extends Resource
{
    protected static ?string $model = EmailEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    
    protected static ?string $navigationGroup = 'Email Management';
    
    protected static ?string $navigationLabel = 'Email Audit Log';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'email';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Email Event Details')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('message_id')
                            ->maxLength(255)
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('event_type')
                            ->required()
                            ->maxLength(255)
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('email_type')
                            ->maxLength(255)
                            ->disabled(),
                        
                        Forms\Components\KeyValue::make('metadata')
                            ->keyLabel('Property')
                            ->valueLabel('Value')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Event Time')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('event_type')
                    ->colors([
                        'primary' => 'queued',
                        'success' => 'sent',
                        'warning' => 'opened',
                        'danger' => fn ($state) => in_array($state, ['bounced', 'complained', 'rejected']),
                        'info' => fn ($state) => in_array($state, ['clicked', 'delivered']),
                    ]),
                
                Tables\Columns\TextColumn::make('email_type')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('metadata.mailable_class')
                    ->label('Email Class')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Event Time')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
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
                
                Tables\Filters\SelectFilter::make('email_type')
                    ->options(function () {
                        return EmailEvent::distinct('email_type')
                            ->whereNotNull('email_type')
                            ->pluck('email_type', 'email_type')
                            ->toArray();
                    }),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
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
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailEvents::route('/'),
            'view' => Pages\ViewEmailEvent::route('/{record}'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }
    
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
