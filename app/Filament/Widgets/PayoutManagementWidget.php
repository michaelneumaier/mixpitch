<?php

namespace App\Filament\Widgets;

use App\Models\PayoutSchedule;
use App\Models\StripeTransaction;
use App\Services\PayoutProcessingService;
use Filament\Widgets\Widget;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class PayoutManagementWidget extends Widget
{
    protected static string $view = 'filament.widgets.payout-management';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 15;
    
    public function getViewData(): array
    {
        // Cache the expensive queries for 5 minutes
        $cacheKey = 'payout_management_widget_data';
        
        return Cache::remember($cacheKey, 300, function () {
            $readyPayouts = PayoutSchedule::where('status', 'scheduled')
                ->where('hold_release_date', '<=', now())
                ->with(['producer', 'project'])
                ->orderBy('hold_release_date')
                ->limit(5)
                ->get();
            
            $recentTransactions = StripeTransaction::where('status', 'succeeded')
                ->where('type', 'payment_intent')
                ->whereNull('payout_schedule_id')
                ->with(['user', 'project'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            $failedPayouts = PayoutSchedule::where('status', 'failed')
                ->with(['producer', 'project'])
                ->orderBy('failed_at', 'desc')
                ->limit(3)
                ->get();
            
            $stats = [
                'ready_count' => PayoutSchedule::where('status', 'scheduled')
                    ->where('hold_release_date', '<=', now())
                    ->count(),
                'ready_amount' => PayoutSchedule::where('status', 'scheduled')
                    ->where('hold_release_date', '<=', now())
                    ->sum('net_amount'),
                'pending_count' => PayoutSchedule::where('status', 'scheduled')
                    ->where('hold_release_date', '>', now())
                    ->count(),
                'pending_amount' => PayoutSchedule::where('status', 'scheduled')
                    ->where('hold_release_date', '>', now())
                    ->sum('net_amount'),
                'failed_count' => PayoutSchedule::where('status', 'failed')->count(),
                'processing_count' => PayoutSchedule::where('status', 'processing')->count(),
                'transactions_without_payout' => StripeTransaction::where('status', 'succeeded')
                    ->where('type', 'payment_intent')
                    ->whereNull('payout_schedule_id')
                    ->count(),
            ];
            
            return [
                'readyPayouts' => $readyPayouts,
                'recentTransactions' => $recentTransactions,
                'failedPayouts' => $failedPayouts,
                'stats' => $stats,
            ];
        });
    }
    
    public function processReadyPayouts(): void
    {
        try {
            $payoutService = app(PayoutProcessingService::class);
            $results = $payoutService->processScheduledPayouts();
            
            $message = "Processed: {$results['processed']}, Failed: {$results['failed']}";
            if (!empty($results['errors'])) {
                $message .= "\n\nFirst few errors:\n" . implode("\n", array_slice($results['errors'], 0, 3));
            }
            
            Notification::make()
                ->title('Batch Processing Complete')
                ->body($message)
                ->color($results['failed'] > 0 ? 'warning' : 'success')
                ->send();
                
            // Clear cache to refresh data
            Cache::forget('payout_management_widget_data');
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Batch Processing Failed')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }
    
    public function getPollingInterval(): ?string
    {
        return '60s'; // Refresh every minute
    }
} 