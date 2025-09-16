<div x-data="globalUploader()" x-cloak>
    <div x-show="isVisible" 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="transform translate-y-full opacity-0" 
         x-transition:enter-end="transform translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="transform translate-y-0 opacity-100"
         x-transition:leave-end="transform translate-y-full opacity-0"
         class="fixed bottom-0 right-0 z-50 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-t border-gray-200/50 dark:border-gray-700/50 shadow-lg overflow-visible"
         :class="{
             'left-0 lg:left-64': document.querySelector('[data-flux-sidebar]') && window.innerWidth >= 1024,
             'left-0': !document.querySelector('[data-flux-sidebar]') || window.innerWidth < 1024
         }"
         style="bottom: var(--global-audio-player-offset, 0px)"
         id="global-file-uploader">

        <!-- Mini Bar -->
        <div class="px-4 py-3" x-show="!isExpanded" 
             x-on:dragover.prevent 
             x-on:dragenter.prevent 
             x-on:drop.prevent="(e) => { const files = Array.from(e.dataTransfer.files || []); const meta = window.GlobalUploader?.getActiveMeta?.(); if (files.length && meta) { window.GlobalUploader?.addFiles(files, meta); isVisible = true; } }">
            <div class="flex items-center justify-between gap-4 mx-auto">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-upload text-white text-sm"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                            <span>Uploads</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-2" x-text="summary()"></span>
                        </h4>
                        <div class="h-1.5 bg-gray-200 dark:bg-gray-700 rounded overflow-hidden mt-1 w-full">
                            <div class="h-full bg-blue-600 dark:bg-blue-500" :style="`width: ${aggregateProgress}%`"></div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <template x-if="hasActiveUploads()">
                        <div class="flex items-center gap-2">
                            <flux:button size="xs" variant="ghost" x-on:click="pauseOrResumeAll()" x-text="isPaused ? 'Resume' : 'Pause'"></flux:button>
                            <flux:button size="xs" variant="ghost" x-on:click="cancelAll()">Cancel</flux:button>
                        </div>
                    </template>
                    <flux:button size="xs" variant="primary" x-on:click="isExpanded = true">Expand</flux:button>
                    <flux:button size="xs" variant="ghost" x-on:click="isVisible = false" title="Hide uploader"><i class="fas fa-times"></i></flux:button>
                </div>
            </div>
        </div>

        <!-- Full View -->
        <div x-show="isExpanded" class="p-4">
            <div class="flex items-center justify-between mb-3">
                <flux:heading size="sm">Upload Queue</flux:heading>
                <div class="flex items-center gap-2">
                    <template x-if="hasActiveUploads()">
                        <flux:button size="xs" variant="ghost" x-on:click="pauseOrResumeAll()" x-text="isPaused ? 'Resume All' : 'Pause All'"></flux:button>
                    </template>
                    <flux:button size="xs" variant="ghost" x-on:click="retryFailed()">Retry Failed</flux:button>
                    <flux:button size="xs" variant="ghost" x-on:click="clearCompleted()">Clear Completed</flux:button>
                    <flux:button size="xs" variant="primary" x-on:click="isExpanded = false">Close</flux:button>
                </div>
            </div>

            <div class="space-y-2 max-h-64 overflow-auto pr-1" aria-live="polite" aria-busy="false">
                <template x-for="item in (queue || [])" :key="item.id || Math.random()">
                    <div class="flex items-center justify-between gap-3 p-2 rounded border border-gray-200 dark:border-gray-700 dark:bg-gray-800/50">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="item.name"></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="formatSize(item.size)"></span>
                                <span class="text-xs text-blue-600 dark:text-blue-400 font-medium" x-text="getTargetDisplayName(item)"></span>
                            </div>
                            <div class="h-1.5 w-full bg-gray-200 dark:bg-gray-700 rounded overflow-hidden mt-1">
                                <div class="h-full bg-blue-600 dark:bg-blue-500" :style="`width: ${item.progress || 0}%`"></div>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="statusText(item)"></div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <flux:button size="xs" variant="ghost" x-on:click="togglePause(item)" x-show="item.status === 'uploading' || item.status === 'queued' || item.status === 'paused'">
                                <span x-text="item.paused ? 'Resume' : 'Pause'"></span>
                            </flux:button>
                            <flux:button size="xs" variant="ghost" x-on:click="retry(item)" x-show="item.error">Retry</flux:button>
                            <flux:button size="xs" variant="ghost" x-on:click="cancel(item)" x-show="item.status === 'uploading' || item.status === 'queued' || item.status === 'paused'">Cancel</flux:button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Uppy UI Targets -->
            <div class="mt-4">
                <div id="global-uppy-progress" class="mb-2"></div>
                <div id="global-uppy-status"></div>
            </div>
        </div>
    </div>

    <script>
        function globalUploader() {
            return {
                isVisible: false,
                isExpanded: false,
                isPaused: false,
                queue: [],
                aggregateProgress: 0,

                init() {
                    if (window.GlobalUploader && typeof window.GlobalUploader.attachLivewire === 'function') {
                        window.GlobalUploader.attachLivewire(@this);
                    }
                    window.addEventListener('global-uploader:update', (e) => {
                        const s = e.detail || {};
                        // Replace the array reference to ensure Alpine updates DOM
                        const newQueue = Array.isArray(s.queue) ? s.queue : [];
                        
                        // Ensure all queue items have valid IDs
                        const validQueue = newQueue.filter(item => item && item.id).map((item, index) => ({
                            ...item,
                            id: item.id || `temp-${Date.now()}-${index}`
                        }));
                        
                        this.queue = validQueue;
                        this.aggregateProgress = s.aggregateProgress || 0;
                        this.isVisible = (this.queue.length > 0);
                        this.isPaused = !!s.isPaused;
                    });
                },


                pauseOrResumeAll() {
                    if (window.GlobalUploader) {
                        this.isPaused ? window.GlobalUploader.resumeAll() : window.GlobalUploader.pauseAll();
                    }
                },
                cancelAll() {
                    window.GlobalUploader?.cancelAll();
                },
                retryFailed() {
                    window.GlobalUploader?.retryFailed();
                },
                clearCompleted() {
                    window.GlobalUploader?.clearCompleted();
                },
                togglePause(item) {
                    if (item && item.id && window.GlobalUploader) {
                        window.GlobalUploader.togglePause(item.id);
                    }
                },
                retry(item) {
                    if (item && item.id && window.GlobalUploader) {
                        window.GlobalUploader.retry(item.id);
                    }
                },
                cancel(item) {
                    window.GlobalUploader?.cancel(item.id);
                },
                summary() {
                    const total = this.queue.length;
                    const uploading = this.queue.filter(i => i.status === 'uploading').length;
                    const done = this.queue.filter(i => i.status === 'complete').length;
                    const failed = this.queue.filter(i => i.status === 'error').length;
                    return `${total} files • ${uploading} uploading • ${done} done • ${failed} failed`;
                },
                hasActiveUploads() {
                    return this.queue.some(i => i.status === 'uploading' || i.status === 'queued' || i.status === 'paused');
                },
                formatSize(bytes) {
                    if (bytes >= 1024 * 1024 * 1024) return (bytes / (1024*1024*1024)).toFixed(1) + 'GB';
                    if (bytes >= 1024 * 1024) return (bytes / (1024*1024)).toFixed(1) + 'MB';
                    if (bytes >= 1024) return (bytes / 1024).toFixed(1) + 'KB';
                    return bytes + 'B';
                },
                statusText(item) {
                    if (item.status === 'uploading') return `Uploading… ${Math.round(item.progress||0)}%`;
                    if (item.status === 'complete') return 'Completed';
                    if (item.status === 'error') return item.error || 'Failed';
                    if (item.status === 'paused') return 'Paused';
                    return item.status || 'Queued';
                },
                getTargetDisplayName(item) {
                    if (!item.meta) return '';
                    
                    const meta = item.meta;
                    
                    // For projects
                    if (meta.modelLabel === 'Project' || meta.modelType === 'App\\Models\\Project') {
                        if (meta.projectTitle) {
                            return `→ ${meta.projectTitle}`;
                        }
                        return '→ Project';
                    }
                    
                    // For pitches
                    if (meta.modelLabel === 'Pitch' || meta.modelType === 'App\\Models\\Pitch') {
                        if (meta.pitchTitle) {
                            return `→ ${meta.pitchTitle}`;
                        }
                        return '→ Pitch';
                    }
                    
                    // For orders
                    if (meta.modelLabel === 'Order' || meta.modelType === 'App\\Models\\Order') {
                        if (meta.itemName) {
                            return `→ ${meta.itemName}`;
                        }
                        return '→ Order';
                    }
                    
                    // For services
                    if (meta.modelLabel === 'Service' || meta.modelType === 'App\\Models\\ServicePackage') {
                        if (meta.itemName) {
                            return `→ ${meta.itemName}`;
                        }
                        return '→ Service';
                    }
                    
                    // Fallback - show whatever information we have
                    if (meta.projectTitle) return `→ ${meta.projectTitle}`;
                    if (meta.pitchTitle) return `→ ${meta.pitchTitle}`;
                    if (meta.itemName) return `→ ${meta.itemName}`;
                    return meta.modelLabel ? `→ ${meta.modelLabel}` : '';
                },
            }
        }
    </script>
</div>
