<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailSuppressionResource\Pages;
use App\Filament\Resources\EmailSuppressionResource\RelationManagers;
use App\Models\EmailSuppression;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class EmailSuppressionResource extends Resource
{
    protected static ?string $model = EmailSuppression::class;

    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';
    
    protected static ?string $navigationGroup = 'Email Management';
    
    protected static ?string $navigationLabel = 'Suppressed Emails';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $recordTitleAttribute = 'email';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Suppression Details')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Select::make('type')
                            ->options([
                                'bounce' => 'Bounce',
                                'complaint' => 'Complaint',
                                'manual' => 'Manual',
                            ])
                            ->required(),
                        
                        Forms\Components\TextInput::make('reason')
                            ->maxLength(255),
                        
                        Forms\Components\KeyValue::make('metadata')
                            ->keyLabel('Property')
                            ->valueLabel('Value'),
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
                
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'danger' => 'bounce',
                        'warning' => 'complaint',
                        'info' => 'manual',
                    ]),
                
                Tables\Columns\TextColumn::make('reason')
                    ->searchable()
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Suppressed At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'bounce' => 'Bounce',
                        'complaint' => 'Complaint',
                        'manual' => 'Manual',
                    ]),
                
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        Notification::make()
                            ->title('Email removed from suppression list')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
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
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailSuppressions::route('/'),
            'create' => Pages\CreateEmailSuppression::route('/create'),
            'edit' => Pages\EditEmailSuppression::route('/{record}/edit'),
        ];
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
