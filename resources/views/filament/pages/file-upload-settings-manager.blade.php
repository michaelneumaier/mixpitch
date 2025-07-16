<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header with effective settings overview -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm dark:shadow-gray-900/10 p-6 ring-1 ring-gray-950/5 dark:ring-white/10">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Current Effective Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($this->getEffectiveSettings() as $context => $settings)
                    <div class="border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-750 rounded-lg p-4 transition-colors">
                        <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-2 capitalize">
                            {{ str_replace('_', ' ', $context) }}
                        </h4>
                        <div class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                            <div class="flex justify-between">
                                <span>Max Size:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-200">{{ $settings['max_file_size_mb'] }}MB</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Chunk Size:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-200">{{ $settings['chunk_size_mb'] }}MB</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Concurrent:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-200">{{ $settings['max_concurrent_uploads'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Retries:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-200">{{ $settings['max_retry_attempts'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Chunking:</span>
                                <span class="font-medium {{ $settings['enable_chunking'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $settings['enable_chunking'] ? 'Yes' : 'No' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span>Timeout:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-200">{{ $settings['session_timeout_hours'] }}h</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Settings Forms -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Global Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm dark:shadow-gray-900/10 ring-1 ring-gray-950/5 dark:ring-white/10 transition-colors">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-blue-500 dark:bg-blue-400 rounded-full"></div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Global Settings (Defaults)</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">These settings apply to all contexts unless overridden.</p>
                </div>
                <div class="p-6">
                    {{ $this->getGlobalForm() }}
                </div>
            </div>

            <!-- Projects Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm dark:shadow-gray-900/10 ring-1 ring-gray-950/5 dark:ring-white/10 transition-colors">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-green-500 dark:bg-green-400 rounded-full"></div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Projects Settings</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Settings specific to project file uploads.</p>
                </div>
                <div class="p-6">
                    {{ $this->getProjectsForm() }}
                </div>
            </div>

            <!-- Pitches Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm dark:shadow-gray-900/10 ring-1 ring-gray-950/5 dark:ring-white/10 transition-colors">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-purple-500 dark:bg-purple-400 rounded-full"></div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Pitches Settings</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Settings specific to pitch file uploads.</p>
                </div>
                <div class="p-6">
                    {{ $this->getPitchesForm() }}
                </div>
            </div>

            <!-- Client Portals Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm dark:shadow-gray-900/10 ring-1 ring-gray-950/5 dark:ring-white/10 transition-colors">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-orange-500 dark:bg-orange-400 rounded-full"></div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Client Portals Settings</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Settings specific to client portal uploads.</p>
                </div>
                <div class="p-6">
                    {{ $this->getClientPortalsForm() }}
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800/30 rounded-lg p-6 transition-colors">
            <div class="flex items-center space-x-2 mb-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <h3 class="text-lg font-medium text-blue-900 dark:text-blue-100">Settings Inheritance</h3>
            </div>
            <div class="text-sm text-blue-800 dark:text-blue-200 space-y-2">
                <div class="flex items-start space-x-2">
                    <div class="w-2 h-2 bg-blue-500 dark:bg-blue-400 rounded-full mt-1.5 flex-shrink-0"></div>
                    <p><strong class="text-blue-900 dark:text-blue-100">Global Settings:</strong> Default values used when no context-specific setting exists.</p>
                </div>
                <div class="flex items-start space-x-2">
                    <div class="w-2 h-2 bg-blue-500 dark:bg-blue-400 rounded-full mt-1.5 flex-shrink-0"></div>
                    <p><strong class="text-blue-900 dark:text-blue-100">Context Settings:</strong> Override global settings for specific upload contexts.</p>
                </div>
                <div class="flex items-start space-x-2">
                    <div class="w-2 h-2 bg-blue-500 dark:bg-blue-400 rounded-full mt-1.5 flex-shrink-0"></div>
                    <p><strong class="text-blue-900 dark:text-blue-100">Empty Fields:</strong> Leave fields empty to inherit from global settings or use system defaults.</p>
                </div>
                <div class="flex items-start space-x-2">
                    <div class="w-2 h-2 bg-blue-500 dark:bg-blue-400 rounded-full mt-1.5 flex-shrink-0"></div>
                    <p><strong class="text-blue-900 dark:text-blue-100">Validation:</strong> All settings are validated before saving to ensure system stability.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Custom dark mode styling for better form experience --}}
    <style>
        /* Smooth transitions for dark mode changes */
        .fi-form-component,
        .fi-input,
        .fi-toggle,
        .fi-btn {
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease !important;
        }

        /* Enhanced dark mode styling for settings cards */
        .dark .settings-card {
            background: linear-gradient(145deg, rgb(31 41 55), rgb(17 24 39));
            border: 1px solid rgb(55 65 81);
        }

        .dark .settings-card:hover {
            border-color: rgb(75 85 99);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
        }

        /* Settings overview cards with better dark mode contrast */
        .dark .overview-card {
            background: rgb(17 24 39);
            border: 1px solid rgb(55 65 81);
        }

        .dark .overview-card:hover {
            background: rgb(31 41 55);
            transform: translateY(-1px);
        }

        /* Form field focus states in dark mode */
        .dark .fi-input:focus {
            border-color: rgb(59 130 246) !important;
            box-shadow: 0 0 0 1px rgb(59 130 246) !important;
        }

        /* Toggle switches in dark mode */
        .dark .fi-toggle input:checked + div {
            background-color: rgb(59 130 246) !important;
        }

        /* Better button styling for dark mode */
        .dark .fi-btn-color-success {
            background: linear-gradient(145deg, rgb(34 197 94), rgb(22 163 74));
        }

        .dark .fi-btn-color-warning {
            background: linear-gradient(145deg, rgb(245 158 11), rgb(217 119 6));
        }

        .dark .fi-btn-color-gray {
            background: linear-gradient(145deg, rgb(75 85 99), rgb(55 65 81));
        }

        /* Help section enhanced styling */
        .help-section {
            background: linear-gradient(145deg, rgb(239 246 255), rgb(219 234 254));
            border: 1px solid rgb(191 219 254);
        }

        .dark .help-section {
            background: linear-gradient(145deg, rgb(30 58 138 / 0.1), rgb(29 78 216 / 0.05));
            border: 1px solid rgb(29 78 216 / 0.3);
        }

        /* Context indicators */
        .context-indicator {
            transition: all 0.2s ease;
        }

        .context-indicator:hover {
            transform: scale(1.1);
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .fi-fo-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    {{-- Add CSS classes to elements for enhanced styling --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add classes to specific elements for enhanced styling
            const settingCards = document.querySelectorAll('[class*="bg-white dark:bg-gray-800"]');
            settingCards.forEach(card => {
                card.classList.add('settings-card');
            });

            const overviewCards = document.querySelectorAll('[class*="border border-gray-200 dark:border-gray-700"]');
            overviewCards.forEach(card => {
                card.classList.add('overview-card');
            });

            const helpSection = document.querySelector('[class*="bg-blue-50 dark:bg-blue-950/20"]');
            if (helpSection) {
                helpSection.classList.add('help-section');
            }

            const contextIndicators = document.querySelectorAll('[class*="w-3 h-3 bg-"]');
            contextIndicators.forEach(indicator => {
                indicator.classList.add('context-indicator');
            });
        });
    </script>
</x-filament-panels::page>