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
                <button onclick="viewLicenseModal('{{ $licenseTemplate->id }}')" 
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
<div id="licensePreviewModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeLicenseModal()"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-2xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
            <div class="sm:flex sm:items-start">
                <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                    <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4" id="modal-title">
                        License Agreement
                    </h3>
                    <div class="mt-2">
                        <div id="license-content" class="text-sm text-gray-700 max-h-96 overflow-y-auto">
                            <!-- License content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeLicenseModal()" 
                        class="w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function viewLicenseModal(licenseId) {
    const modal = document.getElementById('licensePreviewModal');
    const content = document.getElementById('license-content');
    const title = document.getElementById('modal-title');
    
    // Show loading state
    content.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i><p class="text-gray-500 mt-2">Loading license...</p></div>';
    modal.classList.remove('hidden');
    
    // Fetch license content
    fetch(`/api/licenses/${licenseId}/preview`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                title.textContent = data.license.name;
                content.innerHTML = data.license.content || '<p class="text-gray-500">No license content available.</p>';
            } else {
                content.innerHTML = '<p class="text-red-500">Error loading license content.</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<p class="text-red-500">Error loading license content.</p>';
        });
}

function closeLicenseModal() {
    document.getElementById('licensePreviewModal').classList.add('hidden');
}
</script> 