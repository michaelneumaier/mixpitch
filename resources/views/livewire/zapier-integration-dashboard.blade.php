<div>
<div class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-screen p-2">
    <div class="mx-auto space-y-6">
        <!-- Header -->
        <flux:card class="mb-2 bg-white/50 backdrop-blur-lg">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl" class="bg-gradient-to-r from-gray-900 to-purple-800 dark:from-gray-100 dark:to-purple-300 bg-clip-text text-transparent">
                        Zapier Integration
                    </flux:heading>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Automate your client management workflow with Zapier
                    </p>
                </div>
                
                @if($hasApiKey)
                    <div class="flex space-x-3">
                        <flux:button wire:click="refreshStats" variant="ghost" icon="arrow-path" size="sm">
                            Refresh
                        </flux:button>
                        <flux:button wire:click="testApiKey" variant="filled" icon="check-circle" size="sm">
                            Test Connection
                        </flux:button>
                    </div>
                @endif
            </div>
        </flux:card>

        @if(!$isZapierEnabled)
            <!-- Zapier Disabled Notice -->
            <flux:callout icon="exclamation-triangle" color="amber">
                <flux:callout.heading>Zapier Integration Disabled</flux:callout.heading>
                <flux:callout.text>
                    The Zapier integration is currently disabled. Please contact support to enable it.
                </flux:callout.text>
            </flux:callout>
        @elseif(!$hasApiKey)
            <!-- No API Key - Setup -->
            <flux:card class="bg-white/50 backdrop-blur-lg">
                <div class="text-center py-12">
                    <flux:icon.bolt class="mx-auto h-12 w-12 text-gray-400" />
                    <flux:heading size="md" class="mt-4">No Zapier API Key</flux:heading>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                        Generate an API key to start automating your client management workflow with Zapier.
                    </p>
                    <div class="mt-6">
                        <flux:button wire:click="$set('showGenerateKeyModal', true)" 
                                   variant="filled" 
                                   icon="key">
                            Generate API Key
                        </flux:button>
                    </div>
                </div>
            </flux:card>
        @else
            <!-- API Key Management -->
            <flux:card class="bg-white/50 backdrop-blur-lg">
                <div class="flex items-center justify-between border-b border-gray-200/50 dark:border-gray-700/50">
                    <div>
                        <flux:heading size="lg">API Key Management</flux:heading>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Your Zapier API key for client management automation
                        </p>
                    </div>
                    <div class="flex space-x-2">
                        <flux:button wire:click="$set('showUsageReportModal', true)" 
                                   variant="ghost" 
                                   icon="document-chart-bar" 
                                   size="sm">
                            Usage Report
                        </flux:button>
                        <flux:button wire:click="$set('showRevokeKeyModal', true)" 
                                   variant="ghost" 
                                   icon="trash" 
                                   color="red" 
                                   size="sm">
                            Revoke Key
                        </flux:button>
                    </div>
                </div>
                <div class="p-4 lg:p-6 space-y-6">
                    <!-- API Key Display -->
                    <div>
                        <flux:field>
                            <flux:label>API Key</flux:label>
                            <div class="flex rounded-md shadow-sm">
                                <flux:input type="{{ $showApiKey ? 'text' : 'password' }}" 
                                          value="{{ $apiKey ?? '••••••••••••••••••••••••••••••••••••••••' }}" 
                                          readonly 
                                          class="font-mono text-sm flex-1 rounded-r-none" />
                                <flux:button wire:click="toggleApiKeyVisibility" 
                                           variant="ghost" 
                                           icon="{{ $showApiKey ? 'eye-slash' : 'eye' }}" 
                                           size="sm"
                                           class="-ml-px rounded-l-none rounded-r-none border-l-0">
                                </flux:button>
                                @if($apiKey)
                                    <flux:button onclick="copyToClipboard('{{ $apiKey }}')" 
                                               wire:click="copyApiKey"
                                               variant="ghost" 
                                               icon="clipboard" 
                                               size="sm"
                                               class="-ml-px rounded-l-none border-l-0">
                                        Copy
                                    </flux:button>
                                @endif
                            </div>
                            <flux:description>
                                Created {{ $apiKeyCreatedAt ? $apiKeyCreatedAt->format('M j, Y g:i A') : 'recently' }}
                            </flux:description>
                        </flux:field>
                    </div>

                    <!-- Setup Instructions -->
                    <div>
                        <flux:heading size="md" class="mb-3">Setup Instructions</flux:heading>
                        <flux:card class="bg-gray-50/50 dark:bg-gray-800/50">
                            <div class="p-4">
                                <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-300">
                                    <li>Copy your API key above</li>
                                    <li>Go to <a href="https://zapier.com" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 font-medium">Zapier.com</a> and create a new Zap</li>
                                    <li>Search for "MixPitch" and select it as your trigger or action</li>
                                    <li>When prompted, paste your API key to authenticate</li>
                                    <li>Configure your automation workflow</li>
                                </ol>
                            </div>
                        </flux:card>
                    </div>
                </div>
            </flux:card>

            <!-- Usage Statistics -->
            @if($usageStats)
            <flux:card class="bg-white/50 backdrop-blur-lg">
                <div class="border-b border-gray-200/50 dark:border-gray-700/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="lg">Usage Analytics</flux:heading>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Last 30 days of API usage
                            </p>
                        </div>
                        @php $trend = $this->getUsageTrend(); @endphp
                        @if($trend['trend'] !== 'stable')
                            <flux:badge color="{{ $trend['trend'] === 'up' ? 'green' : 'red' }}" size="sm">
                                @if($trend['trend'] === 'up')
                                    ↗ {{ $trend['percentage'] }}%
                                @else
                                    ↘ {{ $trend['percentage'] }}%
                                @endif
                            </flux:badge>
                        @endif
                    </div>
                </div>
                <div class="p-4 lg:p-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <flux:card class="text-center">
                            <flux:heading size="sm" class="text-gray-500 dark:text-gray-400">Total Requests</flux:heading>
                            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($usageStats['total_requests']) }}
                            </div>
                        </flux:card>
                        <flux:card class="text-center">
                            <flux:heading size="sm" class="text-gray-500 dark:text-gray-400">Success Rate</flux:heading>
                            <div class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">
                                {{ number_format(($usageStats['total_requests'] > 0 ? ($usageStats['successful_requests'] / $usageStats['total_requests']) * 100 : 0), 1) }}%
                            </div>
                        </flux:card>
                        <flux:card class="text-center">
                            <flux:heading size="sm" class="text-gray-500 dark:text-gray-400">Avg Response Time</flux:heading>
                            <div class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">
                                {{ number_format($usageStats['average_response_time'], 0) }}ms
                            </div>
                        </flux:card>
                    </div>
                
                    <!-- Most Used Endpoints -->
                    @if(!empty($usageStats['most_used_endpoints']))
                    <div class="mt-8">
                        <flux:heading size="md" class="mb-4">Most Used Endpoints</flux:heading>
                        <div class="space-y-3">
                            @foreach(array_slice($usageStats['most_used_endpoints'], 0, 5) as $endpoint)
                            <div class="flex justify-between items-center p-3 bg-gray-50/50 dark:bg-gray-800/50 rounded-lg">
                                <code class="text-sm text-gray-600 dark:text-gray-300 font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $endpoint['endpoint'] }}</code>
                                <div class="flex items-center space-x-4">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $endpoint['requests'] }} requests</span>
                                    <flux:badge color="{{ $endpoint['success_rate'] >= 95 ? 'green' : 'amber' }}" size="sm">
                                        {{ $endpoint['success_rate'] }}% success
                                    </flux:badge>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </flux:card>
            @endif

            <!-- Rate Limits -->
            @if($quotaStatus)
            <flux:card class="bg-white/50 backdrop-blur-lg">
                <div class="border-b border-gray-200/50 dark:border-gray-700/50">
                    <flux:heading size="lg">Rate Limits & Quotas</flux:heading>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Current usage against your <flux:badge size="sm" color="indigo">{{ ucfirst($quotaStatus['subscription_plan']) }}</flux:badge> plan limits
                    </p>
                </div>
                <div class="space-y-6">
                    @foreach(['per_minute' => 'Per Minute', 'per_hour' => 'Per Hour', 'per_day' => 'Per Day'] as $period => $label)
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <flux:heading size="sm">{{ $label }}</flux:heading>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ number_format($quotaStatus['current_usage'][$period]) }} / {{ number_format($quotaStatus['limits'][$period]) }}
                            </span>
                        </div>
                        
                        <div class="space-y-2">
                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                @php 
                                    $percentage = min(100, $quotaStatus['percentage_used'][$period] ?? 0);
                                    $color = $this->getQuotaStatusColor($period);
                                    $colorClass = match($color) {
                                        'red' => 'bg-red-500',
                                        'yellow' => 'bg-amber-500', 
                                        'blue' => 'bg-blue-500',
                                        'green' => 'bg-green-500',
                                        default => 'bg-gray-400'
                                    };
                                @endphp
                                <div class="h-full {{ $colorClass }} transition-all duration-500 ease-out"
                                     style="width: {{ $percentage }}%"></div>
                            </div>
                            
                            <!-- Usage Details -->
                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                <flux:badge size="sm" color="{{ match($color) { 'red' => 'red', 'yellow' => 'amber', 'green' => 'green', 'blue' => 'blue', default => 'gray' } }}">
                                    {{ number_format($quotaStatus['percentage_used'][$period] ?? 0, 1) }}% used
                                </flux:badge>
                                <span>Resets {{ \Carbon\Carbon::parse($quotaStatus['next_reset'][$period] ?? now())->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    <!-- Warnings -->
                    @if(!empty($quotaStatus['warnings']))
                    <div class="mt-6">
                        <flux:callout icon="exclamation-triangle" color="amber">
                            <flux:callout.heading>Usage Warnings</flux:callout.heading>
                            <flux:callout.text>
                                <div class="space-y-2">
                                    @foreach($quotaStatus['warnings'] as $warning)
                                        <div class="text-sm">
                                            <strong>{{ $warning['message'] }}</strong> - {{ $warning['suggestion'] }}
                                        </div>
                                    @endforeach
                                </div>
                            </flux:callout.text>
                        </flux:callout>
                    </div>
                    @endif
                </div>
            </flux:card>
            @endif

            <!-- Webhook Stats -->
            @if($webhookStats)
            <flux:card class="bg-white/50 backdrop-blur-lg">
                <div class="border-b border-gray-200/50 dark:border-gray-700/50">
                    <flux:heading size="lg">Webhook Statistics</flux:heading>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Real-time webhook usage and performance
                    </p>
                </div>
                <div class="">
                    <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
                        <flux:card class="text-center">
                            <flux:heading size="sm" class="text-gray-500 dark:text-gray-400">Total Webhooks</flux:heading>
                            <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $webhookStats['total_webhooks'] }}
                            </div>
                        </flux:card>
                        <flux:card class="text-center">
                            <flux:heading size="sm" class="text-gray-500 dark:text-gray-400">Active</flux:heading>
                            <div class="mt-2 text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ $webhookStats['active_webhooks'] }}
                            </div>
                        </flux:card>
                        <flux:card class="text-center">
                            <flux:heading size="sm" class="text-gray-500 dark:text-gray-400">Inactive</flux:heading>
                            <div class="mt-2 text-2xl font-bold text-red-600 dark:text-red-400">
                                {{ $webhookStats['inactive_webhooks'] }}
                            </div>
                        </flux:card>
                        <flux:card class="text-center">
                            <flux:heading size="sm" class="text-gray-500 dark:text-gray-400">Total Triggers</flux:heading>
                            <div class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ number_format($webhookStats['total_triggers']) }}
                            </div>
                        </flux:card>
                    </div>
                </div>
            </flux:card>
            @endif
        @endif
    </div>
</div>

<!-- Modals -->
<!-- Generate API Key Modal -->
<flux:modal wire:model="showGenerateKeyModal" class="md:max-w-md">
    <div class="px-6 py-4">
        <flux:heading size="lg">Generate Zapier API Key</flux:heading>
        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
            This will create a new API key for Zapier integration. If you already have a key, it will be replaced.
        </p>
        
        <div class="mt-6 flex justify-end space-x-3">
            <flux:button wire:click="$set('showGenerateKeyModal', false)" variant="ghost">
                Cancel
            </flux:button>
            <flux:button wire:click="generateApiKey" variant="filled">
                Generate Key
            </flux:button>
        </div>
    </div>
</flux:modal>

<!-- Revoke API Key Modal -->
<flux:modal wire:model="showRevokeKeyModal" class="md:max-w-md">
    <div class="px-6 py-4">
        <flux:heading size="lg" class="text-red-600 dark:text-red-400">Revoke Zapier API Key</flux:heading>
        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
            This will permanently revoke your API key and deactivate all webhooks. Your Zapier automations will stop working.
        </p>
        
        <div class="mt-6 flex justify-end space-x-3">
            <flux:button wire:click="$set('showRevokeKeyModal', false)" variant="ghost">
                Cancel
            </flux:button>
            <flux:button wire:click="revokeApiKey" variant="filled" color="red">
                Revoke Key
            </flux:button>
        </div>
    </div>
</flux:modal>

<!-- Usage Report Modal -->
<flux:modal wire:model="showUsageReportModal" class="md:max-w-md">
    <div class="px-6 py-4">
        <flux:heading size="lg">Generate Usage Report</flux:heading>
        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
            Generate a detailed usage report for your Zapier integration.
        </p>
        
        <div class="mt-4">
            <flux:field>
                <flux:label>Report Period</flux:label>
                <flux:select wire:model="usageReportDays">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="60">Last 60 days</option>
                    <option value="90">Last 90 days</option>
                </flux:select>
            </flux:field>
        </div>
        
        <div class="mt-6 flex justify-end space-x-3">
            <flux:button wire:click="$set('showUsageReportModal', false)" variant="ghost">
                Cancel
            </flux:button>
            <flux:button wire:click="generateUsageReport" variant="filled">
                Generate Report
            </flux:button>
        </div>
    </div>
</flux:modal>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Success handled by Livewire
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}

// Listen for download report event
document.addEventListener('livewire:init', () => {
    Livewire.on('download-usage-report', () => {
        // Create download link for usage report
        fetch('/api/zapier-keys/usage-report', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = 'zapier-usage-report.json';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        })
        .catch(error => {
            console.error('Download failed:', error);
        });
    });
});
</script>
</div>