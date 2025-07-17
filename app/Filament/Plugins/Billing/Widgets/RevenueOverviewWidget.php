<?php

namespace App\Filament\Plugins\Billing\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Laravel\Cashier\Cashier;

class RevenueOverviewWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue Overview';

    protected static ?int $sort = 1;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $stripe = Cashier::stripe();
        $months = collect(range(0, 5))->map(function ($months_ago) use ($stripe) {
            $month = Carbon::now()->subMonths($months_ago);
            $start = $month->startOfMonth()->timestamp;
            $end = $month->endOfMonth()->timestamp;

            // Get successful payments for the month
            $payments = $stripe->charges->all([
                'created' => [
                    'gte' => $start,
                    'lte' => $end,
                ],
                'status' => 'succeeded',
                'limit' => 100,
            ]);

            // Calculate total amount
            $total = collect($payments->data)->sum('amount') / 100; // Convert from cents to dollars

            return [
                'month' => $month->format('M Y'),
                'revenue' => $total,
            ];
        })->reverse();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $months->pluck('revenue')->toArray(),
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $months->pluck('month')->toArray(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "$" + value; }',
                    ],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return "$" + context.parsed.y; }',
                    ],
                ],
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }

    public static function getOverviewStats(): array
    {
        $stripe = Cashier::stripe();

        // Current month's revenue
        $current_month_start = Carbon::now()->startOfMonth()->timestamp;
        $current_payments = $stripe->charges->all([
            'created' => [
                'gte' => $current_month_start,
            ],
            'status' => 'succeeded',
            'limit' => 100,
        ]);
        $current_month_revenue = collect($current_payments->data)->sum('amount') / 100;

        // Previous month's revenue for comparison
        $prev_month_start = Carbon::now()->subMonth()->startOfMonth()->timestamp;
        $prev_month_end = Carbon::now()->subMonth()->endOfMonth()->timestamp;
        $prev_payments = $stripe->charges->all([
            'created' => [
                'gte' => $prev_month_start,
                'lte' => $prev_month_end,
            ],
            'status' => 'succeeded',
            'limit' => 100,
        ]);
        $prev_month_revenue = collect($prev_payments->data)->sum('amount') / 100;

        // Calculate percentage change
        $percentage_change = 0;
        if ($prev_month_revenue > 0) {
            $percentage_change = (($current_month_revenue - $prev_month_revenue) / $prev_month_revenue) * 100;
        }

        // Get active customers (users with stripe_id)
        $customers_count = User::whereNotNull('stripe_id')->count();

        // Get pending payments (invoices that are open)
        $pending_invoices = $stripe->invoices->all([
            'status' => 'open',
            'limit' => 100,
        ]);
        $pending_amount = collect($pending_invoices->data)->sum('amount_due') / 100;

        return [
            'total_revenue' => number_format($current_month_revenue, 2),
            'revenue_change' => number_format($percentage_change, 1),
            'revenue_change_positive' => $percentage_change >= 0,
            'customers_count' => $customers_count,
            'pending_amount' => number_format($pending_amount, 2),
        ];
    }
}
