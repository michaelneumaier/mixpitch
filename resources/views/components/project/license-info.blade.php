@props(['project'])

@php
    $licenseTemplate = $project->license_template_id ? $project->licenseTemplate : null;
    $requiresAgreement = $project->requires_license_agreement ?? false;
    $hasLicenseNotes = !empty($project->license_notes);
@endphp

<!-- License Information Section -->
<flux:card class="mb-4">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-lg shadow-sm">
                <flux:icon name="document-check" class="text-white" size="sm" />
            </div>
            <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">License & Terms</flux:heading>
        </div>
        
        <!-- Status Badge -->
        @if($licenseTemplate || $requiresAgreement)
            <flux:badge color="green" size="sm" icon="shield-check">
                Protected
            </flux:badge>
        @else
            <flux:badge color="gray" size="sm" icon="information-circle">
                Standard
            </flux:badge>
        @endif
    </div>

    @if($licenseTemplate)
        <!-- License Template Information -->
        <flux:callout color="green" class="mb-4">
            <flux:callout.heading>
                {{ $licenseTemplate->name }}
                @if($licenseTemplate->category)
                    <flux:badge color="green" size="xs" class="ml-2">
                        {{ ucwords(str_replace('_', ' ', $licenseTemplate->category)) }}
                    </flux:badge>
                @endif
            </flux:callout.heading>
            @if($licenseTemplate->description)
                <flux:callout.text class="mb-3">
                    {{ $licenseTemplate->description }}
                </flux:callout.text>
            @endif
            
            <!-- Key Terms Preview -->
            @if($licenseTemplate->license_terms)
                <div class="space-y-2 mb-4">
                    <flux:text size="sm" class="font-semibold">Key Terms:</flux:text>
                    <div class="grid grid-cols-1 gap-2">
                        @if($licenseTemplate->license_terms['commercial_use'] ?? false)
                            <div class="flex items-center gap-2 text-sm">
                                <flux:icon name="check-circle" size="xs" class="text-green-600" />
                                <span>Commercial Use Allowed</span>
                            </div>
                        @endif
                        
                        @if($licenseTemplate->license_terms['attribution_required'] ?? false)
                            <div class="flex items-center gap-2 text-sm">
                                <flux:icon name="user" size="xs" class="text-green-600" />
                                <span>Attribution Required</span>
                            </div>
                        @endif
                        
                        @if($licenseTemplate->license_terms['modification_allowed'] ?? false)
                            <div class="flex items-center gap-2 text-sm">
                                <flux:icon name="pencil" size="xs" class="text-green-600" />
                                <span>Modification Allowed</span>
                            </div>
                        @endif
                        
                        @if($licenseTemplate->license_terms['redistribution_allowed'] ?? false)
                            <div class="flex items-center gap-2 text-sm">
                                <flux:icon name="share" size="xs" class="text-green-600" />
                                <span>Redistribution Allowed</span>
                            </div>
                        @endif
                        
                        @if($licenseTemplate->license_terms['exclusive_rights'] ?? false)
                            <div class="flex items-center gap-2 text-sm">
                                <flux:icon name="star" size="xs" class="text-green-600" />
                                <span>Exclusive Rights</span>
                            </div>
                        @endif
                        
                        @if($licenseTemplate->license_terms['sync_licensing'] ?? false)
                            <div class="flex items-center gap-2 text-sm">
                                <flux:icon name="film" size="xs" class="text-green-600" />
                                <span>Sync Licensing Included</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </flux:callout>

        <!-- View Full License Button -->
        <flux:button onclick="viewLicenseModal()" icon="eye" variant="outline" size="sm" class="mb-4">
            View Full License
        </flux:button>
    @endif

    @if($requiresAgreement)
        <!-- Agreement Requirement -->
        <flux:callout color="blue" icon="document-check" class="mb-4">
            <flux:callout.heading>License Agreement Required</flux:callout.heading>
            <flux:callout.text>
                Collaborators must agree to the project license terms before participating. The license agreement will be required before any work can begin.
            </flux:callout.text>
        </flux:callout>
    @endif

    @if($hasLicenseNotes)
        <!-- License Notes -->
        <flux:callout color="amber" icon="clipboard-document-list" class="mb-4">
            <flux:callout.heading>Additional License Notes</flux:callout.heading>
            <flux:callout.text class="whitespace-pre-wrap">
                {{ $project->license_notes }}
            </flux:callout.text>
        </flux:callout>
    @endif

    @if(!$licenseTemplate && !$requiresAgreement && !$hasLicenseNotes)
        <!-- Default License Information -->
        <flux:callout color="gray" icon="information-circle" class="mb-4">
            <flux:callout.heading>Standard Collaboration Terms</flux:callout.heading>
            <flux:callout.text>
                This project follows standard collaboration terms. Work will be governed by the platform's 
                <flux:button href="#" variant="ghost" size="xs" class="underline p-0 h-auto">Terms of Service</flux:button> 
                and mutual agreement between collaborators.
            </flux:callout.text>
        </flux:callout>
    @endif
</flux:card>

<!-- License Preview Modal -->
<div id="licensePreviewModal" class="fixed inset-0 z-50 hidden overflow-y-auto" 
     aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeLicenseModal()"></div>
        
        <!-- Modal panel -->
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <flux:heading size="lg" id="modal-title">{{ $licenseTemplate ? $licenseTemplate->name : 'License Agreement' }}</flux:heading>
                    @if($licenseTemplate)
                        <div class="flex gap-2 mt-2">
                            <flux:badge color="gray" size="xs">{{ $licenseTemplate->category_name ?? 'License' }}</flux:badge>
                            @if($licenseTemplate->use_case)
                                <flux:badge color="blue" size="xs">{{ $licenseTemplate->use_case_name }}</flux:badge>
                            @endif
                        </div>
                    @endif
                </div>
                <flux:button onclick="closeLicenseModal()" icon="x-mark" variant="ghost" size="sm" />
            </div>
            
            <!-- Content -->
            <div class="p-6 overflow-y-auto max-h-96">
                <div id="license-content" class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line p-4 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                    @if($licenseTemplate)
                        {!! nl2br(e($licenseTemplate->generateLicenseContent())) !!}
                    @else
                        <flux:text class="text-slate-500 dark:text-slate-400 italic">No license content available.</flux:text>
                    @endif
                </div>
            </div>
            
            <!-- Footer -->
            <div class="flex justify-end p-6 border-t border-gray-200 dark:border-gray-700">
                <flux:button onclick="closeLicenseModal()" variant="outline">
                    Close
                </flux:button>
            </div>
        </div>
    </div>
</div>

<script>
function viewLicenseModal() {
    const modal = document.getElementById('licensePreviewModal');
    modal.classList.remove('hidden');
}

function closeLicenseModal() {
    document.getElementById('licensePreviewModal').classList.add('hidden');
}
</script> 