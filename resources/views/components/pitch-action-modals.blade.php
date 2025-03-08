<div>
    <!-- People find pleasure in different ways. I find it in keeping my mind clear. - Marcus Aurelius -->
</div>

<!-- Approve Modal -->
<div id="approveModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
    <div class="flex items-center justify-center h-full">
        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 md:mx-0 z-10 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Confirm Approval</h3>
            </div>

            <div class="p-6">
                <p class="text-gray-700 mb-4">Are you sure you want to approve this pitch?</p>
            </div>

            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                <button type="button" onclick="closeModal('approveModal')"
                    class="py-2 px-4 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </button>

                <button type="button" id="approveSubmitBtn"
                    class="py-2 px-4 border border-transparent rounded-md text-sm font-medium text-white bg-success hover:bg-success/80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Approve
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Deny Modal -->
<div id="denyModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
    <div class="flex items-center justify-center h-full">
        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 md:mx-0 z-10 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Confirm Denial</h3>
            </div>

            <div class="p-6">
                <p class="text-gray-700 mb-4">Are you sure you want to deny this pitch?</p>
                <div class="mb-4">
                    <label for="denyReason" class="block text-sm font-medium text-gray-700 mb-1">Reason for
                        denial</label>
                    <textarea id="denyReason"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        rows="3" placeholder="Please explain why you are denying this pitch..."></textarea>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                <button type="button" onclick="closeModal('denyModal')"
                    class="py-2 px-4 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </button>

                <button type="button" id="denySubmitBtn"
                    class="py-2 px-4 border border-transparent rounded-md text-sm font-medium text-white bg-error hover:bg-error/80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Deny
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Request Revisions Modal -->
<div id="revisionsModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
    <div class="flex items-center justify-center h-full">
        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 md:mx-0 z-10 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Request Revisions</h3>
            </div>

            <div class="p-6">
                <p class="text-gray-700 mb-4">What revisions would you like to request for this pitch?</p>
                <div class="mb-4">
                    <label for="revisionsRequested" class="block text-sm font-medium text-gray-700 mb-1">Requested
                        Revisions</label>
                    <textarea id="revisionsRequested"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        rows="3"
                        placeholder="Please specify what revisions you'd like to see in this pitch..."></textarea>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                <button type="button" onclick="closeModal('revisionsModal')"
                    class="py-2 px-4 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </button>

                <button type="button" id="revisionsSubmitBtn"
                    class="py-2 px-4 border border-transparent rounded-md text-sm font-medium text-white bg-info hover:bg-info/80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Submit Request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include the shared JS file -->
<script src="{{ asset('js/pitch-modals.js') }}"></script>