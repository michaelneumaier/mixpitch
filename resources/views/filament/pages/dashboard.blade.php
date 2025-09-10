<x-filament-panels::page>
    {{-- Custom Dashboard Header --}}
    <div class="mb-8">
        <div class="bg-gradient-to-r from-purple-500/10 via-indigo-500/10 to-purple-500/10 rounded-2xl p-6 backdrop-blur-sm border border-purple-200/20 dark:border-purple-700/20">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Platform Overview
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            Real-time insights into your MixPitch platform
                        </p>
                    </div>
                </div>
                <div class="hidden md:flex items-center space-x-3">
                    <div class="text-right">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Last updated</div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ now()->format('M j, Y • g:i A') }}
                        </div>
                    </div>
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions Bar --}}
    <div class="mb-8">
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('filament.admin.resources.projects.create') }}" wire:navigate 
               class="flex items-center px-4 py-2 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-lg hover:from-purple-600 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Project
            </a>
            <a href="{{ route('filament.admin.resources.users.index') }}" wire:navigate 
               class="flex items-center px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                </svg>
                Manage Users
            </a>
            <a href="{{ route('filament.admin.pages.analytics') }}" wire:navigate 
               class="flex items-center px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                View Analytics
            </a>
        </div>
    </div>

    {{-- Header Widgets (Stats Overview) --}}
    @if ($this->hasHeaderWidgets())
        <x-filament-widgets::widgets
            :columns="$this->getHeaderWidgetsColumns()"
            :widgets="$this->getHeaderWidgets()"
            :data="$this->getHeaderWidgetsData()"
        />
    @endif

    {{-- Main Widget Grid --}}
    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :widgets="$this->getWidgets()"
        :data="$this->getWidgetData()"
    />

    {{-- Footer Widgets --}}
    @if ($this->hasFooterWidgets())
        <x-filament-widgets::widgets
            :columns="$this->getFooterWidgetsColumns()"
            :widgets="$this->getFooterWidgets()"
            :data="$this->getFooterWidgetsData()"
        />
    @endif

    {{-- Footer Info --}}
    <div class="mt-12 text-center">
        <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-lg border border-purple-200/50 dark:border-purple-700/50">
            <svg class="w-4 h-4 mr-2 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span class="text-sm font-medium text-purple-700 dark:text-purple-300">
                MixPitch Admin Dashboard • Powered by Filament
            </span>
        </div>
    </div>

    {{-- Custom Styles --}}
    <style>
        .fi-wi-stats-overview {
            @apply grid gap-6;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }
        
        @media (max-width: 640px) {
            .fi-wi-stats-overview {
                grid-template-columns: 1fr;
            }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</x-filament-panels::page>