<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTestResource\Pages;
use App\Models\EmailTest;
use App\Services\EmailService;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmailTestResource extends Resource
{
    protected static ?string $model = EmailTest::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Email Management';

    protected static ?string $navigationLabel = 'Email Test Tool';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->schema([
                        TextInput::make('recipient_email')
                            ->email()
                            ->required()
                            ->label('Recipient Email')
                            ->placeholder('Enter recipient email'),

                        TextInput::make('subject')
                            ->label('Subject')
                            ->placeholder('Test email subject')
                            ->default('Test Email from MixPitch'),

                        Select::make('template')
                            ->label('Email Template')
                            ->options([
                                'emails.test' => 'Standard Test Template',
                                // Add other templates if available
                            ])
                            ->default('emails.test')
                            ->required(),

                        KeyValue::make('content_variables')
                            ->label('Content Variables')
                            ->keyLabel('Variable')
                            ->valueLabel('Value')
                            ->keyPlaceholder('Enter variable name')
                            ->valuePlaceholder('Enter value')
                            ->addButtonLabel('Add Variable')
                            ->columnSpan('full'),
                    ])
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('recipient_email')
                    ->label('Recipient')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subject')
                    ->label('Subject')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('template')
                    ->label('Template')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'failed',
                        'warning' => 'pending',
                        'success' => 'sent',
                    ]),

                TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Action::make('send')
                    ->label('Send Test')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->action(function (EmailTest $record, EmailService $emailService) {
                        try {
                            // Send the test email
                            $result = $emailService->sendTestEmail(
                                $record->recipient_email,
                                $record->subject,
                                $record->template,
                                $record->content_variables ?? []
                            );

                            // Update the record
                            $record->update([
                                'status' => 'sent',
                                'result' => $result,
                                'sent_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Test email sent successfully')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            // Update the record with failure
                            $record->update([
                                'status' => 'failed',
                                'result' => ['error' => $e->getMessage()],
                            ]);

                            Notification::make()
                                ->title('Failed to send test email')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (EmailTest $record): bool => $record->status !== 'sent'),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListEmailTests::route('/'),
            'create' => Pages\CreateEmailTest::route('/create'),
            'edit' => Pages\EditEmailTest::route('/{record}/edit'),
            'view' => Pages\ViewEmailTest::route('/{record}'),
        ];
    }
}
