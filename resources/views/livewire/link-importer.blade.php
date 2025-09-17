<div>
    <!-- Import Button -->
    <flux:button wire:click="showImportModal" variant="outline" icon="link" class="w-full">
        Import from Link
    </flux:button>

    <!-- Active Import Progress Display -->
    @if ($importProgress['active'] && $activeImport)
        <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950">
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <flux:icon.cloud-arrow-down class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                    <flux:text weight="semibold" class="text-blue-900 dark:text-blue-100">
                        Importing files...
                    </flux:text>
                </div>
                <flux:button wire:click="cancelImport" variant="ghost" size="sm" class="text-blue-600">
                    Cancel
                </flux:button>
            </div>

            <!-- Progress Bar -->
            <div class="mb-3">
                <div class="mb-1 flex items-center justify-between text-sm">
                    <span class="text-blue-700 dark:text-blue-300">
                        {{ $importProgress['completed'] }} of {{ $importProgress['total'] }} files
                    </span>
                    <span class="text-blue-700 dark:text-blue-300">
                        {{ $this->progressPercentage }}%
                    </span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-blue-200 dark:bg-blue-800">
                    <div 
                        class="h-2 bg-blue-600 transition-all duration-300 ease-out"
                        style="width: {{ $this->progressPercentage }}%"
                    ></div>
                </div>
            </div>

            <!-- Current File -->
            @if ($importProgress['currentFile'])
                <flux:text size="sm" class="text-blue-600 dark:text-blue-400">
                    Currently importing: {{ $importProgress['currentFile'] }}
                </flux:text>
            @endif

            <!-- Import Errors -->
            @if ($this->hasImportErrors)
                <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950">
                    <div class="flex items-center gap-2 mb-2">
                        <flux:icon.exclamation-triangle class="h-4 w-4 text-amber-600" />
                        <flux:text size="sm" weight="medium" class="text-amber-800 dark:text-amber-200">
                            Some files couldn't be imported:
                        </flux:text>
                    </div>
                    <ul class="text-sm text-amber-700 dark:text-amber-300 space-y-1">
                        @foreach ($this->importErrors as $error)
                            <li>• {{ $error['filename'] }}: {{ $error['error'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <!-- Import Modal -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="hideImportModal"></div>

                <!-- Modal panel -->
                <div class="inline-block transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <flux:icon.link class="h-6 w-6 text-blue-600" />
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left flex-1">
                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                Import from Sharing Link
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Import files from WeTransfer, Google Drive, Dropbox, or OneDrive sharing links. The files will be downloaded and added to your project.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <form wire:submit.prevent="importFromLink">
                            <flux:field>
                                <flux:label for="importUrl">Share Link URL</flux:label>
                                <flux:input 
                                    wire:model.defer="importUrl" 
                                    id="importUrl"
                                    placeholder="https://wetransfer.com/downloads/..." 
                                    type="url"
                                    class="font-mono text-sm"
                                />
                                <flux:error name="importUrl" />
                                <flux:text size="sm" class="text-gray-500 mt-1">
                                    Supported: {{ $this->supportedDomains }}
                                </flux:text>
                            </flux:field>

                            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                                <flux:button 
                                    type="button"
                                    wire:click="hideImportModal" 
                                    variant="outline"
                                >
                                    Cancel
                                </flux:button>
                                <flux:button 
                                    type="submit" 
                                    wire:loading.attr="disabled"
                                    variant="primary"
                                >
                                    <span wire:loading.remove wire:target="importFromLink">Import Files</span>
                                    <span wire:loading wire:target="importFromLink">Starting Import...</span>
                                </flux:button>
                            </div>
                        </form>
                    </div>

                    <!-- Information callout -->
                    <div class="mt-4 rounded-md border border-blue-200 bg-blue-50 p-3">
                        <div class="flex items-start gap-2">
                            <flux:icon.information-circle class="h-4 w-4 text-blue-600 mt-0.5 flex-shrink-0" />
                            <div class="text-sm text-blue-800">
                                <p class="font-medium mb-1">How it works:</p>
                                <ul class="text-xs space-y-1">
                                    <li>• We analyze your link to detect available files</li>
                                    <li>• Files are downloaded directly to secure storage</li>
                                    <li>• You'll see progress updates in real-time</li>
                                    <li>• All files are scanned for security</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
