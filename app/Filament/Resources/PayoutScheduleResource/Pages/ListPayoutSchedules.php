<?php

namespace App\Filament\Resources\PayoutScheduleResource\Pages;

use App\Filament\Resources\PayoutScheduleResource;
use App\Models\PayoutSchedule;
use App\Services\PayoutProcessingService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class ListPayoutSchedules extends ListRecords
{
    protected static string $resource = PayoutScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            
            Actions\Action::make('hold_settings')
                ->label('Hold Period Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->url('/admin/payout-hold-settings')
                ->visible(function (): bool {
                    $holdService = app(\App\Services\PayoutHoldService::class);
                    return $holdService->canBypassHold(auth()->user());
                }),
            
            Actions\Action::make('process_ready_payouts')
                ->label('Process Ready Payouts')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Process All Ready Payouts')
                ->modalDescription('This will process all payouts that are past their hold release date. Are you sure?')
                ->action(function (): void {
                    try {
                        $payoutService = app(PayoutProcessingService::class);
                        $results = $payoutService->processScheduledPayouts();
                        
                        $message = "Processed: {$results['processed']}, Failed: {$results['failed']}";
                        if (!empty($results['errors'])) {
                            $message .= "\n\nErrors:\n" . implode("\n", array_slice($results['errors'], 0, 3));
                            if (count($results['errors']) > 3) {
                                $message .= "\n... and " . (count($results['errors']) - 3) . " more errors";
                            }
                        }
                        
                        Notification::make()
                            ->title('Batch Processing Complete')
                            ->body($message)
                            ->color($results['failed'] > 0 ? 'warning' : 'success')
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Batch Processing Failed')
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Actions\Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function (): \Symfony\Component\HttpFoundation\BinaryFileResponse {
                    return response()->download(
                        $this->exportPayoutsToCSV(),
                        'payout-schedules-' . now()->format('Y-m-d') . '.csv'
                    );
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(PayoutSchedule::count()),
                
            'scheduled' => Tab::make('Scheduled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'scheduled'))
                ->badge(PayoutSchedule::where('status', 'scheduled')->count())
                ->badgeColor('warning'),
                
            'ready' => Tab::make('Ready for Release')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('status', 'scheduled')
                          ->where('hold_release_date', '<=', now())
                )
                ->badge(PayoutSchedule::where('status', 'scheduled')
                    ->where('hold_release_date', '<=', now())
                    ->count())
                ->badgeColor('success'),
                
            'processing' => Tab::make('Processing')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'processing'))
                ->badge(PayoutSchedule::where('status', 'processing')->count())
                ->badgeColor('info'),
                
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed'))
                ->badge(PayoutSchedule::where('status', 'completed')->count())
                ->badgeColor('success'),
                
            'failed' => Tab::make('Failed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed'))
                ->badge(PayoutSchedule::where('status', 'failed')->count())
                ->badgeColor('danger'),
                
            'large_amounts' => Tab::make('Large Amounts (>$1000)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('net_amount', '>', 1000))
                ->badge(PayoutSchedule::where('net_amount', '>', 1000)->count())
                ->badgeColor('warning'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\PayoutStatsOverview::class,
        ];
    }

    private function exportPayoutsToCSV(): string
    {
        $payouts = PayoutSchedule::with(['producer', 'project', 'pitch'])->get();
        
        $filename = storage_path('app/temp/payout-schedules-' . uniqid() . '.csv');
        $file = fopen($filename, 'w');
        
        // CSV headers
        fputcsv($file, [
            'ID',
            'Producer Name',
            'Producer Email',
            'Project Name',
            'Pitch Title',
            'Workflow Type',
            'Gross Amount',
            'Commission Rate',
            'Commission Amount',
            'Net Amount',
            'Currency',
            'Status',
            'Hold Release Date',
            'Processed At',
            'Completed At',
            'Stripe Transfer ID',
            'Created At',
        ]);
        
        // CSV data
        foreach ($payouts as $payout) {
            fputcsv($file, [
                $payout->id,
                $payout->producer->name,
                $payout->producer->email,
                $payout->project?->name ?? 'N/A',
                $payout->pitch?->title ?? 'N/A',
                $payout->workflow_type,
                $payout->gross_amount,
                $payout->commission_rate,
                $payout->commission_amount,
                $payout->net_amount,
                $payout->currency,
                $payout->status,
                $payout->hold_release_date?->format('Y-m-d H:i:s'),
                $payout->processed_at?->format('Y-m-d H:i:s'),
                $payout->completed_at?->format('Y-m-d H:i:s'),
                $payout->stripe_transfer_id,
                $payout->created_at->format('Y-m-d H:i:s'),
            ]);
        }
        
        fclose($file);
        return $filename;
    }
} 