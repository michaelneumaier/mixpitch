<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-banknotes class="w-5 h-5 text-primary-500" />
                Payout Management
            </div>
        </x-slot>
        
        <x-slot name="headerEnd">
            <div class="flex gap-2">
                @if($stats['ready_count'] > 0)
                    <x-filament::button 
                        wire:click="processReadyPayouts"
                        color="success" 
                        size="sm"
                        icon="heroicon-o-play"
                    >
                        Process {{ $stats['ready_count'] }} Ready Payouts
                    </x-filament::button>
                @endif
                
                <x-filament::button 
                    href="{{ \App\Filament\Resources\PayoutScheduleResource::getUrl('index') }}"
                    color="primary" 
                    size="sm"
                    icon="heroicon-o-eye"
                    outlined
                >
                    View All
                </x-filament::button>
            </div>
        </x-slot>

        <!-- Stats Overview -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-success-50 dark:bg-success-900/20 p-3 rounded-lg border border-success-200 dark:border-success-800">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-success-600" />
                    <div>
                        <div class="text-sm text-success-600 font-medium">Ready for Release</div>
                        <div class="text-lg font-bold text-success-900 dark:text-success-100">
                            {{ $stats['ready_count'] }} payouts
                        </div>
                        <div class="text-xs text-success-700 dark:text-success-300">
                            ${{ number_format($stats['ready_amount'], 2) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-warning-50 dark:bg-warning-900/20 p-3 rounded-lg border border-warning-200 dark:border-warning-800">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-clock class="w-5 h-5 text-warning-600" />
                    <div>
                        <div class="text-sm text-warning-600 font-medium">In Hold Period</div>
                        <div class="text-lg font-bold text-warning-900 dark:text-warning-100">
                            {{ $stats['pending_count'] }} payouts
                        </div>
                        <div class="text-xs text-warning-700 dark:text-warning-300">
                            ${{ number_format($stats['pending_amount'], 2) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-info-50 dark:bg-info-900/20 p-3 rounded-lg border border-info-200 dark:border-info-800">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-arrow-path class="w-5 h-5 text-info-600" />
                    <div>
                        <div class="text-sm text-info-600 font-medium">Processing</div>
                        <div class="text-lg font-bold text-info-900 dark:text-info-100">
                            {{ $stats['processing_count'] }} payouts
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-danger-50 dark:bg-danger-900/20 p-3 rounded-lg border border-danger-200 dark:border-danger-800">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-danger-600" />
                    <div>
                        <div class="text-sm text-danger-600 font-medium">Failed</div>
                        <div class="text-lg font-bold text-danger-900 dark:text-danger-100">
                            {{ $stats['failed_count'] }} payouts
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Ready Payouts -->
            <div class="lg:col-span-1">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <x-heroicon-o-check-circle class="w-4 h-4 text-success-500" />
                    Ready for Release
                    @if($stats['ready_count'] > 0)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200">
                            {{ $stats['ready_count'] }}
                        </span>
                    @endif
                </h3>
                
                <div class="space-y-2">
                    @forelse($readyPayouts as $payout)
                        <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-success-300 dark:hover:border-success-600 transition-colors">
                            <div class="flex justify-between items-start">
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-gray-900 dark:text-gray-100 truncate">
                                        {{ $payout->producer->name }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                        {{ $payout->project?->name ?? 'No Project' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-500">
                                        Due: {{ $payout->hold_release_date->format('M j, Y g:i A') }}
                                    </div>
                                </div>
                                <div class="text-right ml-2">
                                    <div class="font-bold text-success-600 dark:text-success-400">
                                        ${{ number_format($payout->net_amount, 2) }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ ucfirst($payout->workflow_type) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-check-circle class="w-8 h-8 mx-auto mb-2 text-gray-300" />
                            <div class="text-sm">No payouts ready for release</div>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Recent Transactions Without Payouts -->
            <div class="lg:col-span-1">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <x-heroicon-o-credit-card class="w-4 h-4 text-info-500" />
                    Recent Transactions
                    @if($stats['transactions_without_payout'] > 0)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-info-100 text-info-800 dark:bg-info-900 dark:text-info-200">
                            {{ $stats['transactions_without_payout'] }} need payouts
                        </span>
                    @endif
                </h3>
                
                <div class="space-y-2">
                    @forelse($recentTransactions as $transaction)
                        <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-info-300 dark:hover:border-info-600 transition-colors">
                            <div class="flex justify-between items-start">
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-gray-900 dark:text-gray-100 truncate">
                                        {{ $transaction->user->name ?? 'Unknown User' }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                        {{ $transaction->project?->name ?? 'No Project' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-500">
                                        {{ $transaction->created_at->format('M j, Y g:i A') }}
                                    </div>
                                </div>
                                <div class="text-right ml-2">
                                    <div class="font-bold text-info-600 dark:text-info-400">
                                        ${{ number_format($transaction->amount, 2) }}
                                    </div>
                                    <div class="text-xs bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200 px-2 py-1 rounded-full">
                                        No Payout
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-credit-card class="w-8 h-8 mx-auto mb-2 text-gray-300" />
                            <div class="text-sm">No recent transactions</div>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Failed Payouts -->
            <div class="lg:col-span-1">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-danger-500" />
                    Failed Payouts
                    @if($stats['failed_count'] > 0)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-200">
                            {{ $stats['failed_count'] }}
                        </span>
                    @endif
                </h3>
                
                <div class="space-y-2">
                    @forelse($failedPayouts as $payout)
                        <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-danger-300 dark:hover:border-danger-600 transition-colors">
                            <div class="flex justify-between items-start">
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-gray-900 dark:text-gray-100 truncate">
                                        {{ $payout->producer->name }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                        {{ $payout->project?->name ?? 'No Project' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-500">
                                        Failed: {{ $payout->failed_at?->format('M j, Y g:i A') }}
                                    </div>
                                    @if($payout->failure_reason)
                                        <div class="text-xs text-danger-600 dark:text-danger-400 mt-1 truncate">
                                            {{ $payout->failure_reason }}
                                        </div>
                                    @endif
                                </div>
                                <div class="text-right ml-2">
                                    <div class="font-bold text-danger-600 dark:text-danger-400">
                                        ${{ number_format($payout->net_amount, 2) }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ ucfirst($payout->workflow_type) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-check-circle class="w-8 h-8 mx-auto mb-2 text-gray-300" />
                            <div class="text-sm">No failed payouts</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap gap-2">
                <x-filament::button 
                    href="{{ \App\Filament\Resources\PayoutScheduleResource::getUrl('index', ['tableFilters[ready_for_release][isActive]' => '1']) }}"
                    color="success" 
                    size="sm"
                    icon="heroicon-o-play"
                    outlined
                >
                    View Ready Payouts
                </x-filament::button>
                
                <x-filament::button 
                    href="{{ \App\Filament\Resources\PayoutScheduleResource::getUrl('index', ['tableFilters[status][values][0]' => 'failed']) }}"
                    color="danger" 
                    size="sm"
                    icon="heroicon-o-exclamation-triangle"
                    outlined
                >
                    View Failed Payouts
                </x-filament::button>
                
                <x-filament::button 
                    href="{{ \App\Filament\Resources\StripeTransactionResource::getUrl('index', ['tableFilters[has_payout][isActive]' => '0']) }}"
                    color="warning" 
                    size="sm"
                    icon="heroicon-o-credit-card"
                    outlined
                >
                    Transactions Without Payouts
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget> 