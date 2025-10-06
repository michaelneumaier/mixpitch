@props(['snapshotHistory'])

<div x-data="versionComparison" class="version-comparison-container">
    {{-- Version Comparison Section --}}
    <div id="version-comparison" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-50 overflow-y-auto" style="display: none;">
        <div class="min-h-screen flex items-start justify-center p-4 pt-8">
            <div class="w-full max-w-6xl" id="comparison-content">
                {{-- Comparison content will be loaded here --}}
            </div>
        </div>
    </div>

    {{-- Hidden data script --}}
    <script type="application/json" id="snapshot-data-json">@json($snapshotHistory)</script>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('versionComparison', () => ({
    showComparison: false,
    selectedSnapshots: [],
    snapshotData: null,

    init() {
        // Initialize snapshot data from JSON script
        const dataElement = document.getElementById('snapshot-data-json');
        if (dataElement) {
            this.snapshotData = JSON.parse(dataElement.textContent);
        }

        // Listen for checkbox changes
        document.addEventListener('change', (e) => {
            if (e.target.matches('.comparison-checkbox')) {
                this.updateComparison();
            }
        });

        // Set up global functions for backward compatibility
        window.toggleVersionComparison = () => this.toggleComparison();
        window.hideVersionComparison = () => this.hideComparison();
        window.selectSnapshot = (id) => this.selectSnapshot(id);
    },

    toggleComparison() {
        const checkboxes = document.querySelectorAll('.comparison-checkbox');
        checkboxes.forEach(cb => cb.classList.toggle('hidden'));

        if (checkboxes[0] && checkboxes[0].classList.contains('hidden')) {
            this.hideComparison();
        }
    },

    hideComparison() {
        const checkboxes = document.querySelectorAll('.comparison-checkbox');
        const modalOverlay = document.getElementById('version-comparison');

        checkboxes.forEach(cb => {
            cb.classList.add('hidden');
            cb.checked = false;
        });
        this.showComparison = false;
        this.selectedSnapshots = [];

        // Manually hide the modal
        if (modalOverlay) {
            modalOverlay.style.display = 'none';
        }
    },

    selectSnapshot(snapshotId) {
        // Only navigate if not in comparison mode
        const checkboxes = document.querySelectorAll('.comparison-checkbox');
        if (checkboxes[0] && checkboxes[0].classList.contains('hidden')) {
            // In preview mode, do not navigate
            if (typeof window.isPortalPreview !== 'undefined' && window.isPortalPreview) {
                console.log('Preview mode: Snapshot navigation disabled');
                return;
            }

            const snapshotElement = document.querySelector(`[data-snapshot-id="${snapshotId}"]`);
            if (snapshotElement && snapshotElement.dataset.snapshotUrl) {
                window.location.href = snapshotElement.dataset.snapshotUrl;
            } else if (snapshotId === 'current') {
                const deliverables = document.getElementById('producer-deliverables');
                if (deliverables) {
                    deliverables.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } else {
                console.warn('No signed URL found for snapshot:', snapshotId);
            }
        }
    },

    updateComparison() {
        const checkedBoxes = document.querySelectorAll('.comparison-checkbox:checked');
        const comparisonContent = document.getElementById('comparison-content');
        const modalOverlay = document.getElementById('version-comparison');

        this.selectedSnapshots = Array.from(checkedBoxes).map(cb => cb.dataset.snapshotId);

        if (this.selectedSnapshots.length === 2) {
            this.showComparison = true;

            // Manually show the modal
            if (modalOverlay) {
                modalOverlay.style.display = 'block';
            }

            comparisonContent.innerHTML = '<div class="flex items-center justify-center py-8"><div class="text-blue-600">Loading comparison...</div></div>';

            // Find both snapshots
            const snapshot1 = this.snapshotData.find(s => s.id == this.selectedSnapshots[0]);
            const snapshot2 = this.snapshotData.find(s => s.id == this.selectedSnapshots[1]);

            if (snapshot1 && snapshot2) {
                // Sort snapshots by version number (old to new)
                // Lower version = older snapshot (left side)
                // Higher version = newer snapshot (right side)
                const oldSnapshot = snapshot1.version < snapshot2.version ? snapshot1 : snapshot2;
                const newSnapshot = snapshot1.version < snapshot2.version ? snapshot2 : snapshot1;

                const html = this.buildComparisonView(oldSnapshot, newSnapshot);
                comparisonContent.innerHTML = html;
            } else {
                comparisonContent.innerHTML = `
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-2">Error</h2>
                        <p class="text-gray-600 dark:text-gray-400">Could not find the selected versions for comparison.</p>
                    </div>
                `;
            }
        } else if (this.selectedSnapshots.length > 2) {
            // Limit to 2 selections
            checkedBoxes[checkedBoxes.length - 1].checked = false;
            this.selectedSnapshots.pop();
        } else {
            this.showComparison = false;

            // Manually hide the modal
            if (modalOverlay) {
                modalOverlay.style.display = 'none';
            }
        }
    },

    buildComparisonView(leftSnapshot, rightSnapshot) {
        return `
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="bg-gradient-to-r from-blue-50 to-green-50 dark:from-blue-900/20 dark:to-green-900/20 border-b border-gray-200 dark:border-gray-700 rounded-t-xl p-4 -m-6 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-2">Version Comparison</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Comparing Version ${leftSnapshot.version}
                                <span class="mx-2 text-blue-500">vs</span>
                                Version ${rightSnapshot.version}
                            </p>
                        </div>
                        <button onclick="window.hideVersionComparison()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="mt-3 flex items-center space-x-4 text-sm">
                        ${this.buildDifferencesSummary(leftSnapshot, rightSnapshot)}
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- File Differences Section -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            File Changes
                        </h3>
                        ${this.buildFileDiffSection(leftSnapshot, rightSnapshot)}
                    </div>

                    <!-- Version Details Side by Side -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        ${this.buildSnapshotColumn(leftSnapshot, 'left', leftSnapshot, rightSnapshot)}
                        ${this.buildSnapshotColumn(rightSnapshot, 'right', leftSnapshot, rightSnapshot)}
                    </div>
                </div>
            </div>
        `;
    },

    buildDifferencesSummary(left, right) {
        const leftFiles = left.files || [];
        const rightFiles = right.files || [];
        
        const added = rightFiles.filter(rf => !leftFiles.find(lf => lf.id === rf.id)).length;
        const removed = leftFiles.filter(lf => !rightFiles.find(rf => rf.id === lf.id)).length;
        const modified = leftFiles.filter(lf => {
            const rf = rightFiles.find(rf => rf.id === lf.id);
            return rf && (rf.file_name !== lf.file_name || rf.size !== lf.size);
        }).length;

        return `
            <div class="flex items-center space-x-4 text-xs">
                ${added > 0 ? `<span class="bg-green-100 text-green-800 px-2 py-1 rounded-md">+${added} added</span>` : ''}
                ${removed > 0 ? `<span class="bg-red-100 text-red-800 px-2 py-1 rounded-md">-${removed} removed</span>` : ''}
                ${modified > 0 ? `<span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-md">${modified} modified</span>` : ''}
                ${added === 0 && removed === 0 && modified === 0 ? '<span class="text-gray-500">No file changes</span>' : ''}
            </div>
        `;
    },

    buildFileDiffSection(left, right) {
        const leftFiles = left.files || [];
        const rightFiles = right.files || [];

        // Get all unique files
        const allFileIds = [...new Set([...leftFiles.map(f => f.id), ...rightFiles.map(f => f.id)])];

        if (allFileIds.length === 0) {
            return '<p class="text-center py-4 text-gray-600 dark:text-gray-400">No files in either version</p>';
        }

        let html = '<div class="space-y-3">';

        allFileIds.forEach(fileId => {
            const leftFile = leftFiles.find(f => f.id === fileId);
            const rightFile = rightFiles.find(f => f.id === fileId);

            if (!leftFile && rightFile) {
                // Added file
                html += `
                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border-l-4 border-green-500">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">${rightFile.file_name}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Added • ${(rightFile.size / 1024).toFixed(1)} KB</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Added</span>
                    </div>
                `;
            } else if (leftFile && !rightFile) {
                // Removed file
                html += `
                    <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border-l-4 border-red-500">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                            </svg>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">${leftFile.file_name}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Removed • ${(leftFile.size / 1024).toFixed(1)} KB</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Removed</span>
                    </div>
                `;
            } else if (leftFile && rightFile) {
                // File exists in both - check for changes
                const changed = leftFile.file_name !== rightFile.file_name || leftFile.size !== rightFile.size;
                const borderClass = changed ? 'border-l-4 border-yellow-500' : 'border-l-4 border-gray-300 dark:border-gray-700';
                const bgClass = changed ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-gray-50 dark:bg-gray-800/50';

                html += `
                    <div class="flex items-center justify-between p-3 ${bgClass} rounded-lg ${borderClass}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 ${changed ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-500 dark:text-gray-400'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">${rightFile.file_name}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    ${changed ? 'Modified • ' : 'Unchanged • '}${(rightFile.size / 1024).toFixed(1)} KB
                                </p>
                            </div>
                        </div>
                        ${changed ? '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Modified</span>' : '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300">Unchanged</span>'}
                    </div>
                `;
            }
        });

        html += '</div>';
        return html;
    },

    buildSnapshotColumn(snapshot, side, leftSnapshot, rightSnapshot) {
        const files = snapshot.files || [];
        const isLeft = side === 'left';

        return `
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-6">
                <div class="mb-4">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                        Version ${snapshot.version}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Created ${new Date(snapshot.submitted_at).toLocaleDateString()}
                        ${snapshot.response_to_feedback ? ' • ' + snapshot.response_to_feedback.substring(0, 50) + (snapshot.response_to_feedback.length > 50 ? '...' : '') : ''}
                    </p>
                </div>

                <div class="space-y-2">
                    ${files.length === 0 ?
                        '<p class="text-center py-4 text-gray-600 dark:text-gray-400">No files in this version</p>' :
                        files.map(file => `
                            <div class="flex items-center justify-between p-2 rounded-lg border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${file.file_name}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">${(file.size / 1024).toFixed(1)} KB</p>
                                    </div>
                                </div>
                            </div>
                        `).join('')
                    }
                </div>
            </div>
        `;
    }
    }));
});
</script>
@endpush