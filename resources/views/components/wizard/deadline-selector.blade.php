@props(['deadline' => null, 'workflowType' => 'standard'])

<div class="space-y-6">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-4">
            Project Deadline
            @if($workflowType !== \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                <span class="text-gray-500">(Optional)</span>
            @endif
        </label>
        
        <!-- Deadline Options -->
        <div x-data="{ 
            selectedPreset: '',
            customDate: '{{ $deadline }}',
            
            init() {
                // Set initial preset based on current deadline
                this.selectedPreset = this.customDate ? 'custom' : 'none';
            },
            
            setPreset(preset) {
                // Prevent double-triggering by checking if already selected
                if (this.selectedPreset === preset && preset !== 'custom') {
                    return;
                }
                
                this.selectedPreset = preset;
                const today = new Date();
                
                switch(preset) {
                    case 'week':
                        const nextWeek = new Date(today);
                        nextWeek.setDate(today.getDate() + 7);
                        this.customDate = nextWeek.toISOString().split('T')[0];
                        $wire.set('form.deadline', this.customDate);
                        break;
                    case 'month':
                        const nextMonth = new Date(today);
                        nextMonth.setMonth(today.getMonth() + 1);
                        this.customDate = nextMonth.toISOString().split('T')[0];
                        $wire.set('form.deadline', this.customDate);
                        break;
                    case '3months':
                        const next3Months = new Date(today);
                        next3Months.setMonth(today.getMonth() + 3);
                        this.customDate = next3Months.toISOString().split('T')[0];
                        $wire.set('form.deadline', this.customDate);
                        break;
                    case 'custom':
                        // Keep current date, just switch to custom mode
                        // Don't update the wire model here to avoid conflicts
                        break;
                    case 'none':
                        this.customDate = '';
                        $wire.set('form.deadline', '');
                        break;
                }
            },
            
            formatDate(dateString) {
                if (!dateString) return '';
                // Parse the date string as local date to avoid timezone issues
                const parts = dateString.split('-');
                const date = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                return date.toLocaleDateString('en-US', { 
                    weekday: 'short',
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric' 
                });
            },
            
            updateCustomDate() {
                // Only update if we're in custom mode or no preset is selected
                if (this.selectedPreset === '' || this.selectedPreset === 'custom') {
                    this.selectedPreset = 'custom';
                    $wire.set('form.deadline', this.customDate);
                }
            }
        }">
            
            <!-- Quick Preset Options -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                <!-- No Deadline -->
                <button type="button" @click="setPreset('none')"
                        class="p-3 text-center border-2 rounded-lg transition-all duration-200 hover:shadow-md"
                        :class="selectedPreset === 'none' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 hover:border-gray-300'">
                    <div class="text-lg mb-1">🕐</div>
                    <div class="text-sm font-medium">No Rush</div>
                    <div class="text-xs text-gray-500">Flexible timing</div>
                </button>
                
                <!-- 1 Week -->
                <button type="button" @click="setPreset('week')"
                        class="p-3 text-center border-2 rounded-lg transition-all duration-200 hover:shadow-md"
                        :class="selectedPreset === 'week' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 hover:border-gray-300'">
                    <div class="text-lg mb-1">⚡</div>
                    <div class="text-sm font-medium">1 Week</div>
                    <div class="text-xs text-gray-500">Quick turnaround</div>
                </button>
                
                <!-- 1 Month -->
                <button type="button" @click="setPreset('month')"
                        class="p-3 text-center border-2 rounded-lg transition-all duration-200 hover:shadow-md"
                        :class="selectedPreset === 'month' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 hover:border-gray-300'">
                    <div class="text-lg mb-1">📅</div>
                    <div class="text-sm font-medium">1 Month</div>
                    <div class="text-xs text-gray-500">Standard timing</div>
                </button>
                
                <!-- 3 Months -->
                <button type="button" @click="setPreset('3months')"
                        class="p-3 text-center border-2 rounded-lg transition-all duration-200 hover:shadow-md"
                        :class="selectedPreset === '3months' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 hover:border-gray-300'">
                    <div class="text-lg mb-1">🎯</div>
                    <div class="text-sm font-medium">3 Months</div>
                    <div class="text-xs text-gray-500">Relaxed pace</div>
                </button>
            </div>
            
            <!-- Custom Date Selection -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <label class="text-sm font-medium text-gray-700">
                        <i class="fas fa-calendar-alt text-gray-500 mr-2"></i>
                        Custom Date
                        <span class="ml-2 text-xs font-normal text-gray-600 bg-gray-100 px-2 py-1 rounded-md" id="wizard-deadline-timezone-indicator">
                            @php
                                $userTimezone = auth()->user()->getTimezone();
                                $browserTimezone = 'Loading...';
                                try {
                                    $date = new DateTime();
                                    $timeString = $date->format('T');
                                    $abbreviation = $timeString;
                                    echo $abbreviation . ' (' . $userTimezone . ')';
                                } catch (Exception $e) {
                                    echo $userTimezone ?: 'Loading timezone...';
                                }
                            @endphp
                        </span>
                    </label>
                    <button type="button" @click="setPreset('custom')"
                            class="text-xs px-2 py-1 rounded border transition-colors"
                            :class="selectedPreset === 'custom' ? 'bg-blue-100 border-blue-300 text-blue-700' : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50'">
                        Use Custom
                    </button>
                </div>
                
                <div class="space-y-3">
                    <input type="datetime-local" 
                           x-model="customDate"
                           @change="updateCustomDate()"
                           :min="new Date().toISOString().split('T')[0]"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                </div>
            </div>
            
            <!-- Deadline Impact Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <h5 class="text-sm font-medium text-blue-800 mb-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    How deadlines affect your project
                </h5>
                <ul class="text-xs text-blue-700 space-y-1">
                    <li>• <strong>Shorter deadlines</strong> may limit the number of producers who can participate</li>
                    <li>• <strong>Longer deadlines</strong> give producers more time to create quality work</li>
                    <li>• <strong>No deadline</strong> keeps your project open indefinitely</li>
                </ul>
            </div>
        </div>
        
        @error('form.deadline')
        <p class="mt-2 text-sm text-red-600 flex items-center">
            <i class="fas fa-exclamation-circle mr-1"></i>
            {{ $message }}
        </p>
        @enderror
    </div>
</div>

<script>
// Updated timezone handling - v2.1
/**
 * Update wizard timezone indicator on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    updateWizardTimezoneIndicator();
    
    // Update timezone indicator after Livewire updates
    document.addEventListener('livewire:updated', function() {
        updateWizardTimezoneIndicator();
    });
});

function updateWizardTimezoneIndicator() {
    try {
        // Get user's profile timezone (passed from blade template)
        const userTimezone = @js(auth()->user()->getTimezone());
        const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        
        // Use user's profile timezone if available, otherwise fall back to browser timezone
        const timezoneToUse = userTimezone || browserTimezone;
        const timezoneDisplayName = getWizardTimezoneDisplayName(timezoneToUse);
        
        const indicator = document.getElementById('wizard-deadline-timezone-indicator');
        if (indicator) {
            indicator.textContent = timezoneDisplayName;
        }
        
    } catch (error) {
        console.error('Error updating wizard timezone indicator:', error);
    }
}

function getWizardTimezoneDisplayName(timezone) {
    try {
        // Use toLocaleString with timeZoneName to get abbreviation
        const date = new Date();
        const timeString = date.toLocaleString('en-US', {
            timeZone: timezone,
            timeZoneName: 'short'
        });
        
        // Extract timezone abbreviation from the end of the string
        const match = timeString.match(/\b([A-Z]{2,5})\s*$/);
        const abbreviation = match ? match[1] : null;
        
        if (abbreviation) {
            return `${abbreviation} (${timezone})`;
        }
        
        // Fallback: just return the timezone identifier
        return timezone;
    } catch (error) {
        console.error('Error getting wizard timezone display name:', error);
        return timezone;
    }
}

// JavaScript timezone conversion function removed
// Now using server-side timezone conversion with wire:model bindings
// This eliminates the double-conversion issue and browser compatibility problems
</script> 