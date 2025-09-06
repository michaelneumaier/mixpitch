<div>
    <flux:modal name="backupHistory" class="w-full max-w-6xl">
        <div class="flex flex-col max-h-[85vh]">
            <!-- Header -->
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="xl">
                            @if($viewType === 'project' && $model instanceof \App\Models\Project)
                                {{ $model->title }} - Backup History
                            @else
                                Google Drive Backup History
                            @endif
                        </flux:heading>
                        <flux:subheading class="mt-1">
                            Track and manage your file backups to Google Drive
                        </flux:subheading>
                    </div>
                    <flux:modal.close>
                        <flux:button variant="ghost" icon="x-mark" size="sm"></flux:button>
                    </flux:modal.close>
                </div>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <div class="flex items-center">
                            <flux:icon name="cloud-arrow-up" class="w-8 h-8 text-blue-600 mr-3" />
                            <div>
                                <div class="text-2xl font-bold text-blue-900">{{ $stats['total_backups'] ?? 0 }}</div>
                                <div class="text-sm text-blue-600">Total Backups</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                        <div class="flex items-center">
                            <flux:icon name="check-circle" class="w-8 h-8 text-green-600 mr-3" />
                            <div>
                                <div class="text-2xl font-bold text-green-900">{{ $stats['successful_backups'] ?? 0 }}</div>
                                <div class="text-sm text-green-600">Successful</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                        <div class="flex items-center">
                            <flux:icon name="x-circle" class="w-8 h-8 text-red-600 mr-3" />
                            <div>
                                <div class="text-2xl font-bold text-red-900">{{ $stats['failed_backups'] ?? 0 }}</div>
                                <div class="text-sm text-red-600">Failed</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                        <div class="flex items-center">
                            <flux:icon name="scale" class="w-8 h-8 text-gray-600 mr-3" />
                            <div>
                                <div class="text-2xl font-bold text-gray-900">{{ $this->formatFileSize($stats['total_size_backed_up'] ?? 0) }}</div>
                                <div class="text-sm text-gray-600">Total Size</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8 px-6" aria-label="Tabs">
                    <button 
                        wire:click="setFilter('all')"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 {{ $filterStatus === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        All Backups ({{ $stats['total_backups'] ?? 0 }})
                    </button>
                    <button 
                        wire:click="setFilter('completed')"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 {{ $filterStatus === 'completed' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        Successful ({{ $stats['successful_backups'] ?? 0 }})
                    </button>
                    <button 
                        wire:click="setFilter('failed')"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 {{ $filterStatus === 'failed' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        Failed ({{ $stats['failed_backups'] ?? 0 }})
                    </button>
                    <button 
                        wire:click="setFilter('pending')"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 {{ $filterStatus === 'pending' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        Pending ({{ $stats['pending_backups'] ?? 0 }})
                    </button>
                </nav>
            </div>

            <!-- Backup List -->
            <div class="flex-1 overflow-y-auto min-h-0">
                @if($backups->count() > 0)
                    <div class="divide-y divide-gray-200">
                        @foreach($backups as $backup)
                            <div class="p-6 hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4 flex-1 min-w-0">
                                        <!-- File Icon -->
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                                                <flux:icon name="musical-note" class="w-5 h-5" />
                                            </div>
                                        </div>
                                        
                                        <!-- File Info -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center space-x-3 mb-1">
                                                <h4 class="text-sm font-semibold text-gray-900 truncate">
                                                    {{ $backup->original_file_name }}
                                                </h4>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $this->getStatusColor($backup->status) }}">
                                                    <flux:icon name="{{ $this->getStatusIcon($backup->status) }}" class="w-3 h-3 mr-1" />
                                                    {{ ucfirst($backup->status) }}
                                                </span>
                                            </div>
                                            
                                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                                @if($backup->project)
                                                    <span class="flex items-center">
                                                        <flux:icon name="folder" class="w-3 h-3 mr-1" />
                                                        {{ $backup->project->title }}
                                                    </span>
                                                @endif
                                                <span class="flex items-center">
                                                    <flux:icon name="scale" class="w-3 h-3 mr-1" />
                                                    {{ $backup->formatted_file_size }}
                                                </span>
                                                <span class="flex items-center">
                                                    <flux:icon name="calendar" class="w-3 h-3 mr-1" />
                                                    {{ $backup->created_at->format('M d, Y H:i') }}
                                                </span>
                                                @if($backup->backed_up_at)
                                                    <span class="flex items-center text-green-600">
                                                        <flux:icon name="check" class="w-3 h-3 mr-1" />
                                                        Backed up {{ $backup->backed_up_at->diffForHumans() }}
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            @if($backup->google_drive_folder_name)
                                                <div class="mt-2 text-xs text-gray-600">
                                                    <flux:icon name="folder" class="w-3 h-3 inline mr-1" />
                                                    Google Drive: {{ $backup->google_drive_folder_name }}
                                                </div>
                                            @endif
                                            
                                            @if($backup->error_message)
                                                <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700">
                                                    <flux:icon name="exclamation-triangle" class="w-3 h-3 inline mr-1" />
                                                    {{ $backup->error_message }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="flex items-center space-x-2 ml-4">
                                        @if($backup->hasFailed())
                                            <flux:button 
                                                wire:click="retryBackup({{ $backup->id }})"
                                                variant="outline" 
                                                size="xs"
                                                icon="arrow-path"
                                            >
                                                Retry
                                            </flux:button>
                                        @endif
                                        
                                        @if($backup->isSuccessful() && $backup->google_drive_file_id)
                                            <flux:button 
                                                onclick="window.open('https://drive.google.com/file/d/{{ $backup->google_drive_file_id }}/view', '_blank')"
                                                variant="outline" 
                                                size="xs"
                                                icon="arrow-top-right-on-square"
                                            >
                                                View
                                            </flux:button>
                                        @endif
                                        
                                        <flux:button 
                                            wire:click="deleteBackupRecord({{ $backup->id }})"
                                            variant="ghost" 
                                            size="xs"
                                            icon="trash"
                                            class="text-red-600 hover:text-red-800"
                                        >
                                        </flux:button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Pagination -->
                    @if($backups->hasPages())
                        <div class="p-6 border-t border-gray-200">
                            {{ $backups->links() }}
                        </div>
                    @endif
                @else
                    <!-- Empty State -->
                    <div class="flex flex-col items-center justify-center h-64 p-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mb-4">
                            <flux:icon name="cloud-arrow-up" class="w-8 h-8 text-gray-400" />
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No backup history found</h3>
                        <p class="text-gray-600 text-center">
                            @if($filterStatus === 'all')
                                You haven't backed up any files to Google Drive yet. Start by selecting files from your projects.
                            @else
                                No {{ $filterStatus }} backups found. Try changing the filter or create some backups.
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <div>
                        @if($stats['latest_backup'])
                            Last backup: {{ \Carbon\Carbon::parse($stats['latest_backup'])->diffForHumans() }}
                        @else
                            No backups yet
                        @endif
                    </div>
                    <flux:modal.close>
                        <flux:button variant="outline">
                            Close
                        </flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </div>
    </flux:modal>
</div>