<?php

namespace App\Filament\Widgets;

use App\Models\EmailAudit;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class EmailActivityChart extends ChartWidget
{
    protected static ?string $heading = 'Email Activity';
    
    protected static ?string $pollingInterval = '60s';
    
    protected static ?string $maxHeight = '300px';
    
    protected static ?string $navigationGroup = 'Email Management';
    
    public function getDescription(): ?string
    {
        return 'Email activity broken down by event type';
    }

    protected function getData(): array
    {
        // Get the last 14 days of data
        $startDate = now()->subDays(14)->startOfDay();
        $endDate = now()->endOfDay();
        
        // Initialize arrays with dates
        $labels = [];
        $sentData = [];
        $bouncedData = [];
        $suppressedData = [];
        $failedData = [];
        
        $current = clone $startDate;
        while ($current <= $endDate) {
            $labels[] = $current->format('M d');
            
            // For each day, initialize the data points with 0
            $sentData[$current->format('Y-m-d')] = 0;
            $bouncedData[$current->format('Y-m-d')] = 0;
            $suppressedData[$current->format('Y-m-d')] = 0;
            $failedData[$current->format('Y-m-d')] = 0;
            
            $current->addDay();
        }
        
        // Get the email data
        $emailData = EmailAudit::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, status, COUNT(*) as count')
            ->groupBy('date', 'status')
            ->get();
        
        // Populate the data arrays
        foreach ($emailData as $data) {
            $date = $data->date;
            
            switch ($data->status) {
                case 'sent':
                    $sentData[$date] = $data->count;
                    break;
                case 'bounced':
                    $bouncedData[$date] = $data->count;
                    break;
                case 'suppressed':
                    $suppressedData[$date] = $data->count;
                    break;
                case 'failed':
                    $failedData[$date] = $data->count;
                    break;
            }
        }
        
        // Convert data arrays to sequential arrays (removing keys)
        $sent = array_values($sentData);
        $bounced = array_values($bouncedData);
        $suppressed = array_values($suppressedData);
        $failed = array_values($failedData);
        
        return [
            'datasets' => [
                [
                    'label' => 'Sent',
                    'data' => $sent,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => 'start',
                ],
                [
                    'label' => 'Bounced',
                    'data' => $bounced,
                    'backgroundColor' => 'rgba(220, 38, 38, 0.2)',
                    'borderColor' => 'rgb(220, 38, 38)',
                    'fill' => 'start',
                ],
                [
                    'label' => 'Suppressed',
                    'data' => $suppressed,
                    'backgroundColor' => 'rgba(234, 179, 8, 0.2)',
                    'borderColor' => 'rgb(234, 179, 8)',
                    'fill' => 'start',
                ],
                [
                    'label' => 'Failed',
                    'data' => $failed,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.2)',
                    'borderColor' => 'rgb(99, 102, 241)',
                    'fill' => 'start',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
            'elements' => [
                'line' => [
                    'tension' => 0.3, // Slightly curved lines
                ],
                'point' => [
                    'radius' => 3,
                    'hitRadius' => 10,
                    'hoverRadius' => 5,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
