<?php

namespace App\Filament\Widgets;

use App\Models\EmailEvent;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class EmailActivityChart extends ChartWidget
{
    protected static ?string $heading = 'Email Activity (Last 30 Days)';
    
    protected static ?string $pollingInterval = '300s';
    
    protected static ?string $maxHeight = '400px';
    
    protected int $daysToShow = 30;
    
    public function getDescription(): ?string
    {
        return 'Email activity broken down by event type';
    }

    protected function getData(): array
    {
        $days = $this->daysToShow;
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subDays($days - 1);
        
        // Create date range array for labels
        $dateRange = collect();
        for ($i = 0; $i < $days; $i++) {
            $dateRange->push($startDate->copy()->addDays($i)->format('M d'));
        }
        
        // Get event counts by type and date
        $eventTypes = ['sent', 'delivered', 'opened', 'clicked', 'bounced', 'complained'];
        $datasets = [];
        
        $colors = [
            'sent' => '75, 85, 99', // Gray
            'delivered' => '52, 211, 153', // Green
            'opened' => '59, 130, 246', // Blue
            'clicked' => '249, 115, 22', // Orange
            'bounced' => '239, 68, 68', // Red
            'complained' => '217, 70, 239', // Purple
        ];
        
        foreach ($eventTypes as $type) {
            $data = EmailEvent::where('event_type', $type)
                ->where('created_at', '>=', $startDate->format('Y-m-d'))
                ->where('created_at', '<=', $endDate->format('Y-m-d'))
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date')
                ->toArray();
            
            // Fill in missing dates with zeros
            $countsArray = [];
            for ($i = 0; $i < $days; $i++) {
                $currentDate = $startDate->copy()->addDays($i)->format('Y-m-d');
                $countsArray[] = $data[$currentDate] ?? 0;
            }
            
            $datasets[] = [
                'label' => ucfirst($type),
                'data' => $countsArray,
                'backgroundColor' => "rgba({$colors[$type]}, 0.7)",
                'borderColor' => "rgba({$colors[$type]}, 1)",
                'borderWidth' => 1,
            ];
        }
        
        return [
            'labels' => $dateRange->toArray(),
            'datasets' => $datasets,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
    
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
