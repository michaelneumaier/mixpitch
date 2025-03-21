<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailAuditResource\Pages;
use App\Filament\Resources\EmailAuditResource\RelationManagers;
use App\Models\EmailAudit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmailAuditResource extends Resource
{
    protected static ?string $model = EmailAudit::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    
    protected static ?string $navigationGroup = 'Email Management';
    
    protected static ?string $navigationLabel = 'Email Audit';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Email Information')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('recipient_name')
                            ->label('Recipient Name')
                            ->maxLength(255)
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('subject')
                            ->maxLength(255)
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('message_id')
                            ->label('Message ID')
                            ->maxLength(255)
                            ->columnSpan(1),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'sent' => 'Sent',
                                'queued' => 'Queued',
                                'suppressed' => 'Suppressed',
                                'bounced' => 'Bounced',
                                'complained' => 'Complained',
                                'failed' => 'Failed',
                            ])
                            ->required()
                            ->columnSpan(1),
                        
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Logged At')
                            ->disabled()
                            ->columnSpan(1),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Email Content')
                    ->schema([
                        Forms\Components\ViewField::make('content')
                            ->view('filament.forms.components.email-content-viewer')
                            ->columnSpan('full')
                            ->visible(fn ($record) => !empty($record->content)),
                    ])
                    ->collapsible()
                    ->collapsed(false),
                
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->keyLabel('Property')
                            ->valueLabel('Value')
                            ->columnSpan('full'),
                        
                        Forms\Components\KeyValue::make('headers')
                            ->keyLabel('Header')
                            ->valueLabel('Value')
                            ->columnSpan('full')
                            ->visible(fn ($record) => !empty($record->headers)),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('recipient_name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('message_id')
                    ->searchable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'queued',
                        'success' => 'sent',
                        'warning' => 'suppressed',
                        'danger' => fn ($state) => in_array($state, ['bounced', 'complained', 'failed']),
                    ]),
                
                Tables\Columns\TextColumn::make('metadata.mailable_class')
                    ->label('Email Class')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('metadata.email_type')
                    ->label('Type')
                    ->searchable(),
                
                Tables\Columns\IconColumn::make('content')
                    ->label('Has Content')
                    ->boolean()
                    ->state(fn ($record) => !empty($record->content))
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'sent' => 'Sent',
                        'queued' => 'Queued',
                        'suppressed' => 'Suppressed',
                        'bounced' => 'Bounced',
                        'complained' => 'Complained',
                        'failed' => 'Failed',
                    ]),
                
                Tables\Filters\Filter::make('has_content')
                    ->label('Has Email Content')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('content'))
                    ->toggle(),
                
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailAudits::route('/'),
            'create' => Pages\CreateEmailAudit::route('/create'),
            'view' => Pages\ViewEmailAudit::route('/{record}'),
            'edit' => Pages\EditEmailAudit::route('/{record}/edit'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; // This is an audit log, so we don't want direct creation
    }
    
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false; // This is an audit log, so we don't want direct editing
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && (auth()->user()->can('view_email_audit') || auth()->user()->hasRole('admin'));
    }
}
