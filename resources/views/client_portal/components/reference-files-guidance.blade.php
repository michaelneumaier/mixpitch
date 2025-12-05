{{-- Desktop Version (Full Guidance) --}}
<div class="hidden md:block p-8 space-y-6">
    {{-- Hero Section --}}
    <div class="text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
            <flux:icon.cloud-arrow-up class="w-8 h-8 text-blue-600 dark:text-blue-400" />
        </div>
        <flux:heading size="lg" class="text-gray-800 dark:text-gray-200 mb-2">
            No Files Uploaded Yet
        </flux:heading>
        <flux:subheading class="text-gray-600 dark:text-gray-400">
            Upload your project files and references above to get started
        </flux:subheading>
    </div>

    {{-- What You Can Upload --}}
    <flux:callout variant="info">
        <div class="flex items-start gap-3">
            <flux:icon.information-circle class="text-blue-500 w-5 h-5 flex-shrink-0 mt-0.5" />
            <div class="space-y-3 flex-1">
                <div>
                    <flux:heading size="sm" class="mb-1">What You Can Upload</flux:heading>
                    <flux:subheading>Share everything the producer needs to complete your project:</flux:subheading>
                </div>

                {{-- File Types Grid --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="flex items-center gap-2">
                        <flux:icon.musical-note class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                        <flux:text size="sm">Audio files (stems, tracks, references)</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon.document-text class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                        <flux:text size="sm">Project briefs & documents</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon.photo class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                        <flux:text size="sm">Images & artwork</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon.play class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                        <flux:text size="sm">Videos & visual references</flux:text>
                    </div>
                </div>

                {{-- File Support Info --}}
                <div class="pt-2 border-t border-blue-200 dark:border-blue-800">
                    <div class="flex items-center gap-2 text-sm">
                        <flux:icon.check-circle class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                        <flux:text size="sm" class="text-gray-700 dark:text-gray-300">Large files supported • Upload as many files as you need</flux:text>
                    </div>
                </div>
            </div>
        </div>
    </flux:callout>

    {{-- Upload Methods --}}
    <div class="flex items-center gap-4">
        <div class="flex-1 border-t border-gray-300 dark:border-gray-600"></div>
        <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Multiple Ways to Upload</flux:text>
        <div class="flex-1 border-t border-gray-300 dark:border-gray-600"></div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        {{-- Direct Upload --}}
        <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950 p-4">
            <div class="flex items-center gap-2 mb-2">
                <flux:icon.arrow-up-tray class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                <flux:heading size="sm">Direct Upload</flux:heading>
            </div>
            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                Drag & drop or click to browse files from your computer
            </flux:text>
        </div>

        {{-- Link Import --}}
        <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950 p-4">
            <div class="flex items-center gap-2 mb-2">
                <flux:icon.link class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                <flux:heading size="sm">Import from Link</flux:heading>
            </div>
            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                WeTransfer, Google Drive, Dropbox, or OneDrive links
            </flux:text>
        </div>
    </div>
</div>

{{-- Mobile Version (Condensed) --}}
<div class="block md:hidden p-6 space-y-4">
    {{-- Hero Section --}}
    <div class="text-center">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
            <flux:icon.cloud-arrow-up class="w-6 h-6 text-blue-600 dark:text-blue-400" />
        </div>
        <flux:heading size="base" class="text-gray-800 dark:text-gray-200 mb-1">
            No Files Uploaded Yet
        </flux:heading>
        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
            Upload your project files above to get started
        </flux:text>
    </div>

    {{-- Condensed Info --}}
    <flux:callout variant="info">
        <div class="flex items-start gap-2">
            <flux:icon.information-circle class="text-blue-500 w-5 h-5 flex-shrink-0" />
            <div class="space-y-2 flex-1">
                <flux:heading size="sm">What to Upload</flux:heading>
                <flux:text size="sm" class="text-gray-700 dark:text-gray-300">
                    Project files, stems, audio tracks, briefs, references, images, and videos
                </flux:text>

                {{-- Upload Methods (Condensed) --}}
                <div class="pt-2 border-t border-blue-200 dark:border-blue-800 space-y-1">
                    <div class="flex items-center gap-2">
                        <flux:icon.arrow-up-tray class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                        <flux:text size="sm">Drag & drop or browse</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon.link class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                        <flux:text size="sm">Import from sharing links</flux:text>
                    </div>
                </div>
            </div>
        </div>
    </flux:callout>
</div>
