<div>
    <!-- People find pleasure in different ways. I find it in keeping my mind clear. - Marcus Aurelius -->
</div>

<!-- Approve Modal -->
<div id="approveModal" class="fixed inset-0 z-[100] hidden">
    <!-- Enhanced backdrop with blur -->
    <div class="fixed inset-0 bg-gradient-to-br from-black/60 via-black/50 to-black/60 backdrop-blur-sm transition-opacity duration-300"></div>
    
    <!-- Modal positioning -->
    <div class="flex items-center justify-center h-full p-4">
        <!-- Modern modal container with glass morphism -->
        <div class="relative bg-white/95 backdrop-blur-lg border border-white/30 rounded-2xl shadow-2xl max-w-lg w-full mx-4 md:mx-0 z-[110] overflow-hidden transform transition-all duration-300">
            <!-- Background effects -->
            <div class="absolute inset-0 bg-gradient-to-br from-green-50/40 via-white/20 to-emerald-50/40"></div>
            <div class="absolute top-4 right-4 w-20 h-20 bg-green-400/10 rounded-full blur-xl"></div>
            
            <!-- Header -->
            <div class="relative px-6 py-5 border-b border-white/20 bg-gradient-to-r from-green-50/50 to-emerald-50/50 backdrop-blur-sm">
                <h3 class="text-xl font-bold bg-gradient-to-r from-green-700 to-emerald-700 bg-clip-text text-transparent flex items-center">
                    <i class="fas fa-check-circle mr-3 text-green-600"></i>
                    Confirm Approval
                </h3>
                <p class="text-sm text-gray-600 mt-1">This action will approve the current snapshot</p>
            </div>

            <!-- Content -->
            <div class="relative p-6">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-green-100 to-emerald-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-thumbs-up text-green-600 text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-700 font-medium mb-2">Are you sure you want to approve this pitch?</p>
                        <p class="text-sm text-gray-500">This will mark the current snapshot as accepted and notify the producer.</p>
                    </div>
                </div>
                
                <form id="approveForm" method="POST" class="hidden">
                    @csrf
                    <!-- Form fields will be added via JavaScript -->
                </form>
            </div>

            <!-- Actions -->
            <div class="relative px-6 py-4 bg-gradient-to-r from-gray-50/80 to-white/80 backdrop-blur-sm border-t border-white/20 flex justify-end space-x-3">
                <button type="button" onclick="closeModal('approveModal')"
                    class="px-4 py-2 bg-white/80 hover:bg-white border border-gray-200 hover:border-gray-300 text-gray-700 hover:text-gray-800 rounded-xl font-medium transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:shadow-md backdrop-blur-sm">
                    Cancel
                </button>

                <button type="button" id="approveSubmitBtn"
                    class="px-6 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-semibold transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:shadow-lg">
                    <i class="fas fa-check mr-2"></i>Approve Pitch
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Deny Modal -->
<div id="denyModal" class="fixed inset-0 z-[100] hidden">
    <!-- Enhanced backdrop with blur -->
    <div class="fixed inset-0 bg-gradient-to-br from-black/60 via-black/50 to-black/60 backdrop-blur-sm transition-opacity duration-300"></div>
    
    <!-- Modal positioning -->
    <div class="flex items-center justify-center h-full p-4">
        <!-- Modern modal container with glass morphism -->
        <div class="relative bg-white/95 backdrop-blur-lg border border-white/30 rounded-2xl shadow-2xl max-w-lg w-full mx-4 md:mx-0 z-[110] overflow-hidden transform transition-all duration-300">
            <!-- Background effects -->
            <div class="absolute inset-0 bg-gradient-to-br from-red-50/40 via-white/20 to-pink-50/40"></div>
            <div class="absolute top-4 right-4 w-20 h-20 bg-red-400/10 rounded-full blur-xl"></div>
            
            <!-- Header -->
            <div class="relative px-6 py-5 border-b border-white/20 bg-gradient-to-r from-red-50/50 to-pink-50/50 backdrop-blur-sm">
                <h3 class="text-xl font-bold bg-gradient-to-r from-red-700 to-pink-700 bg-clip-text text-transparent flex items-center">
                    <i class="fas fa-times-circle mr-3 text-red-600"></i>
                    Confirm Denial
                </h3>
                <p class="text-sm text-gray-600 mt-1">Please provide a reason for denying this pitch</p>
            </div>

            <!-- Content -->
            <div class="relative p-6">
                <div class="flex items-start space-x-4 mb-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-red-100 to-pink-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-700 font-medium mb-2">Are you sure you want to deny this pitch?</p>
                        <p class="text-sm text-gray-500">This action will reject the current snapshot and require feedback.</p>
                    </div>
                </div>
                
                <form id="denyForm" method="POST" class="hidden">
                    @csrf
                    <!-- Form fields will be added via JavaScript -->
                </form>
                
                <div class="space-y-2">
                    <label for="denyReason" class="block text-sm font-semibold text-gray-700">
                        Reason for denial <span class="text-red-500">*</span>
                    </label>
                    <textarea id="denyReason"
                        class="w-full rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm shadow-sm focus:border-red-300 focus:ring-2 focus:ring-red-200 transition-all duration-200 resize-none"
                        rows="4" 
                        placeholder="Please explain why you are denying this pitch. This feedback will help the producer understand what needs to be improved."></textarea>
                    <p class="text-xs text-gray-500">Minimum 10 characters required</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="relative px-6 py-4 bg-gradient-to-r from-gray-50/80 to-white/80 backdrop-blur-sm border-t border-white/20 flex justify-end space-x-3">
                <button type="button" onclick="closeModal('denyModal')"
                    class="px-4 py-2 bg-white/80 hover:bg-white border border-gray-200 hover:border-gray-300 text-gray-700 hover:text-gray-800 rounded-xl font-medium transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:shadow-md backdrop-blur-sm">
                    Cancel
                </button>

                <button type="button" id="denySubmitBtn"
                    class="px-6 py-2 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white rounded-xl font-semibold transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:shadow-lg">
                    <i class="fas fa-times mr-2"></i>Deny Pitch
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Request Revisions Modal -->
<div id="revisionsModal" class="fixed inset-0 z-[100] hidden">
    <!-- Enhanced backdrop with blur -->
    <div class="fixed inset-0 bg-gradient-to-br from-black/60 via-black/50 to-black/60 backdrop-blur-sm transition-opacity duration-300"></div>
    
    <!-- Modal positioning -->
    <div class="flex items-center justify-center h-full p-4">
        <!-- Modern modal container with glass morphism -->
        <div class="relative bg-white/95 backdrop-blur-lg border border-white/30 rounded-2xl shadow-2xl max-w-lg w-full mx-4 md:mx-0 z-[110] overflow-hidden transform transition-all duration-300">
            <!-- Background effects -->
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50/40 via-white/20 to-indigo-50/40"></div>
            <div class="absolute top-4 right-4 w-20 h-20 bg-blue-400/10 rounded-full blur-xl"></div>
            
            <!-- Header -->
            <div class="relative px-6 py-5 border-b border-white/20 bg-gradient-to-r from-blue-50/50 to-indigo-50/50 backdrop-blur-sm">
                <h3 class="text-xl font-bold bg-gradient-to-r from-blue-700 to-indigo-700 bg-clip-text text-transparent flex items-center">
                    <i class="fas fa-edit mr-3 text-blue-600"></i>
                    Request Revisions
                </h3>
                <p class="text-sm text-gray-600 mt-1">Provide specific feedback to help improve the pitch</p>
            </div>

            <!-- Content -->
            <div class="relative p-6">
                <div class="flex items-start space-x-4 mb-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-comments text-blue-600 text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-700 font-medium mb-2">What revisions would you like to request?</p>
                        <p class="text-sm text-gray-500">Provide constructive feedback to help the producer deliver exactly what you're looking for.</p>
                    </div>
                </div>
                
                <form id="revisionsForm" method="POST" class="hidden">
                    @csrf
                    <!-- Form fields will be added via JavaScript -->
                </form>
                
                <div class="space-y-2">
                    <label for="revisionsRequested" class="block text-sm font-semibold text-gray-700">
                        Requested Revisions <span class="text-blue-500">*</span>
                    </label>
                    <textarea id="revisionsRequested"
                        class="w-full rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm shadow-sm focus:border-blue-300 focus:ring-2 focus:ring-blue-200 transition-all duration-200 resize-none"
                        rows="4"
                        placeholder="Please specify what revisions you'd like to see. Be specific about what changes are needed, what you liked, and any additional requirements."></textarea>
                    <p class="text-xs text-gray-500">Minimum 10 characters required</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="relative px-6 py-4 bg-gradient-to-r from-gray-50/80 to-white/80 backdrop-blur-sm border-t border-white/20 flex justify-end space-x-3">
                <button type="button" onclick="closeModal('revisionsModal')"
                    class="px-4 py-2 bg-white/80 hover:bg-white border border-gray-200 hover:border-gray-300 text-gray-700 hover:text-gray-800 rounded-xl font-medium transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:shadow-md backdrop-blur-sm">
                    Cancel
                </button>

                <button type="button" id="revisionsSubmitBtn"
                    class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-semibold transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:shadow-lg">
                    <i class="fas fa-paper-plane mr-2"></i>Send Request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include the shared JS file -->
<script src="{{ asset('js/pitch-modals.js') }}"></script>