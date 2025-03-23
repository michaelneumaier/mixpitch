<x-filament-panels::page>
    <x-filament::section>
        <div class="space-y-6">
            <div class="prose max-w-none dark:prose-invert">
                <h2>{{ __('Billing Administration') }}</h2>
                <p>{{ __('Comprehensive overview of all billing activities, user payment statuses, and revenue metrics.') }}</p>
            </div>
            
            @php
                $stats = \App\Filament\Plugins\Billing\Widgets\RevenueOverviewWidget::getOverviewStats();
            @endphp
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-gray-500 text-sm font-medium">Total Revenue</h3>
                        <div class="bg-blue-100 text-blue-800 p-1 rounded">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-baseline mt-2">
                        <div class="text-2xl font-semibold" id="revenue-value">${{ $stats['total_revenue'] }}</div>
                        <div class="ml-2 {{ $stats['revenue_change_positive'] ? 'text-green-500' : 'text-red-500' }} text-xs font-medium" id="revenue-change">
                            <span>{{ $stats['revenue_change_positive'] ? '+' : '' }}{{ $stats['revenue_change'] }}%</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-gray-500 text-sm font-medium">Active Customers</h3>
                        <div class="bg-green-100 text-green-800 p-1 rounded">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-baseline mt-2">
                        <div class="text-2xl font-semibold" id="customers-value">{{ $stats['customers_count'] }}</div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-gray-500 text-sm font-medium">Pending Payments</h3>
                        <div class="bg-yellow-100 text-yellow-800 p-1 rounded">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-baseline mt-2">
                        <div class="text-2xl font-semibold" id="pending-value">${{ $stats['pending_amount'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>

    @push('scripts')
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // This will be fired when the Livewire component is updated
                document.addEventListener('livewire:navigated', function() {
                    fetchStats();
                });
                
                // Fetch stats every 5 minutes
                setInterval(fetchStats, 5 * 60 * 1000);
                
                function fetchStats() {
                    fetch('/admin/billing/stats')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update revenue
                                document.getElementById('revenue-value').innerText = '$' + data.stats.total_revenue;
                                const revenueChange = document.getElementById('revenue-change');
                                revenueChange.innerHTML = `<span>${data.stats.revenue_change_positive ? '+' : ''}${data.stats.revenue_change}%</span>`;
                                revenueChange.className = `ml-2 ${data.stats.revenue_change_positive ? 'text-green-500' : 'text-red-500'} text-xs font-medium`;
                                
                                // Update customers
                                document.getElementById('customers-value').innerText = data.stats.customers_count;
                                
                                // Update pending
                                document.getElementById('pending-value').innerText = '$' + data.stats.pending_amount;
                            }
                        })
                        .catch(error => console.error('Error fetching stats:', error));
                }
            });
        </script>
    @endpush
</x-filament-panels::page> 