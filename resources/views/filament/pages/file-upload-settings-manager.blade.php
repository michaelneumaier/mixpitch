<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header with effective settings overview -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Current Effective Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($this->getEffectiveSettings() as $context => $settings)
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium text-sm text-gray-700 mb-2 capitalize">
                            {{ str_replace('_', ' ', $context) }}
                        </h4>
                        <div class="space-y-1 text-xs text-gray-600">
                            <div>Max Size: {{ $settings['max_file_size_mb'] }}MB</div>
                            <div>Chunk Size: {{ $settings['chunk_size_mb'] }}MB</div>
                            <div>Concurrent: {{ $settings['max_concurrent_uploads'] }}</div>
                            <div>Retries: {{ $settings['max_retry_attempts'] }}</div>
                            <div>Chunking: {{ $settings['enable_chunking'] ? 'Yes' : 'No' }}</div>
                            <div>Timeout: {{ $settings['session_timeout_hours'] }}h</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Settings Forms -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Global Settings -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Global Settings (Defaults)</h3>
                    <p class="text-sm text-gray-600 mt-1">These settings apply to all contexts unless overridden.</p>
                </div>
                <div class="p-6">
                    {{ $this->getGlobalForm() }}
                </div>
            </div>

            <!-- Projects Settings -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Projects Settings</h3>
                    <p class="text-sm text-gray-600 mt-1">Settings specific to project file uploads.</p>
                </div>
                <div class="p-6">
                    {{ $this->getProjectsForm() }}
                </div>
            </div>

            <!-- Pitches Settings -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Pitches Settings</h3>
                    <p class="text-sm text-gray-600 mt-1">Settings specific to pitch file uploads.</p>
                </div>
                <div class="p-6">
                    {{ $this->getPitchesForm() }}
                </div>
            </div>

            <!-- Client Portals Settings -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Client Portals Settings</h3>
                    <p class="text-sm text-gray-600 mt-1">Settings specific to client portal uploads.</p>
                </div>
                <div class="p-6">
                    {{ $this->getClientPortalsForm() }}
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="bg-blue-50 rounded-lg p-6">
            <h3 class="text-lg font-medium text-blue-900 mb-2">Settings Inheritance</h3>
            <div class="text-sm text-blue-800 space-y-2">
                <p><strong>Global Settings:</strong> Default values used when no context-specific setting exists.</p>
                <p><strong>Context Settings:</strong> Override global settings for specific upload contexts.</p>
                <p><strong>Empty Fields:</strong> Leave fields empty to inherit from global settings or use system defaults.</p>
                <p><strong>Validation:</strong> All settings are validated before saving to ensure system stability.</p>
            </div>
        </div>
    </div>
</x-filament-panels::page>