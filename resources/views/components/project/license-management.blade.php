@props(['project', 'workflowColors' => null, 'semanticColors' => null])

@php
    $licenseTemplate = $project->license_template_id ? $project->licenseTemplate : null;
    $requiresAgreement = $project->requires_license_agreement ?? false;
    $hasLicenseNotes = !empty($project->license_notes);
    
    // Get fresh license signatures for this project
    $licenseSignatures = $project->licenseSignatures()->get();
    $pendingSignatures = $licenseSignatures->where('status', 'pending');
    $signedSignatures = $licenseSignatures->where('status', 'active');

    // Create workflow-aware gradient classes matching other components
    $gradientClasses = match($project->workflow_type) {
        'standard' => [
            'outer' => 'bg-gradient-to-br from-blue-50/95 to-indigo-50/90 dark:from-blue-950/95 dark:to-indigo-950/90 backdrop-blur-sm border border-blue-200/50 dark:border-blue-700/50',
            'header' => 'bg-gradient-to-r from-blue-100/80 to-indigo-100/80 dark:from-blue-900/80 dark:to-indigo-900/80 border-b border-blue-200/30 dark:border-blue-700/30',
            'text_primary' => 'text-blue-900 dark:text-blue-100',
            'text_secondary' => 'text-blue-700 dark:text-blue-300',
            'text_muted' => 'text-blue-600 dark:text-blue-400',
            'icon' => 'text-blue-600 dark:text-blue-400'
        ],
        'contest' => [
            'outer' => 'bg-gradient-to-br from-amber-50/95 to-yellow-50/90 dark:from-amber-950/95 dark:to-yellow-950/90 backdrop-blur-sm border border-amber-200/50 dark:border-amber-700/50',
            'header' => 'bg-gradient-to-r from-amber-100/80 to-yellow-100/80 dark:from-amber-900/80 dark:to-yellow-900/80 border-b border-amber-200/30 dark:border-amber-700/30',
            'text_primary' => 'text-amber-900 dark:text-amber-100',
            'text_secondary' => 'text-amber-700 dark:text-amber-300',
            'text_muted' => 'text-amber-600 dark:text-amber-400',
            'icon' => 'text-amber-600 dark:text-amber-400'
        ],
        'direct_hire' => [
            'outer' => 'bg-gradient-to-br from-green-50/95 to-emerald-50/90 dark:from-green-950/95 dark:to-emerald-950/90 backdrop-blur-sm border border-green-200/50 dark:border-green-700/50',
            'header' => 'bg-gradient-to-r from-green-100/80 to-emerald-100/80 dark:from-green-900/80 dark:to-emerald-900/80 border-b border-green-200/30 dark:border-green-700/30',
            'text_primary' => 'text-green-900 dark:text-green-100',
            'text_secondary' => 'text-green-700 dark:text-green-300',
            'text_muted' => 'text-green-600 dark:text-green-400',
            'icon' => 'text-green-600 dark:text-green-400'
        ],
        'client_management' => [
            'outer' => 'bg-gradient-to-br from-purple-50/95 to-indigo-50/90 dark:from-purple-950/95 dark:to-indigo-950/90 backdrop-blur-sm border border-purple-200/50 dark:border-purple-700/50',
            'header' => 'bg-gradient-to-r from-purple-100/80 to-indigo-100/80 dark:from-purple-900/80 dark:to-indigo-900/80 border-b border-purple-200/30 dark:border-purple-700/30',
            'text_primary' => 'text-purple-900 dark:text-purple-100',
            'text_secondary' => 'text-purple-700 dark:text-purple-300',
            'text_muted' => 'text-purple-600 dark:text-purple-400',
            'icon' => 'text-purple-600 dark:text-purple-400'
        ],
        default => [
            'outer' => 'bg-gradient-to-br from-gray-50/95 to-slate-50/90 dark:from-gray-950/95 dark:to-slate-950/90 backdrop-blur-sm border border-gray-200/50 dark:border-gray-700/50',
            'header' => 'bg-gradient-to-r from-gray-100/80 to-slate-100/80 dark:from-gray-900/80 dark:to-slate-900/80 border-b border-gray-200/30 dark:border-gray-700/30',
            'text_primary' => 'text-gray-900 dark:text-gray-100',
            'text_secondary' => 'text-gray-700 dark:text-gray-300',
            'text_muted' => 'text-gray-600 dark:text-gray-400',
            'icon' => 'text-gray-600 dark:text-gray-400'
        ]
    };

    // Provide fallback colors if not passed from parent
    $workflowColors = $workflowColors ?? [
        'text_primary' => $gradientClasses['text_primary'],
        'text_secondary' => $gradientClasses['text_secondary'],
        'text_muted' => $gradientClasses['text_muted'],
        'icon' => $gradientClasses['icon']
    ];

    $semanticColors = $semanticColors ?? [
        'success' => ['bg' => 'bg-green-50 dark:bg-green-950', 'text' => 'text-green-800 dark:text-green-200', 'icon' => 'text-green-600 dark:text-green-400'],
        'warning' => ['bg' => 'bg-amber-50 dark:bg-amber-950', 'text' => 'text-amber-800 dark:text-amber-200', 'icon' => 'text-amber-600 dark:text-amber-400'],
        'danger' => ['bg' => 'bg-red-50 dark:bg-red-950', 'text' => 'text-red-800 dark:text-red-200', 'icon' => 'text-red-600 dark:text-red-400']
    ];
@endphp

<!-- License Management Section -->
<div class="{{ $gradientClasses['outer'] }} rounded-2xl shadow-lg overflow-hidden">
    <!-- Professional Header matching workflow-status style -->
    <div class="{{ $gradientClasses['header'] }} p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold {{ $gradientClasses['text_primary'] }} flex items-center">
                    <flux:icon.document-text class="w-5 h-5 {{ $gradientClasses['icon'] }} mr-3" />
                    License Management
                </h3>
                <p class="text-sm {{ $gradientClasses['text_secondary'] }} mt-1">
                    @if($requiresAgreement)
                        {{ $signedSignatures->count() }} {{ Str::plural('agreement', $signedSignatures->count()) }} active
                    @else
                        Using platform default terms
                    @endif
                </p>
            </div>
            <div class="text-right">
                @if($requiresAgreement)
                    <div class="text-2xl font-bold {{ $gradientClasses['icon'] }}">{{ $signedSignatures->count() }}</div>
                    <div class="text-xs {{ $gradientClasses['text_muted'] }}">Agreements</div>
                @else
                    <div class="bg-white/60 dark:bg-gray-800/60 border border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-200/30 dark:border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-700/30 rounded-xl px-3 py-2">
                        <div class="text-xs {{ $gradientClasses['text_secondary'] }} font-medium">Default Terms</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="p-6">
        @if($licenseTemplate || $requiresAgreement || $hasLicenseNotes)
            <!-- Compact License Overview -->
            <div class="bg-white/60 dark:bg-gray-800/60 border border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-200/30 dark:border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-700/30 rounded-xl p-4 mb-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        @if($licenseTemplate)
                            <div class="flex items-center gap-2 mb-2">
                                <flux:icon.document class="w-4 h-4 {{ $gradientClasses['icon'] }}" />
                                <span class="text-sm font-medium {{ $gradientClasses['text_primary'] }}">{{ $licenseTemplate->name }}</span>
                                @if($licenseTemplate->category)
                                    <flux:badge color="gray" size="xs">
                                        {{ ucwords(str_replace('_', ' ', $licenseTemplate->category)) }}
                                    </flux:badge>
                                @endif
                            </div>
                        @endif
                        
                        @if($hasLicenseNotes)
                            <div class="text-xs {{ $gradientClasses['text_muted'] }} mb-2">
                                <span class="font-medium">Custom Notes:</span> {{ Str::limit($project->license_notes, 80) }}
                            </div>
                        @endif
                        
                        <div class="flex items-center gap-3 text-xs {{ $gradientClasses['text_muted'] }}">
                            <div class="flex items-center gap-1">
                                @if($requiresAgreement)
                                    <flux:icon.check-circle class="w-3 h-3 text-green-500" />
                                    <span>Agreement required</span>
                                @else
                                    <flux:icon.information-circle class="w-3 h-3" />
                                    <span>Platform defaults</span>
                                @endif
                            </div>
                            @if($requiresAgreement && $signedSignatures->count() > 0)
                                <div class="flex items-center gap-1">
                                    <flux:icon.users class="w-3 h-3" />
                                    <span>{{ $signedSignatures->count() }} signed</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex gap-2 ml-4">
                        @if($licenseTemplate)
                            <flux:button 
                                variant="ghost"
                                size="xs"
                                flux:modal="license-preview">
                                <flux:icon.eye class="w-3 h-3" />
                            </flux:button>
                        @endif
                        <flux:button 
                            href="{{ route('projects.edit', $project) }}#license"
                            variant="ghost"
                            size="xs">
                            <flux:icon.pencil class="w-3 h-3" />
                        </flux:button>
                    </div>
                </div>
            </div>
            
            @if($requiresAgreement && $signedSignatures->count() > 0)
                <!-- Simple Compliance Status -->
                <div class="bg-green-50/50 dark:bg-green-950/50 border border-green-200/30 dark:border-green-800/30 rounded-lg p-3 text-center">
                    <div class="flex items-center justify-center gap-2 text-sm {{ $semanticColors['success']['text'] }}">
                        <flux:icon.shield-check class="w-4 h-4" />
                        <span>License compliance active - Status shown with each pitch</span>
                    </div>
                </div>
            @endif
        @else
            <!-- Clean Empty State -->
            <div class="bg-white/60 dark:bg-gray-800/60 border border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-200/30 dark:border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-700/30 rounded-xl p-6 text-center">
                <div class="flex items-center justify-center gap-2 {{ $gradientClasses['text_muted'] }} mb-3">
                    <flux:icon.document-text class="w-5 h-5" />
                    <span class="text-sm font-medium">Using platform default terms</span>
                </div>
                <p class="text-xs {{ $gradientClasses['text_muted'] }} mb-4">
                    Add a specific license template to provide clearer terms for collaborators.
                </p>
                <flux:button 
                    href="{{ route('projects.edit', $project) }}#license"
                    variant="primary"
                    size="sm">
                    <flux:icon.plus class="w-3 h-3 mr-1" />
                    Add License
                </flux:button>
            </div>
        @endif
    </div>
</div>

<!-- License Preview Modal -->
<flux:modal name="license-preview" class="max-w-2xl">
    <div class="p-6">
        <flux:heading class="mb-4">License Agreement</flux:heading>
        
        @if($licenseTemplate)
            <div class="mb-4 flex gap-2">
                <flux:badge color="gray" size="sm">{{ $licenseTemplate->category_name ?? 'License' }}</flux:badge>
                @if($licenseTemplate->use_case)
                    <flux:badge color="blue" size="sm">{{ $licenseTemplate->use_case_name }}</flux:badge>
                @endif
            </div>
        @endif
        
        <div class="max-h-96 overflow-y-auto mb-6">
            <div id="license-content" class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                <!-- License content will be loaded here -->
            </div>
        </div>
        
        <div class="flex justify-end">
            <flux:button variant="ghost" flux:modal.close>Close</flux:button>
        </div>
    </div>
</flux:modal>

@php
$licenseData = [
    'name' => $licenseTemplate ? $licenseTemplate->name : 'License Agreement',
    'content' => $licenseTemplate ? nl2br(e($licenseTemplate->generateLicenseContent())) : 'No license content available.'
];
@endphp

<script>
// Pre-render license content server-side for security
const licenseData = @json($licenseData);

// Listen for modal opening to populate content
document.addEventListener('flux:modal.opened', function(event) {
    if (event.detail.name === 'license-preview') {
        const content = document.getElementById('license-content');
        if (content) {
            content.innerHTML = licenseData.content;
        }
    }
});

function sendReminders() {
    // This would trigger a Livewire method to send reminder emails
    if (confirm('Send reminder emails to all collaborators with pending license agreements?')) {
        // Add Livewire event dispatch here
        window.dispatchEvent(new CustomEvent('send-license-reminders'));
    }
}
</script> 