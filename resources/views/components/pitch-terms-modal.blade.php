@props(['project'])

<!-- Terms of Service Modal -->
<div id="pitch-terms-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <!-- Enhanced backdrop with blur -->
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-all duration-300"></div>
    
    <!-- Background Effects -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-gradient-to-br from-blue-400/20 to-purple-600/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-48 h-48 bg-gradient-to-tr from-purple-400/20 to-pink-600/20 rounded-full blur-2xl"></div>
    </div>
    
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white/95 backdrop-blur-lg border border-white/30 rounded-2xl shadow-2xl max-w-4xl w-full mx-auto z-10 overflow-hidden">
            <!-- Modal Header -->
            <div class="px-8 py-6 bg-gradient-to-r from-blue-500/10 via-purple-500/10 to-blue-500/10 backdrop-blur-sm border-b border-white/20">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-2">
                            <i class="fas fa-paper-plane mr-3"></i>
                            Start Your Pitch
                        </h3>
                        <p class="text-gray-600 font-medium">for "{{ $project->name }}"</p>
                    </div>
                    <button type="button" onclick="closePitchTermsModal()" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50/50 rounded-xl transition-all duration-200 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-8">
                <div class="mb-8">
                    <!-- Welcome Section -->
                    <div class="bg-gradient-to-br from-blue-50/80 to-purple-50/80 backdrop-blur-sm border border-blue-200/50 rounded-2xl p-6 mb-6">
                        <div class="flex items-center mb-4">
                            <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl mr-4">
                                <i class="fas fa-rocket text-white text-lg"></i>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold text-blue-800">Welcome to your creative journey!</h4>
                                <p class="text-blue-600 text-sm">Let's get your pitch started</p>
                            </div>
                        </div>
                        <p class="text-gray-700 leading-relaxed">
                            You're about to start a pitch for this project. Before you begin, please review our terms to ensure a smooth collaboration experience.
                        </p>
                    </div>

                    <!-- Terms Section -->
                    <div class="bg-gradient-to-br from-white/90 to-gray-50/90 backdrop-blur-sm border border-white/50 rounded-2xl p-6 shadow-lg">
                        <div class="flex items-center mb-4">
                            <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl mr-3">
                                <i class="fas fa-shield-alt text-white"></i>
                            </div>
                            <h5 class="text-lg font-bold text-gray-800">Terms of Service Highlights</h5>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="flex items-center justify-center w-6 h-6 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full mr-3 mt-0.5 flex-shrink-0">
                                    <i class="fas fa-copyright text-white text-xs"></i>
                                </div>
                                <p class="text-gray-700">You retain ownership of your original work.</p>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex items-center justify-center w-6 h-6 bg-gradient-to-br from-green-400 to-green-600 rounded-full mr-3 mt-0.5 flex-shrink-0">
                                    <i class="fas fa-handshake text-white text-xs"></i>
                                </div>
                                <p class="text-gray-700">If your pitch is selected, you grant the project owner a license as specified in the project details.</p>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex items-center justify-center w-6 h-6 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full mr-3 mt-0.5 flex-shrink-0">
                                    <i class="fas fa-balance-scale text-white text-xs"></i>
                                </div>
                                <p class="text-gray-700">Respect copyright and intellectual property rights in your submissions.</p>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex items-center justify-center w-6 h-6 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full mr-3 mt-0.5 flex-shrink-0">
                                    <i class="fas fa-heart text-white text-xs"></i>
                                </div>
                                <p class="text-gray-700">Be respectful and professional in all communications.</p>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex items-center justify-center w-6 h-6 bg-gradient-to-br from-red-400 to-red-600 rounded-full mr-3 mt-0.5 flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-white text-xs"></i>
                                </div>
                                <p class="text-gray-700">We may remove content that violates our community standards.</p>
                            </div>
                        </div>
                        
                        <div class="mt-6 p-4 bg-gradient-to-r from-blue-50/80 to-purple-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl">
                            <p class="text-sm text-gray-700 flex items-center">
                                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                For a complete understanding, please review our full 
                                <a href="/terms" target="_blank" class="text-blue-600 hover:text-blue-700 font-medium underline decoration-blue-300 hover:decoration-blue-500 transition-colors duration-200 ml-1">
                                    Terms and Conditions
                                    <i class="fas fa-external-link-alt text-xs ml-1"></i>
                                </a>
                            </p>
                        </div>
                    </div>

                    @php
                        $licenseTemplate = $project->license_template_id ? $project->licenseTemplate : null;
                        $requiresAgreement = $project->requires_license_agreement ?? false;
                        $hasLicenseNotes = !empty($project->license_notes);
                    @endphp

                    @if($licenseTemplate || $requiresAgreement || $hasLicenseNotes)
                        <!-- Project License Section -->
                        <div class="bg-gradient-to-br from-emerald-50/90 to-teal-50/90 backdrop-blur-sm border border-emerald-200/50 rounded-2xl p-6 shadow-lg mt-6">
                            <div class="flex items-center mb-4">
                                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl mr-3">
                                    <i class="fas fa-file-contract text-white"></i>
                                </div>
                                <h5 class="text-lg font-bold text-emerald-800">Project License Terms</h5>
                            </div>

                            @if($licenseTemplate)
                                <div class="mb-4">
                                    <div class="bg-white/60 rounded-lg p-4 border border-emerald-200/30">
                                        <div class="flex items-start justify-between mb-3">
                                            <div class="flex-1">
                                                <h6 class="font-semibold text-emerald-900 mb-1">{{ $licenseTemplate->name }}</h6>
                                                @if($licenseTemplate->description)
                                                    <p class="text-emerald-800 text-sm">{{ $licenseTemplate->description }}</p>
                                                @endif
                                            </div>
                                            @if($licenseTemplate->category)
                                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-emerald-100 text-emerald-800 ml-3">
                                                    {{ ucwords(str_replace('_', ' ', $licenseTemplate->category)) }}
                                                </span>
                                            @endif
                                        </div>
                                        
                                        @if($licenseTemplate->terms)
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                                @if($licenseTemplate->terms['commercial_use'] ?? false)
                                                    <div class="flex items-center text-emerald-700">
                                                        <i class="fas fa-check-circle text-emerald-600 mr-2"></i>
                                                        Commercial Use Allowed
                                                    </div>
                                                @endif
                                                @if($licenseTemplate->terms['attribution_required'] ?? false)
                                                    <div class="flex items-center text-emerald-700">
                                                        <i class="fas fa-user-tag text-emerald-600 mr-2"></i>
                                                        Attribution Required
                                                    </div>
                                                @endif
                                                @if($licenseTemplate->terms['modification_allowed'] ?? false)
                                                    <div class="flex items-center text-emerald-700">
                                                        <i class="fas fa-edit text-emerald-600 mr-2"></i>
                                                        Modification Allowed
                                                    </div>
                                                @endif
                                                @if($licenseTemplate->terms['exclusive_rights'] ?? false)
                                                    <div class="flex items-center text-emerald-700">
                                                        <i class="fas fa-crown text-emerald-600 mr-2"></i>
                                                        Exclusive Rights
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                        
                                        <div class="mt-3 pt-3 border-t border-emerald-200/30">
                                            <button type="button" onclick="openProjectLicenseModal()" 
                                                    class="text-emerald-600 hover:text-emerald-700 text-sm font-medium flex items-center">
                                                <i class="fas fa-eye mr-1"></i>
                                                View Full License Terms
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($requiresAgreement)
                                <div class="bg-amber-50/60 rounded-lg p-4 border border-amber-200/30 mb-4">
                                    <div class="flex items-start">
                                        <i class="fas fa-file-signature text-amber-600 mr-3 mt-1"></i>
                                        <div>
                                            <h6 class="font-semibold text-amber-900 mb-1">License Agreement Required</h6>
                                            <p class="text-amber-800 text-sm">
                                                If your pitch is selected, you'll be required to sign the project license agreement before work begins.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($hasLicenseNotes)
                                <div class="bg-blue-50/60 rounded-lg p-4 border border-blue-200/30">
                                    <div class="flex items-start">
                                        <i class="fas fa-sticky-note text-blue-600 mr-3 mt-1"></i>
                                        <div>
                                            <h6 class="font-semibold text-blue-900 mb-1">Additional License Notes</h6>
                                            <p class="text-blue-800 text-sm whitespace-pre-wrap">{{ $project->license_notes }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Agreement Form -->
                <form id="pitch-create-form" action="{{ route('projects.pitches.store', ['project' => $project->slug]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $project->id }}">

                    <div class="bg-gradient-to-br from-green-50/80 to-emerald-50/80 backdrop-blur-sm border border-green-200/50 rounded-2xl p-6">
                        <div class="flex items-start">
                            <div class="flex items-center h-6 mt-1">
                                <input id="agree_terms" name="agree_terms" type="checkbox"
                                    class="w-5 h-5 text-green-600 bg-white border-green-300 rounded focus:ring-green-500 focus:ring-2 transition-all duration-200">
                            </div>
                            <div class="ml-4">
                                <label for="agree_terms" class="text-base font-medium text-green-800 cursor-pointer">
                                    I agree to the Terms and Conditions
                                </label>
                                <p class="text-sm text-green-600 mt-1">
                                    By checking this box, you confirm that you have read and agree to our terms of service.
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gradient-to-r from-gray-50/80 to-gray-100/80 backdrop-blur-sm border-t border-white/20">
                <div class="flex flex-col sm:flex-row gap-4 sm:justify-end">
                    <button type="button" onclick="closePitchTermsModal()"
                        class="px-6 py-3 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </button>
                    <button type="button" onclick="submitPitchForm()"
                        class="px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-bold transition-all duration-200 transform hover:scale-105 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <i class="fas fa-rocket mr-2"></i>
                        Start My Pitch
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@if($project->license_template_id && $project->licenseTemplate)
<!-- Project License Preview Modal -->
<div id="project-license-modal" class="fixed inset-0 z-[9999] hidden overflow-y-auto" 
     aria-labelledby="license-modal-title" role="dialog" aria-modal="true" 
     style="z-index: 10000;">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeProjectLicenseModal()"></div>
        
        <!-- Spacer element to center modal -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="license-modal-title">{{ $project->licenseTemplate->name }}</h3>
                    <button type="button" onclick="closeProjectLicenseModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="mb-4">
                    <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">{{ $project->licenseTemplate->category_name ?? 'License' }}</span>
                    @if($project->licenseTemplate->use_case)
                        <span class="inline-block bg-blue-100 text-blue-600 text-xs px-2 py-1 rounded-full ml-1">{{ $project->licenseTemplate->use_case_name }}</span>
                    @endif
                </div>
                
                <div class="max-h-96 overflow-y-auto">
                    <div class="text-sm text-gray-700 whitespace-pre-line border rounded-lg p-4 bg-gray-50">
                        {!! nl2br(e($project->licenseTemplate->generateLicenseContent())) !!}
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeProjectLicenseModal()" 
                        class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<script>
    // Make sure scripts only load once
    if (typeof window.pitchTermsModalInitialized === 'undefined') {
        window.pitchTermsModalInitialized = true;

        document.addEventListener('DOMContentLoaded', function () {
            // Define functions in global scope
            window.openPitchTermsModal = function () {
                const modal = document.getElementById('pitch-terms-modal');
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                
                // Add entrance animation
                setTimeout(() => {
                    const modalContent = modal.querySelector('.relative');
                    modalContent.style.transform = 'scale(1)';
                    modalContent.style.opacity = '1';
                }, 10);
            };

            window.closePitchTermsModal = function () {
                const modal = document.getElementById('pitch-terms-modal');
                const modalContent = modal.querySelector('.relative');
                
                // Add exit animation
                modalContent.style.transform = 'scale(0.95)';
                modalContent.style.opacity = '0';
                
                setTimeout(() => {
                    modal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                    // Reset for next time
                    modalContent.style.transform = 'scale(0.95)';
                    modalContent.style.opacity = '0';
                }, 200);
            };

            window.submitPitchForm = function () {
                const checkbox = document.getElementById('agree_terms');
                const submitButton = event.target;
                
                if (!checkbox.checked) {
                    // Enhanced error feedback
                    const checkboxContainer = checkbox.closest('.bg-gradient-to-br');
                    checkboxContainer.classList.add('ring-2', 'ring-red-500', 'ring-opacity-50');
                    
                    // Show error message
                    let errorMsg = checkboxContainer.querySelector('.error-message');
                    if (!errorMsg) {
                        errorMsg = document.createElement('p');
                        errorMsg.className = 'error-message text-sm text-red-600 mt-2 flex items-center';
                        errorMsg.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Please agree to the Terms and Conditions to continue.';
                        checkboxContainer.appendChild(errorMsg);
                    }
                    
                    // Remove error styling after a few seconds
                    setTimeout(() => {
                        checkboxContainer.classList.remove('ring-2', 'ring-red-500', 'ring-opacity-50');
                        if (errorMsg) errorMsg.remove();
                    }, 3000);
                    
                    return;
                }

                // Add loading state to button
                const originalContent = submitButton.innerHTML;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Starting Pitch...';
                submitButton.disabled = true;
                
                // Submit the form
                document.getElementById('pitch-create-form').submit();
            };

            // Close modal when clicking outside or pressing Escape key
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    window.closePitchTermsModal();
                }
            });

            // Add event listener to backdrop for closing modal when clicked
            document.addEventListener('click', function (event) {
                const modal = document.getElementById('pitch-terms-modal');
                if (event.target === modal) {
                    window.closePitchTermsModal();
                }
            });
            
            // Initialize modal content styling
            const modalContent = document.querySelector('#pitch-terms-modal .relative');
            if (modalContent) {
                modalContent.style.transform = 'scale(0.95)';
                modalContent.style.opacity = '0';
                modalContent.style.transition = 'all 0.2s ease-out';
            }

            // Project License Modal functions
            window.openProjectLicenseModal = function () {
                const modal = document.getElementById('project-license-modal');
                if (modal) {
                    modal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                }
            };

            window.closeProjectLicenseModal = function () {
                const modal = document.getElementById('project-license-modal');
                if (modal) {
                    modal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            };
        });
    }
</script>