@props(['project'])

@php
    $licenseTemplate = $project->license_template_id ? $project->licenseTemplate : null;
    $requiresAgreement = $project->requires_license_agreement ?? false;
    $hasLicenseNotes = !empty($project->license_notes);
@endphp

<!-- License Information Section -->
<div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
    <!-- Gradient Border Effect -->
    <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/20 via-teal-500/20 to-cyan-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    <div class="relative bg-white/95 backdrop-blur-sm rounded-2xl m-0.5 p-8">
        <!-- Header with Icon -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                    <i class="fas fa-file-contract text-white text-lg"></i>
                </div>
                <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                    License & Terms
                </h3>
            </div>
            
            <!-- Status Badge -->
            @if($licenseTemplate || $requiresAgreement)
                <div class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-gradient-to-r from-emerald-100 to-teal-100 text-emerald-800 border border-emerald-200/50">
                    <i class="fas fa-shield-alt mr-2"></i>
                    License Protected
                </div>
            @else
                <div class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 border border-gray-200/50">
                    <i class="fas fa-info-circle mr-2"></i>
                    Standard Terms
                </div>
            @endif
        </div>

        @if($licenseTemplate)
            <!-- License Template Information -->
            <div class="mb-6">
                <div class="bg-gradient-to-r from-emerald-50/50 to-teal-50/50 rounded-xl p-6 border border-emerald-200/30">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h4 class="text-lg font-bold text-emerald-900 mb-2">{{ $licenseTemplate->name }}</h4>
                            @if($licenseTemplate->description)
                                <p class="text-emerald-800 text-sm mb-4 leading-relaxed">{{ $licenseTemplate->description }}</p>
                            @endif
                        </div>
                        
                        <!-- License Category Badge -->
                        @if($licenseTemplate->category)
                            <div class="ml-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold bg-gradient-to-r from-emerald-200 to-teal-200 text-emerald-900 shadow-sm">
                                    {{ ucwords(str_replace('_', ' ', $licenseTemplate->category)) }}
                                </span>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Key Terms Preview -->
                    @if($licenseTemplate->license_terms)
                        <div class="space-y-3">
                            <h5 class="text-sm font-semibold text-emerald-900 mb-2">Key Terms:</h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @if($licenseTemplate->license_terms['commercial_use'] ?? false)
                                    <div class="flex items-center text-sm text-emerald-800">
                                        <i class="fas fa-check-circle text-emerald-600 mr-2"></i>
                                        Commercial Use Allowed
                                    </div>
                                @endif
                                
                                @if($licenseTemplate->license_terms['attribution_required'] ?? false)
                                    <div class="flex items-center text-sm text-emerald-800">
                                        <i class="fas fa-user-tag text-emerald-600 mr-2"></i>
                                        Attribution Required
                                    </div>
                                @endif
                                
                                @if($licenseTemplate->license_terms['modification_allowed'] ?? false)
                                    <div class="flex items-center text-sm text-emerald-800">
                                        <i class="fas fa-edit text-emerald-600 mr-2"></i>
                                        Modification Allowed
                                    </div>
                                @endif
                                
                                @if($licenseTemplate->license_terms['redistribution_allowed'] ?? false)
                                    <div class="flex items-center text-sm text-emerald-800">
                                        <i class="fas fa-share text-emerald-600 mr-2"></i>
                                        Redistribution Allowed
                                    </div>
                                @endif
                                
                                @if($licenseTemplate->license_terms['exclusive_rights'] ?? false)
                                    <div class="flex items-center text-sm text-emerald-800">
                                        <i class="fas fa-crown text-emerald-600 mr-2"></i>
                                        Exclusive Rights
                                    </div>
                                @endif
                                
                                @if($licenseTemplate->license_terms['sync_licensing'] ?? false)
                                    <div class="flex items-center text-sm text-emerald-800">
                                        <i class="fas fa-film text-emerald-600 mr-2"></i>
                                        Sync Licensing Included
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- View Full License Button -->
            <div class="mb-6">
                <button onclick="viewLicenseModal()" 
                        class="group/btn inline-flex items-center px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                    <i class="fas fa-eye mr-2 group-hover/btn:scale-110 transition-transform"></i>
                    View Full License
                </button>
            </div>
        @endif

        @if($requiresAgreement)
            <!-- Agreement Requirement -->
            <div class="bg-gradient-to-r from-blue-50/50 to-indigo-50/50 rounded-xl p-6 border border-blue-200/30 mb-6">
                <div class="flex items-start">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-4 mt-1">
                        <i class="fas fa-file-signature text-blue-600"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-lg font-semibold text-blue-900 mb-2">License Agreement Required</h4>
                        <p class="text-blue-800 text-sm leading-relaxed">
                            Collaborators must agree to the project license terms before participating. The license agreement will be required before any work can begin.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if($hasLicenseNotes)
            <!-- License Notes -->
            <div class="bg-gradient-to-r from-amber-50/50 to-orange-50/50 rounded-xl p-6 border border-amber-200/30 mb-6">
                <div class="flex items-start">
                    <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center mr-4 mt-1">
                        <i class="fas fa-sticky-note text-amber-600"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-lg font-semibold text-amber-900 mb-2">Additional License Notes</h4>
                        <p class="text-amber-800 text-sm leading-relaxed whitespace-pre-wrap">{{ $project->license_notes }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(!$licenseTemplate && !$requiresAgreement && !$hasLicenseNotes)
            <!-- Default License Information -->
            <div class="bg-gradient-to-r from-gray-50/50 to-slate-50/50 rounded-xl p-6 border border-gray-200/30">
                <div class="flex items-start">
                    <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center mr-4 mt-1">
                        <i class="fas fa-info-circle text-gray-600"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Standard Collaboration Terms</h4>
                        <p class="text-gray-700 text-sm leading-relaxed">
                            This project follows standard collaboration terms. Work will be governed by the platform's 
                            <a href="#" class="text-blue-600 hover:text-blue-800 underline">Terms of Service</a> 
                            and mutual agreement between collaborators.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Important Notice -->
        <div class="mt-6 p-4 bg-gradient-to-r from-gray-100/50 to-gray-200/50 rounded-lg border border-gray-200/30">
            <p class="text-xs text-gray-600 leading-relaxed">
                <i class="fas fa-exclamation-triangle text-gray-500 mr-1"></i>
                <strong>Important:</strong> By participating in this project, you agree to abide by all license terms. 
                Make sure you understand the licensing requirements before starting work.
            </p>
        </div>
    </div>
</div>

<!-- License Preview Modal -->
<div id="licensePreviewModal" class="fixed inset-0 z-[9999] hidden overflow-y-auto" 
     aria-labelledby="modal-title" role="dialog" aria-modal="true" 
     style="z-index: 9999;">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeLicenseModal()"></div>
        
        <!-- Spacer element to center modal -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="modal-title">{{ $licenseTemplate ? $licenseTemplate->name : 'License Agreement' }}</h3>
                    <button type="button" onclick="closeLicenseModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                @if($licenseTemplate)
                    <div class="mb-4">
                        <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">{{ $licenseTemplate->category_name ?? 'License' }}</span>
                        @if($licenseTemplate->use_case)
                            <span class="inline-block bg-blue-100 text-blue-600 text-xs px-2 py-1 rounded-full ml-1">{{ $licenseTemplate->use_case_name }}</span>
                        @endif
                    </div>
                @endif
                
                <div class="max-h-96 overflow-y-auto">
                    <div id="license-content" class="text-sm text-gray-700 whitespace-pre-line border rounded-lg p-4 bg-gray-50">
                        @if($licenseTemplate)
                            {!! nl2br(e($licenseTemplate->generateLicenseContent())) !!}
                        @else
                            <div class="text-gray-500 italic">No license content available.</div>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeLicenseModal()" 
                        class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                    Close
                </button>
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