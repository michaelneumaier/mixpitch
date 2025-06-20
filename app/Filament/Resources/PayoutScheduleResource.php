<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayoutScheduleResource\Pages;
use App\Filament\Resources\PayoutScheduleResource\RelationManagers;
use App\Filament\Resources\UserResource;
use App\Models\PayoutSchedule;
use App\Models\User;
use App\Models\Project;
use App\Services\PayoutProcessingService;
use App\Services\StripeConnectService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayoutScheduleResource extends Resource
{
    protected static ?string $model = PayoutSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationGroup = 'Financial';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Payout Schedules';

    protected static ?string $modelLabel = 'Payout Schedule';

    protected static ?string $pluralModelLabel = 'Payout Schedules';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payout Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('producer_user_id')
                                    ->label('Producer')
                                    ->relationship('producer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

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

                                Forms\Components\Select::make('workflow_type')
                                    ->label('Workflow Type')
                                    ->options([
                                        'standard' => 'Standard',
                                        'contest' => 'Contest',
                                        'client_management' => 'Client Management',
                                    ])
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Financial Details')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('gross_amount')
                                    ->label('Gross Amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->required(),

                                Forms\Components\TextInput::make('commission_rate')
                                    ->label('Commission Rate')
                                    ->numeric()
                                    ->suffix('%')
                                    ->step(0.01)
                                    ->required(),

                                Forms\Components\TextInput::make('commission_amount')
                                    ->label('Commission Amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->required(),

                                Forms\Components\TextInput::make('net_amount')
                                    ->label('Net Amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
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

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'scheduled' => 'Scheduled',
                                        'processing' => 'Processing',
                                        'completed' => 'Completed',
                                        'failed' => 'Failed',
                                        'cancelled' => 'Cancelled',
                                        'disputed' => 'Disputed',
                                        'reversed' => 'Reversed',
                                    ])
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Schedule & Processing')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('hold_release_date')
                                    ->label('Hold Release Date')
                                    ->required(),

                                Forms\Components\DateTimePicker::make('processed_at')
                                    ->label('Processed At'),

                                Forms\Components\DateTimePicker::make('completed_at')
                                    ->label('Completed At'),

                                Forms\Components\DateTimePicker::make('failed_at')
                                    ->label('Failed At'),
                            ]),
                    ]),

                Forms\Components\Section::make('Stripe Integration')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('producer_stripe_account_id')
                                    ->label('Producer Stripe Account ID')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('stripe_payment_intent_id')
                                    ->label('Payment Intent ID')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('stripe_transfer_id')
                                    ->label('Transfer ID')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('stripe_reversal_id')
                                    ->label('Reversal ID')
                                    ->maxLength(255),
                            ]),
                    ]),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('failure_reason')
                            ->label('Failure Reason')
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata')
                            ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('producer.name')
                    ->label('Producer')
                    ->sortable()
                    ->searchable()
                    ->url(function (PayoutSchedule $record): ?string {
                        if (!$record->producer) {
                            return null;
                        }
                        
                        try {
                            return UserResource::getUrl('view', ['record' => $record->producer]);
                        } catch (\Exception $e) {
                            // Fallback to edit page if view page is not available
                            return UserResource::getUrl('edit', ['record' => $record->producer]);
                        }
                    }),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->sortable()
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (PayoutSchedule $record): ?string => $record->project?->name),

                Tables\Columns\TextColumn::make('workflow_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'standard' => 'success',
                        'contest' => 'warning',
                        'client_management' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('gross_amount')
                    ->label('Gross')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Net Amount')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        'disputed' => 'warning',
                        'reversed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('hold_release_date')
                    ->label('Release Date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->color(fn (PayoutSchedule $record): string => 
                        $record->hold_release_date->isPast() && $record->status === 'scheduled' ? 'success' : 'gray'
                    ),

                Tables\Columns\IconColumn::make('ready_for_release')
                    ->label('Ready')
                    ->boolean()
                    ->state(fn (PayoutSchedule $record): bool => 
                        $record->status === 'scheduled' && $record->hold_release_date->isPast()
                    )
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                        'disputed' => 'Disputed',
                        'reversed' => 'Reversed',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('workflow_type')
                    ->label('Workflow Type')
                    ->options([
                        'standard' => 'Standard',
                        'contest' => 'Contest',
                        'client_management' => 'Client Management',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('ready_for_release')
                    ->label('Ready for Release')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('status', 'scheduled')
                              ->where('hold_release_date', '<=', now())
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('hold_period')
                    ->label('In Hold Period')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('status', 'scheduled')
                              ->where('hold_release_date', '>', now())
                    )
                    ->toggle(),

                Tables\Filters\SelectFilter::make('producer')
                    ->relationship('producer', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount_from')
                                    ->label('Net Amount From')
                                    ->numeric()
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('amount_to')
                                    ->label('Net Amount To')
                                    ->numeric()
                                    ->prefix('$'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('net_amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('net_amount', '<=', $amount),
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
                
                Action::make('process_immediately')
                    ->label('Process Now')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Process Payout Immediately')
                    ->modalDescription('This will bypass the hold period and process the payout immediately. Are you sure?')
                    ->visible(fn (PayoutSchedule $record): bool => $record->status === 'scheduled')
                    ->action(function (PayoutSchedule $record): void {
                        try {
                            $payoutService = app(PayoutProcessingService::class);
                            
                            // Update hold release date to now to bypass hold
                            $record->update(['hold_release_date' => now()]);
                            
                            // Process the payout
                            $payoutService->processSinglePayout($record);
                            
                            Notification::make()
                                ->title('Payout Processed Successfully')
                                ->success()
                                ->body("Payout of {$record->formatted_net_amount} processed for {$record->producer->name}")
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Payout Processing Failed')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('cancel_payout')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Cancellation Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->visible(fn (PayoutSchedule $record): bool => in_array($record->status, ['scheduled', 'processing']))
                    ->action(function (PayoutSchedule $record, array $data): void {
                        try {
                            $payoutService = app(PayoutProcessingService::class);
                            $payoutService->cancelPayout($record, $data['reason']);
                            
                            Notification::make()
                                ->title('Payout Cancelled')
                                ->success()
                                ->body("Payout cancelled: {$data['reason']}")
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Cancellation Failed')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('view_stripe_transfer')
                    ->label('View in Stripe')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (PayoutSchedule $record): ?string => 
                        $record->stripe_transfer_id 
                            ? "https://dashboard.stripe.com/transfers/{$record->stripe_transfer_id}"
                            : null
                    )
                    ->openUrlInNewTab()
                    ->visible(fn (PayoutSchedule $record): bool => !empty($record->stripe_transfer_id)),

                Action::make('check_stripe_status')
                    ->label('Check Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (PayoutSchedule $record): void {
                        try {
                            $stripeService = app(StripeConnectService::class);
                            $producer = $record->producer;
                            
                            $status = $stripeService->getDetailedAccountStatus($producer);
                            
                            $statusMessage = "Account Status: {$status['status_display']}\n";
                            $statusMessage .= "Can Receive Payouts: " . ($status['can_receive_payouts'] ? 'Yes' : 'No') . "\n";
                            if (!empty($status['next_steps'])) {
                                $statusMessage .= "Next Steps:\n" . implode("\n", $status['next_steps']);
                            }
                            
                            Notification::make()
                                ->title('Stripe Connect Status')
                                ->info()
                                ->body($statusMessage)
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Status Check Failed')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('process_selected')
                        ->label('Process Selected')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Process Selected Payouts')
                        ->modalDescription('This will process all selected scheduled payouts immediately. Are you sure?')
                        ->action(function (Collection $records): void {
                            $processed = 0;
                            $failed = 0;
                            $errors = [];
                            
                            $payoutService = app(PayoutProcessingService::class);
                            
                            foreach ($records as $record) {
                                if ($record->status !== 'scheduled') {
                                    continue;
                                }
                                
                                try {
                                    // Bypass hold period
                                    $record->update(['hold_release_date' => now()]);
                                    
                                    // Process payout
                                    $payoutService->processSinglePayout($record);
                                    $processed++;
                                    
                                } catch (\Exception $e) {
                                    $failed++;
                                    $errors[] = "Payout {$record->id}: " . $e->getMessage();
                                }
                            }
                            
                            $message = "Processed: {$processed}, Failed: {$failed}";
                            if (!empty($errors)) {
                                $message .= "\n\nErrors:\n" . implode("\n", array_slice($errors, 0, 5));
                                if (count($errors) > 5) {
                                    $message .= "\n... and " . (count($errors) - 5) . " more errors";
                                }
                            }
                            
                            Notification::make()
                                ->title('Bulk Processing Complete')
                                ->body($message)
                                ->color($failed > 0 ? 'warning' : 'success')
                                ->send();
                        }),

                    BulkAction::make('cancel_selected')
                        ->label('Cancel Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Cancellation Reason')
                                ->required()
                                ->maxLength(500),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $cancelled = 0;
                            $skipped = 0;
                            
                            $payoutService = app(PayoutProcessingService::class);
                            
                            foreach ($records as $record) {
                                if (!in_array($record->status, ['scheduled', 'processing'])) {
                                    $skipped++;
                                    continue;
                                }
                                
                                try {
                                    $payoutService->cancelPayout($record, $data['reason']);
                                    $cancelled++;
                                } catch (\Exception $e) {
                                    // Log error but continue
                                    \Log::error("Failed to cancel payout {$record->id}: " . $e->getMessage());
                                }
                            }
                            
                            Notification::make()
                                ->title('Bulk Cancellation Complete')
                                ->success()
                                ->body("Cancelled: {$cancelled}, Skipped: {$skipped}")
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Payouts')
                        ->modalDescription('This will permanently delete the selected payout records. This action cannot be undone.')
                        ->color('danger'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s') // Auto-refresh every 30 seconds
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RefundRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayoutSchedules::route('/'),
            'create' => Pages\CreatePayoutSchedule::route('/create'),
            'view' => Pages\ViewPayoutSchedule::route('/{record}'),
            'edit' => Pages\EditPayoutSchedule::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['producer', 'project', 'pitch']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['id', 'producer.name', 'producer.email', 'project.name', 'pitch.title'];
    }

    public static function getNavigationBadge(): ?string
    {
        $ready = static::getModel()::where('status', 'scheduled')
            ->where('hold_release_date', '<=', now())
            ->count();
            
        return $ready > 0 ? (string) $ready : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() ? 'success' : null;
    }
} 