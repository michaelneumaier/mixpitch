@props(['project'])

@php
    $licenseTemplate = $project->license_template_id ? $project->licenseTemplate : null;
    $requiresAgreement = $project->requires_license_agreement ?? false;
    $hasLicenseNotes = !empty($project->license_notes);
    
    // Get fresh license signatures for this project
    $licenseSignatures = $project->licenseSignatures()->get();
    $pendingSignatures = $licenseSignatures->where('status', 'pending');
    $signedSignatures = $licenseSignatures->where('status', 'active'); // Fixed: looking for 'active' status
@endphp

<!-- License Management Section -->
<div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
    <!-- Gradient Border Effect -->
    <div class="absolute inset-0 bg-gradient-to-r from-purple-500/20 via-blue-500/20 to-teal-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    <div class="relative bg-white/95 backdrop-blur-sm rounded-2xl m-0.5 p-4 lg:p-6">
        <!-- Header -->
        <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-blue-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                    <i class="fas fa-file-contract text-white text-lg"></i>
                </div>
                <div>
                <h3 class="text-xl lg:text-2xl font-bold bg-gradient-to-r from-gray-900 to-purple-800 bg-clip-text text-transparent">
                        License Management
                    </h3>
                    <p class="text-gray-600 text-sm">Track license agreements and compliance</p>
                </div>
        </div>

        @if($licenseTemplate || $requiresAgreement || $hasLicenseNotes)
            <!-- Current License Configuration -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- License Template Info -->
                <div class="bg-gradient-to-r from-purple-50/50 to-blue-50/50 rounded-xl p-6 border border-purple-200/30">
                    <h4 class="text-lg font-bold text-purple-900 mb-4 flex items-center">
                        <i class="fas fa-document text-purple-600 mr-2"></i>
                        License Template
                    </h4>
                    
                    @if($licenseTemplate)
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-purple-700">Template:</span>
                                <span class="text-purple-900 font-semibold">{{ $licenseTemplate->name }}</span>
                            </div>
                            @if($licenseTemplate->category)
                                <div>
                                    <span class="text-sm font-medium text-purple-700">Category:</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-semibold bg-purple-200 text-purple-900 ml-2">
                                        {{ ucwords(str_replace('_', ' ', $licenseTemplate->category)) }}
                                    </span>
                                </div>
                            @endif
                            <div>
                                <span class="text-sm font-medium text-purple-700">Type:</span>
                                <span class="text-purple-900">{{ $licenseTemplate->user_id ? 'Custom Template' : 'System Template' }}</span>
                            </div>
                        </div>
                    @else
                        <p class="text-purple-700">No specific license template selected. Using platform default terms.</p>
                    @endif
                </div>

                <!-- License Settings -->
                <div class="bg-gradient-to-r from-blue-50/50 to-teal-50/50 rounded-xl p-6 border border-blue-200/30">
                    <h4 class="text-lg font-bold text-blue-900 mb-4 flex items-center">
                        <i class="fas fa-cog text-blue-600 mr-2"></i>
                        Settings
                    </h4>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-blue-700">Agreement Required:</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $requiresAgreement ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                <i class="fas {{ $requiresAgreement ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                {{ $requiresAgreement ? 'Yes' : 'No' }}
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-blue-700">Custom Notes:</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $hasLicenseNotes ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700' }}">
                                <i class="fas {{ $hasLicenseNotes ? 'fa-sticky-note' : 'fa-times' }} mr-1"></i>
                                {{ $hasLicenseNotes ? 'Yes' : 'None' }}
                            </span>
                        </div>
                        
                        @if($hasLicenseNotes)
                            <div class="mt-4 p-3 bg-blue-100/50 rounded-lg border border-blue-200/30">
                                <p class="text-sm text-blue-800">{{ $project->license_notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($requiresAgreement)
                <!-- Active License Agreements Section -->
                <div class="bg-gradient-to-r from-green-50/50 to-emerald-50/50 rounded-xl p-6 border border-green-200/30 mb-6">
                    <h4 class="text-lg font-bold text-green-900 mb-4 flex items-center">
                        <i class="fas fa-check-shield text-green-600 mr-2"></i>
                        License Compliance
                    </h4>

                    <!-- Compliance Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="bg-white/60 rounded-lg p-4 border border-green-200/50">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-green-600">Active Agreements</p>
                                    <p class="text-2xl font-bold text-green-700">{{ $signedSignatures->count() }}</p>
                                    <p class="text-xs text-green-600 mt-1">Users who agreed during pitch creation</p>
                                </div>
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-check-circle text-green-600"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white/60 rounded-lg p-4 border border-blue-200/50">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-blue-600">Compliance Status</p>
                                    <p class="text-lg font-bold text-blue-700">
                                        @if($signedSignatures->count() > 0)
                                            <i class="fas fa-shield-check mr-1"></i>Active
                                        @else
                                            <i class="fas fa-info-circle mr-1"></i>Ready
                                        @endif
                                    </p>
                                    <p class="text-xs text-blue-600 mt-1">
                                        @if($signedSignatures->count() > 0)
                                            All participants have agreed to terms
                                        @else
                                            License terms will be enforced on participation
                                        @endif
                                    </p>
                                </div>
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-shield-alt text-blue-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- License Status Summary -->
                    <div class="text-center py-4">
                        <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-lg text-sm">
                            <i class="fas fa-info-circle mr-2"></i>
                            License status is shown with each pitch submission
                        </div>
                    </div>
                </div>
            @endif

            <!-- License Actions -->
            <div class="flex flex-wrap gap-3">
                @if($licenseTemplate)
                    <button onclick="viewLicenseModal()" 
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-medium rounded-xl transition-all duration-200 hover:scale-105 shadow-lg">
                        <i class="fas fa-eye mr-2"></i>
                        View License Terms
                    </button>
                @endif
                
                <a href="{{ route('projects.edit', $project) }}#license" 
                   class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white font-medium rounded-xl transition-all duration-200 hover:scale-105 shadow-lg">
                    <i class="fas fa-edit mr-2"></i>
                    Modify License
                </a>

                @if($requiresAgreement)
                    <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-medium rounded-xl shadow-lg">
                        <i class="fas fa-info-circle mr-2"></i>
                        Auto-enforced on pitch creation
                    </div>
                @endif
            </div>
        @else
            <!-- No License Configuration -->
            <div class="text-center py-12">
                <div class="w-20 h-20 bg-gradient-to-br from-purple-100 to-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                    <i class="fas fa-file-contract text-3xl text-purple-400"></i>
                </div>
                <h4 class="text-xl font-semibold text-gray-700 mb-2">No License Configuration</h4>
                <p class="text-gray-500 max-w-md mx-auto mb-6">
                    This project is using platform default terms. You can add a specific license template to provide clearer terms for collaborators.
                </p>
                <a href="{{ route('projects.edit', $project) }}#license" 
                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-bold rounded-xl transition-all duration-200 hover:scale-105 shadow-lg">
                    <i class="fas fa-plus mr-2"></i>
                    Add License Template
                </a>
            </div>
        @endif
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
                    <h3 class="text-lg font-medium text-gray-900" id="modal-title">License Agreement</h3>
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
                        <!-- License content will be loaded here -->
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
// Pre-render license content server-side for security
const licenseData = @json([
    'name' => $licenseTemplate ? $licenseTemplate->name : 'License Agreement',
    'content' => $licenseTemplate ? nl2br(e($licenseTemplate->generateLicenseContent())) : 'No license content available.'
]);

function viewLicenseModal() {
    const modal = document.getElementById('licensePreviewModal');
    const content = document.getElementById('license-content');
    const title = document.getElementById('modal-title');
    
    // Set content from pre-rendered data
    title.textContent = licenseData.name;
    content.innerHTML = licenseData.content;
    
    // Show modal
    modal.classList.remove('hidden');
}

function closeLicenseModal() {
    document.getElementById('licensePreviewModal').classList.add('hidden');
}

function sendReminders() {
    // This would trigger a Livewire method to send reminder emails
    if (confirm('Send reminder emails to all collaborators with pending license agreements?')) {
        // Add Livewire event dispatch here
        window.dispatchEvent(new CustomEvent('send-license-reminders'));
    }
}
</script> 