@props(['snapshotHistory'])

<div x-data="versionComparison" class="version-comparison-container">
    {{-- Version Comparison Section --}}
    <div id="version-comparison" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-50 hidden overflow-y-auto" x-show="showComparison" x-transition>
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
        checkboxes.forEach(cb => {
            cb.classList.add('hidden');
            cb.checked = false;
        });
        this.showComparison = false;
        this.selectedSnapshots = [];
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

        this.selectedSnapshots = Array.from(checkedBoxes).map(cb => cb.dataset.snapshotId);

        if (this.selectedSnapshots.length === 2) {
            this.showComparison = true;
            comparisonContent.innerHTML = '<div class="flex items-center justify-center py-8"><div class="text-blue-600">Loading comparison...</div></div>';

            const leftSnapshot = this.snapshotData.find(s => s.id == this.selectedSnapshots[0]);
            const rightSnapshot = this.snapshotData.find(s => s.id == this.selectedSnapshots[1]);

            if (leftSnapshot && rightSnapshot) {
                comparisonContent.innerHTML = this.buildComparisonView(leftSnapshot, rightSnapshot);
            } else {
                comparisonContent.innerHTML = `
                    <flux:card class="text-center">
                        <flux:icon.exclamation-triangle class="mx-auto mb-4 text-red-500" size="xl" />
                        <flux:heading size="lg" class="mb-2">Error</flux:heading>
                        <flux:text>Could not find the selected versions for comparison.</flux:text>
                    </flux:card>
                `;
            }
        } else if (this.selectedSnapshots.length > 2) {
            // Limit to 2 selections
            checkedBoxes[checkedBoxes.length - 1].checked = false;
            this.selectedSnapshots.pop();
        } else {
            this.showComparison = false;
        }
    },

    buildComparisonView(leftSnapshot, rightSnapshot) {
        return `
            <flux:card>
                <div class="bg-gradient-to-r from-blue-50 to-green-50 dark:from-blue-900/20 dark:to-green-900/20 border-b rounded-t-xl p-4 -m-6 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="lg" class="mb-2">Version Comparison</flux:heading>
                            <flux:subheading>
                                Comparing Version ${leftSnapshot.version} 
                                <span class="mx-2 text-blue-500">vs</span> 
                                Version ${rightSnapshot.version}
                            </flux:subheading>
                        </div>
                        <flux:button variant="ghost" @click="hideComparison()" size="sm">
                            <flux:icon.x-mark />
                        </flux:button>
                    </div>
                    
                    <div class="mt-3 flex items-center space-x-4 text-sm">
                        ${this.buildDifferencesSummary(leftSnapshot, rightSnapshot)}
                    </div>
                </div>
                
                <div class="space-y-6">
                    <!-- File Differences Section -->
                    <flux:card>
                        <flux:heading size="md" class="mb-4">
                            <flux:icon.document class="mr-2 text-blue-500" />
                            File Changes
                        </flux:heading>
                        ${this.buildFileDiffSection(leftSnapshot, rightSnapshot)}
                    </flux:card>
                    
                    <!-- Version Details Side by Side -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        ${this.buildSnapshotColumn(leftSnapshot, 'left', leftSnapshot, rightSnapshot)}
                        ${this.buildSnapshotColumn(rightSnapshot, 'right', leftSnapshot, rightSnapshot)}
                    </div>
                </div>
            </flux:card>
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
            return '<flux:text class="text-center py-4">No files in either version</flux:text>';
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
                            <flux:icon.plus class="text-green-600" />
                            <div>
                                <flux:text class="font-medium">${rightFile.file_name}</flux:text>
                                <flux:subheading>Added • ${(rightFile.size / 1024).toFixed(1)} KB</flux:subheading>
                            </div>
                        </div>
                        <flux:badge variant="success" size="sm">Added</flux:badge>
                    </div>
                `;
            } else if (leftFile && !rightFile) {
                // Removed file
                html += `
                    <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border-l-4 border-red-500">
                        <div class="flex items-center space-x-3">
                            <flux:icon.minus class="text-red-600" />
                            <div>
                                <flux:text class="font-medium">${leftFile.file_name}</flux:text>
                                <flux:subheading>Removed • ${(leftFile.size / 1024).toFixed(1)} KB</flux:subheading>
                            </div>
                        </div>
                        <flux:badge variant="danger" size="sm">Removed</flux:badge>
                    </div>
                `;
            } else if (leftFile && rightFile) {
                // File exists in both - check for changes
                const changed = leftFile.file_name !== rightFile.file_name || leftFile.size !== rightFile.size;
                const borderClass = changed ? 'border-l-4 border-yellow-500' : 'border-l-4 border-gray-300';
                const bgClass = changed ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-gray-50 dark:bg-gray-800/50';
                
                html += `
                    <div class="flex items-center justify-between p-3 ${bgClass} rounded-lg ${borderClass}">
                        <div class="flex items-center space-x-3">
                            <flux:icon.document class="${changed ? 'text-yellow-600' : 'text-gray-500'}" />
                            <div>
                                <flux:text class="font-medium">${rightFile.file_name}</flux:text>
                                <flux:subheading>
                                    ${changed ? 'Modified • ' : 'Unchanged • '}${(rightFile.size / 1024).toFixed(1)} KB
                                </flux:subheading>
                            </div>
                        </div>
                        ${changed ? '<flux:badge variant="warning" size="sm">Modified</flux:badge>' : '<flux:badge variant="ghost" size="sm">Unchanged</flux:badge>'}
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
            <flux:card>
                <div class="mb-4">
                    <flux:heading size="md">
                        Version ${snapshot.version}
                        ${isLeft ? '' : ''}
                    </flux:heading>
                    <flux:subheading>
                        Created ${new Date(snapshot.created_at).toLocaleDateString()}
                        ${snapshot.summary ? ' • ' + snapshot.summary.substring(0, 50) + (snapshot.summary.length > 50 ? '...' : '') : ''}
                    </flux:subheading>
                </div>
                
                <div class="space-y-2">
                    ${files.length === 0 ? 
                        '<flux:text class="text-center py-4">No files in this version</flux:text>' :
                        files.map(file => `
                            <div class="flex items-center justify-between p-2 rounded-lg border">
                                <div class="flex items-center space-x-2">
                                    <flux:icon.document class="text-blue-500" size="sm" />
                                    <div>
                                        <flux:text size="sm" class="font-medium">${file.file_name}</flux:text>
                                        <flux:subheading>${(file.size / 1024).toFixed(1)} KB</flux:subheading>
                                    </div>
                                </div>
                            </div>
                        `).join('')
                    }
                </div>
            </flux:card>
        `;
    }
    }));
});
</script>
@endpush