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
        });
    }
</script>