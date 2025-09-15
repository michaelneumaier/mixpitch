<x-layouts.app-sidebar>
    <div class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-screen p-2">
        <div class="mx-auto space-y-6">
            <!-- Header -->
            <flux:card class="mb-2 bg-white/50 backdrop-blur-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="xl" class="bg-gradient-to-r from-gray-900 to-purple-800 dark:from-gray-100 dark:to-purple-300 bg-clip-text text-transparent">
                            Integrations
                        </flux:heading>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Connect MixPitch with your favorite tools and services to streamline your workflow.
                        </p>
                    </div>
                </div>
            </flux:card>

            <!-- Integrations Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($integrations as $key => $integration)
                    <flux:card class="bg-white/50 backdrop-blur-lg hover:shadow-lg transition-all duration-200">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    @if($integration['icon'] === 'zapier')
                                        <div class="flex-shrink-0 w-12 h-12 bg-orange-100 dark:bg-orange-900/20 rounded-lg flex items-center justify-center">
                                            <svg class="w-7 h-7 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2L2 7v10l10 5 10-5V7l-10-5zm0 2.18L19.82 9 12 13.82 4.18 9 12 4.18zm-8 5.82L11 16V20l-7-3.5V10zm16 0V16.5L13 20V16l7-8z"/>
                                            </svg>
                                        </div>
                                    @elseif($integration['icon'] === 'google-drive')
                                        <div class="flex-shrink-0 w-12 h-12 bg-white dark:bg-gray-800 rounded-lg flex items-center justify-center border border-gray-200 dark:border-gray-700">
                                            <svg class="w-7 h-7" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                            </svg>
                                        </div>
                                    @endif
                                    
                                    <div>
                                        <flux:heading size="md" class="text-gray-900 dark:text-gray-100">
                                            {{ $integration['name'] }}
                                        </flux:heading>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $integration['description'] }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex-shrink-0">
                                    @if($integration['status']['connected'])
                                        <flux:badge color="green" size="sm">
                                            <div class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1.5"></div>
                                            Connected
                                        </flux:badge>
                                    @elseif($integration['status']['needs_reauth'] ?? false)
                                        <flux:badge color="amber" size="sm">
                                            <div class="w-1.5 h-1.5 bg-amber-400 rounded-full mr-1.5"></div>
                                            Reconnect Required
                                        </flux:badge>
                                    @else
                                        <flux:badge color="gray" size="sm">
                                            <div class="w-1.5 h-1.5 bg-gray-400 rounded-full mr-1.5"></div>
                                            Not Connected
                                        </flux:badge>
                                    @endif
                                </div>
                            </div>

                            @if($integration['status']['connected'] && $integration['status']['stats'])
                                <div class="mt-4 pt-4 border-t border-gray-200/50 dark:border-gray-700/50">
                                    <div class="space-y-3">
                                        @if($key === 'zapier')
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-500 dark:text-gray-400">Webhooks received:</span>
                                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($integration['status']['stats']['webhooks_received']) }}</span>
                                            </div>
                                            @if($integration['status']['stats']['last_activity'])
                                                <div class="flex justify-between text-sm">
                                                    <span class="text-gray-500 dark:text-gray-400">Last activity:</span>
                                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($integration['status']['stats']['last_activity'])->diffForHumans() }}</span>
                                                </div>
                                            @endif
                                        @elseif($key === 'google_drive')
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-500 dark:text-gray-400">Files backed up:</span>
                                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($integration['status']['stats']['successful_backups']) }}</span>
                                            </div>
                                            @if($integration['status']['stats']['total_size_backed_up'] > 0)
                                                <div class="flex justify-between text-sm">
                                                    <span class="text-gray-500 dark:text-gray-400">Total size:</span>
                                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($integration['status']['stats']['total_size_backed_up'] / 1024 / 1024, 1) }} MB</span>
                                                </div>
                                            @endif
                                            @if($integration['status']['stats']['latest_backup'])
                                                <div class="flex justify-between text-sm">
                                                    <span class="text-gray-500 dark:text-gray-400">Last backup:</span>
                                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($integration['status']['stats']['latest_backup'])->diffForHumans() }}</span>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if($integration['status']['connected_at'])
                                <div class="mt-4 pt-4 border-t border-gray-200/50 dark:border-gray-700/50">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Connected {{ \Carbon\Carbon::parse($integration['status']['connected_at'])->diffForHumans() }}
                                    </p>
                                </div>
                            @endif

                            <div class="mt-6">
                                @if($integration['status']['connected'] && !($integration['status']['needs_reauth'] ?? false))
                                    <flux:button href="{{ route($integration['route']) }}" 
                                               variant="filled" 
                                               class="w-full">
                                        Manage
                                    </flux:button>
                                @else
                                    <flux:button href="{{ route($integration['setup_route']) }}" 
                                               variant="filled" 
                                               color="indigo"
                                               class="w-full">
                                        @if($integration['status']['needs_reauth'] ?? false)
                                            Reconnect
                                        @else
                                            Connect
                                        @endif
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </flux:card>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.app-sidebar>