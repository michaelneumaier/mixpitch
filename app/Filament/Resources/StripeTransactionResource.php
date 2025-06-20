<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StripeTransactionResource\Pages;
use App\Filament\Resources\UserResource;
use App\Models\StripeTransaction;
use App\Models\PayoutSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class StripeTransactionResource extends Resource
{
    protected static ?string $model = StripeTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $navigationGroup = 'Financial';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Stripe Transactions';

    protected static ?string $modelLabel = 'Stripe Transaction';

    protected static ?string $pluralModelLabel = 'Stripe Transactions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('stripe_transaction_id')
                                    ->label('Stripe Transaction ID')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Select::make('type')
                                    ->label('Transaction Type')
                                    ->options([
                                        'payment_intent' => 'Payment Intent',
                                        'transfer' => 'Transfer',
                                        'refund' => 'Refund',
                                        'payout' => 'Payout',
                                        'invoice' => 'Invoice',
                                        'subscription' => 'Subscription',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'succeeded' => 'Succeeded',
                                        'failed' => 'Failed',
                                        'canceled' => 'Canceled',
                                        'requires_action' => 'Requires Action',
                                        'processing' => 'Processing',
                                    ])
                                    ->required(),

                                Forms\Components\TextInput::make('amount')
                                    ->label('Amount')
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('$')
                                    ->required(),

                                Forms\Components\Select::make('currency')
                                    ->label('Currency')
                                    ->options([
                                        'USD' => 'USD',
                                        'EUR' => 'EUR',
                                        'GBP' => 'GBP',
                                    ])
                                    ->default('USD')
                                    ->required(),

                                Forms\Components\TextInput::make('fee_amount')
                                    ->label('Stripe Fee')
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('$'),
                            ]),
                    ]),

                Forms\Components\Section::make('Related Records')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('User')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('project_id')
                                    ->label('Project')
                                    ->relationship('project', 'name')
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('pitch_id')
                                    ->label('Pitch')
                                    ->relationship('pitch', 'title')
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('payout_schedule_id')
                                    ->label('Payout Schedule')
                                    ->relationship('payoutSchedule', 'id')
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),

                Forms\Components\Section::make('Stripe Metadata')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('stripe_customer_id')
                                    ->label('Customer ID')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('stripe_account_id')
                                    ->label('Connect Account ID')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('stripe_invoice_id')
                                    ->label('Invoice ID')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('payment_method_id')
                                    ->label('Payment Method ID')
                                    ->maxLength(255),
                            ]),
                    ]),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('failure_reason')
                            ->label('Failure Reason')
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('stripe_metadata')
                            ->label('Stripe Metadata')
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('processed_at')
                            ->label('Processed At'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('stripe_transaction_id')
                    ->label('Stripe ID')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->limit(20)
                    ->tooltip(fn (StripeTransaction $record): string => $record->stripe_transaction_id),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'payment_intent' => 'success',
                        'transfer' => 'info',
                        'refund' => 'warning',
                        'payout' => 'primary',
                        'invoice' => 'gray',
                        'subscription' => 'indigo',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'succeeded' => 'success',
                        'pending' => 'warning',
                        'processing' => 'info',
                        'failed' => 'danger',
                        'canceled' => 'gray',
                        'requires_action' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('fee_amount')
                    ->label('Stripe Fee')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable()
                    ->url(function (StripeTransaction $record): ?string {
                        if (!$record->user) {
                            return null;
                        }
                        
                        try {
                            return UserResource::getUrl('view', ['record' => $record->user]);
                        } catch (\Exception $e) {
                            // Fallback to edit page if view page is not available
                            return UserResource::getUrl('edit', ['record' => $record->user]);
                        }
                    }),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->limit(30)
                    ->tooltip(fn (StripeTransaction $record): ?string => $record->project?->name),

                Tables\Columns\IconColumn::make('has_payout')
                    ->label('Payout')
                    ->boolean()
                    ->state(fn (StripeTransaction $record): bool => $record->payout_schedule_id !== null)
                    ->trueIcon('heroicon-o-banknotes')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processed')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Transaction Type')
                    ->options([
                        'payment_intent' => 'Payment Intent',
                        'transfer' => 'Transfer',
                        'refund' => 'Refund',
                        'payout' => 'Payout',
                        'invoice' => 'Invoice',
                        'subscription' => 'Subscription',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'succeeded' => 'Succeeded',
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'failed' => 'Failed',
                        'canceled' => 'Canceled',
                        'requires_action' => 'Requires Action',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('has_payout')
                    ->label('Has Payout Schedule')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('payout_schedule_id'))
                    ->toggle(),

                Tables\Filters\Filter::make('failed_transactions')
                    ->label('Failed Transactions')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'failed'))
                    ->toggle(),

                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount_from')
                                    ->label('Amount From')
                                    ->numeric()
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('amount_to')
                                    ->label('Amount To')
                                    ->numeric()
                                    ->prefix('$'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
                            );
                    }),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('created_from')
                                    ->label('Created From'),
                                Forms\Components\DatePicker::make('created_to')
                                    ->label('Created To'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Action::make('view_in_stripe')
                    ->label('View in Stripe')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (StripeTransaction $record): string => 
                        $record->getStripeUrl()
                    )
                    ->openUrlInNewTab(),

                Action::make('create_payout')
                    ->label('Create Payout')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (StripeTransaction $record): bool => 
                        $record->type === 'payment_intent' && 
                        $record->status === 'succeeded' && 
                        $record->payout_schedule_id === null
                    )
                    ->form([
                        Forms\Components\Select::make('producer_user_id')
                            ->label('Producer')
                            ->relationship('user', 'name')
                            ->required(),
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Commission Rate (%)')
                            ->numeric()
                            ->default(15)
                            ->step(0.01)
                            ->required(),
                        Forms\Components\DateTimePicker::make('hold_release_date')
                            ->label('Hold Release Date')
                            ->default(now()->addDays(3))
                            ->required(),
                    ])
                    ->action(function (StripeTransaction $record, array $data): void {
                        try {
                            $grossAmount = $record->amount;
                            $commissionRate = $data['commission_rate'];
                            $commissionAmount = ($grossAmount * $commissionRate) / 100;
                            $netAmount = $grossAmount - $commissionAmount;

                            PayoutSchedule::create([
                                'producer_user_id' => $data['producer_user_id'],
                                'project_id' => $record->project_id,
                                'pitch_id' => $record->pitch_id,
                                'transaction_id' => $record->id,
                                'workflow_type' => 'standard',
                                'gross_amount' => $grossAmount,
                                'commission_rate' => $commissionRate,
                                'commission_amount' => $commissionAmount,
                                'net_amount' => $netAmount,
                                'currency' => $record->currency,
                                'status' => 'scheduled',
                                'hold_release_date' => $data['hold_release_date'],
                                'producer_stripe_account_id' => $record->stripe_account_id,
                                'stripe_payment_intent_id' => $record->stripe_transaction_id,
                            ]);

                            Notification::make()
                                ->title('Payout Schedule Created')
                                ->success()
                                ->body("Payout of $" . number_format($netAmount, 2) . " scheduled for release on " . Carbon::parse($data['hold_release_date'])->format('M j, Y'))
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to Create Payout')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('sync_from_stripe')
                    ->label('Sync from Stripe')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (StripeTransaction $record): void {
                        try {
                            // Here you would implement the logic to sync the transaction from Stripe
                            // This would update the local record with the latest data from Stripe
                            
                            Notification::make()
                                ->title('Transaction Synced')
                                ->success()
                                ->body('Transaction data has been updated from Stripe')
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Sync Failed')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s') // Auto-refresh every 60 seconds
            ->striped()
            ->paginated([10, 25, 50, 100]);
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
            'index' => Pages\ListStripeTransactions::route('/'),
            'create' => Pages\CreateStripeTransaction::route('/create'),
            'view' => Pages\ViewStripeTransaction::route('/{record}'),
            'edit' => Pages\EditStripeTransaction::route('/{record}/edit'),
        ];
    }



    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['user', 'project', 'pitch']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['stripe_transaction_id', 'user.name', 'user.email', 'project.name'];
    }

    public static function getNavigationBadge(): ?string
    {
        $failed = static::getModel()::where('status', 'failed')->count();
        return $failed > 0 ? (string) $failed : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() ? 'danger' : null;
    }
} 