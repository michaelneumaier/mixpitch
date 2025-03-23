<?php

namespace App\Filament\Plugins\Billing\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Cashier\Cashier;
use Carbon\Carbon;

class UserBillingStatusWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stripe_id')
                    ->label('Customer ID')
                    ->formatStateUsing(fn ($state) => $state ? $state : 'Not created')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->getStateUsing(function (User $record): string {
                        if (!$record->stripe_id) return 'None';
                        
                        try {
                            $stripe = Cashier::stripe();
                            $methods = $stripe->paymentMethods->all([
                                'customer' => $record->stripe_id,
                                'type' => 'card',
                            ]);
                            
                            if (count($methods->data) === 0) return 'None';
                            
                            $method = $methods->data[0];
                            return ucfirst($method->card->brand) . ' •••• ' . $method->card->last4;
                        } catch (\Exception $e) {
                            return 'Error fetching';
                        }
                    }),
                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->getStateUsing(function (User $record): string {
                        if (!$record->stripe_id) return '$0.00';
                        
                        try {
                            $stripe = Cashier::stripe();
                            $charges = $stripe->charges->all([
                                'customer' => $record->stripe_id,
                                'status' => 'succeeded',
                                'limit' => 100,
                            ]);
                            
                            $total = 0;
                            foreach ($charges->data as $charge) {
                                $total += $charge->amount;
                            }
                            
                            return '$' . number_format($total / 100, 2);
                        } catch (\Exception $e) {
                            return 'Error fetching';
                        }
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_payment')
                    ->label('Last Payment')
                    ->getStateUsing(function (User $record): string {
                        if (!$record->stripe_id) return 'Never';
                        
                        try {
                            $stripe = Cashier::stripe();
                            $charges = $stripe->charges->all([
                                'customer' => $record->stripe_id,
                                'status' => 'succeeded',
                                'limit' => 1,
                            ]);
                            
                            if (count($charges->data) === 0) return 'Never';
                            
                            $lastCharge = $charges->data[0];
                            return Carbon::createFromTimestamp($lastCharge->created)->diffForHumans();
                        } catch (\Exception $e) {
                            return 'Error fetching';
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_invoices')
                    ->label('Invoices')
                    ->url(fn (User $record): string => route('filament.admin.resources.users.invoices', $record))
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->visible(fn (User $record): bool => $record->stripe_id !== null),
                Tables\Actions\Action::make('make_payment')
                    ->label('Process Payment')
                    ->url(fn (User $record): string => route('filament.admin.resources.users.payment', $record))
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->stripe_id !== null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'has_payment' => 'Has Payment Method',
                        'no_payment' => 'No Payment Method',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'has_payment') {
                            $query->whereNotNull('stripe_id');
                        } elseif ($data['value'] === 'no_payment') {
                            $query->whereNull('stripe_id');
                        }
                    }),
            ])
            ->heading('User Billing Status')
            ->emptyStateHeading('No Users Found')
            ->emptyStateDescription('No users matching your filters have been found.');
    }
} 