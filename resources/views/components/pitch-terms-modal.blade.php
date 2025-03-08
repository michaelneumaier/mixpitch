@props(['project'])

<!-- Terms of Service Modal -->
<div id="pitch-terms-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full mx-auto z-10 overflow-hidden">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-2xl font-semibold text-gray-800">Start Your Pitch for "{{ $project->name }}"</h3>
                    <button type="button" onclick="closePitchTermsModal()" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <div class="mb-6">
                    <h4 class="text-lg font-medium mb-2">Welcome to your creative journey!</h4>
                    <p class="text-gray-600 mb-4">
                        You're about to start a pitch for this project. Before you begin, please review our terms:
                    </p>

                    <div class="bg-gray-50 p-4 rounded-lg mb-4 text-sm">
                        <h5 class="font-semibold mb-2">Terms of Service Highlights:</h5>
                        <ul class="list-disc pl-5 space-y-2">
                            <li>You retain ownership of your original work.</li>
                            <li>If your pitch is selected, you grant the project owner a license as specified in the
                                project details.</li>
                            <li>Respect copyright and intellectual property rights in your submissions.</li>
                            <li>Be respectful and professional in all communications.</li>
                            <li>We may remove content that violates our community standards.</li>
                        </ul>
                        <p class="mt-3">
                            For a complete understanding, please review our full <a href="/terms" target="_blank"
                                class="text-blue-600 hover:underline">Terms and Conditions</a>.
                        </p>
                    </div>
                </div>

                <form id="pitch-create-form" action="{{ route('pitches.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $project->id }}">

                    <div class="flex items-start mb-6">
                        <div class="flex items-center h-5">
                            <input id="agree_terms" name="agree_terms" type="checkbox"
                                class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="agree_terms" class="font-medium text-gray-700">I agree to the Terms and
                                Conditions</label>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-gray-50 flex justify-end">
                <button type="button" onclick="closePitchTermsModal()"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none mr-3">
                    Cancel
                </button>
                <button type="button" onclick="submitPitchForm()"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-accent hover:bg-accent-focus focus:outline-none">
                    Start My Pitch
                </button>
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
                document.getElementById('pitch-terms-modal').classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            };

            window.closePitchTermsModal = function () {
                document.getElementById('pitch-terms-modal').classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            };

            window.submitPitchForm = function () {
                const checkbox = document.getElementById('agree_terms');
                if (!checkbox.checked) {
                    alert('Please agree to the Terms and Conditions to continue.');
                    return;
                }

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
        });
    }
</script>