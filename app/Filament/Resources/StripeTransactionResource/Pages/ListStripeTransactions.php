<?php

namespace App\Filament\Resources\StripeTransactionResource\Pages;

use App\Filament\Resources\StripeTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListStripeTransactions extends ListRecords
{
    protected static string $resource = StripeTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
                
            'succeeded' => Tab::make('Succeeded')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'succeeded'))
                ->badge(fn () => static::getModel()::where('status', 'succeeded')->count())
                ->badgeColor('success'),
                
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => static::getModel()::where('status', 'pending')->count())
                ->badgeColor('warning'),
                
            'failed' => Tab::make('Failed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed'))
                ->badge(fn () => static::getModel()::where('status', 'failed')->count())
                ->badgeColor('danger'),
                
            'no_payout' => Tab::make('No Payout Schedule')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('status', 'succeeded')
                          ->where('type', 'payment_intent')
                          ->whereNull('payout_schedule_id')
                )
                ->badge(fn () => static::getModel()::where('status', 'succeeded')
                    ->where('type', 'payment_intent')
                    ->whereNull('payout_schedule_id')
                    ->count())
                ->badgeColor('warning'),
        ];
    }
} 