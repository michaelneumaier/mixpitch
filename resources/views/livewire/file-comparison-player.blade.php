<div class="file-comparison-player" x-data="fileComparison()" x-init="init()">
    <!-- Header Controls -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xl font-semibold text-gray-900">File Comparison</h3>
                <p class="text-sm text-gray-600 mt-1">
                    Compare {{ $leftFile->file_name }} vs {{ $rightFile->file_name }}
                </p>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- Comparison Mode Selector -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">View:</label>
                    <select wire:model="comparisonMode" 
                            wire:change="setComparisonMode($event.target.value)"
                            class="text-sm border-gray-300 rounded-md">
                        <option value="side-by-side">Side by Side</option>
                        <option value="overlay">Overlay</option>
                        <option value="sequential">Sequential</option>
                    </select>
                </div>
                
                <!-- Sync Toggle -->
                <label class="flex items-center space-x-2">
                    <input type="checkbox" 
                           wire:model="syncPlayback" 
                           wire:change="toggleSync"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Sync playback</span>
                </label>
                
                <!-- Play Both Button -->
                <button @click="playBoth()" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <i class="fas fa-play mr-2"></i>Play Both
                </button>
            </div>
        </div>
        
        <!-- Comparison Summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div class="bg-blue-50 rounded-lg p-3">
                <div class="text-lg font-bold text-blue-600">{{ abs($differences['duration_diff']) }}s</div>
                <div class="text-xs text-blue-700">Duration Δ</div>
            </div>
            <div class="bg-green-50 rounded-lg p-3">
                <div class="text-lg font-bold text-green-600">{{ $differences['version_diff'] }}</div>
                <div class="text-xs text-green-700">Version Δ</div>
            </div>
            <div class="bg-purple-50 rounded-lg p-3">
                <div class="text-lg font-bold text-purple-600">{{ $summary['comment_changes']['total_comments'] }}</div>
                <div class="text-xs text-purple-700">Total Comments</div>
            </div>
            <div class="bg-amber-50 rounded-lg p-3">
                <div class="text-lg font-bold text-amber-600">{{ $summary['time_span_minutes'] }}m</div>
                <div class="text-xs text-amber-700">Time Span</div>
            </div>
        </div>
    </div>

    <!-- File Comparison Section -->
    @if($comparisonMode === 'side-by-side')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Left File Panel -->
            <div class="comparison-panel">
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-lg mb-4 border border-blue-200">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold text-blue-900">
                            @if($leftSnapshot)
                                Version {{ $leftSnapshot['snapshot_data']['version'] ?? 1 }} - {{ \Carbon\Carbon::parse($leftSnapshot['created_at'])->format('M j, Y') }}
                            @else
                                Original Version
                            @endif
                        </h4>
                        <span class="text-xs px-2 py-1 bg-blue-200 text-blue-800 rounded-full">LEFT</span>
                    </div>
                    <p class="text-sm text-blue-700 mb-2">{{ $leftFile->file_name }}</p>
                    <div class="grid grid-cols-2 gap-2 text-xs text-blue-600">
                        <div>Duration: {{ gmdate('i:s', $leftMetadata['duration']) }}</div>
                        <div>Size: {{ $leftFile->formatted_size }}</div>
                    </div>
                    <div class="mt-3">
                        <button wire:click="playLeftInGlobalPlayer" 
                                class="inline-flex items-center px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white text-xs rounded-md font-medium">
                            <i class="fas fa-external-link-alt mr-1"></i> Play in Global Player
                        </button>
                    </div>
                </div>
                
                <!-- Left File Player -->
                <div class="player-container" data-player="left">
                    <div class="bg-gray-100 rounded-lg p-6 text-center">
                        <i class="fas fa-play-circle text-4xl text-blue-500 mb-2"></i>
                        <p class="text-sm text-gray-600">Audio Player (Left)</p>
                        <p class="text-xs text-gray-500">{{ $leftFile->file_name }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Right File Panel -->
            <div class="comparison-panel">
                <div class="bg-gradient-to-r from-green-50 to-green-100 p-4 rounded-lg mb-4 border border-green-200">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold text-green-900">
                            @if($rightSnapshot)
                                Version {{ $rightSnapshot['snapshot_data']['version'] ?? 2 }} - {{ \Carbon\Carbon::parse($rightSnapshot['created_at'])->format('M j, Y') }}
                            @else
                                Current Version
                            @endif
                        </h4>
                        <span class="text-xs px-2 py-1 bg-green-200 text-green-800 rounded-full">RIGHT</span>
                    </div>
                    <p class="text-sm text-green-700 mb-2">{{ $rightFile->file_name }}</p>
                    <div class="grid grid-cols-2 gap-2 text-xs text-green-600">
                        <div>Duration: {{ gmdate('i:s', $rightMetadata['duration']) }}</div>
                        <div>Size: {{ $rightFile->formatted_size }}</div>
                    </div>
                    <div class="mt-3">
                        <button wire:click="playRightInGlobalPlayer" 
                                class="inline-flex items-center px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white text-xs rounded-md font-medium">
                            <i class="fas fa-external-link-alt mr-1"></i> Play in Global Player
                        </button>
                    </div>
                </div>
                
                <!-- Right File Player -->
                <div class="player-container" data-player="right">
                    <div class="bg-gray-100 rounded-lg p-6 text-center">
                        <i class="fas fa-play-circle text-4xl text-green-500 mb-2"></i>
                        <p class="text-sm text-gray-600">Audio Player (Right)</p>
                        <p class="text-xs text-gray-500">{{ $rightFile->file_name }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Annotation Comparison -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h4 class="text-lg font-semibold text-gray-900 mb-4">Annotation Comparison</h4>
        
        @if($comparisonMode === 'side-by-side')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <div class="flex items-center space-x-2 mb-3">
                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                        <h5 class="font-medium text-gray-900">
                            Version {{ $leftSnapshot['snapshot_data']['version'] ?? 'Original' }} Annotations
                        </h5>
                        <span class="text-sm text-gray-500">({{ $summary['comment_changes']['left_comments'] }} comments)</span>
                    </div>
                    <livewire:pitch-file-annotation-summary 
                        :pitchFile="$leftFile" 
                        key="left-annotations-{{ $leftFile->id }}" />
                </div>
                
                <div>
                    <div class="flex items-center space-x-2 mb-3">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <h5 class="font-medium text-gray-900">
                            Version {{ $rightSnapshot['snapshot_data']['version'] ?? 'Current' }} Annotations
                        </h5>
                        <span class="text-sm text-gray-500">({{ $summary['comment_changes']['right_comments'] }} comments)</span>
                    </div>
                    <livewire:pitch-file-annotation-summary 
                        :pitchFile="$rightFile" 
                        key="right-annotations-{{ $rightFile->id }}" />
                </div>
            </div>
        @else
            <!-- Unified Timeline Comparison -->
            <div class="timeline-comparison">
                @forelse($commentComparison as $interval)
                    <div class="mb-4 p-4 {{ $interval['has_changes'] ? 'bg-yellow-50 border border-yellow-200' : 'bg-gray-50 border border-gray-200' }} rounded-lg">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-3">
                                <button wire:click="jumpToTimestamp({{ $interval['start_time'] }}, 'both')"
                                        class="text-xs px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition-colors">
                                    <i class="fas fa-play mr-1"></i>{{ $interval['time_label'] }}
                                </button>
                                @if($interval['has_changes'])
                                    <span class="text-xs px-2 py-1 bg-yellow-200 text-yellow-800 rounded-full">
                                        <i class="fas fa-exclamation-circle mr-1"></i>Changes
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Left Comments -->
                            <div>
                                @if(count($interval['left_comments']) > 0)
                                    <div class="space-y-2">
                                        @foreach($interval['left_comments'] as $comment)
                                            <div class="flex items-start space-x-2 p-3 bg-blue-50 rounded border-l-4 border-blue-400">
                                                <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                                                    <i class="fas fa-user text-white text-xs"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm text-gray-900">{{ $comment['comment'] }}</p>
                                                    <p class="text-xs text-gray-500">
                                                        {{ $comment['user']['name'] ?? 'Producer' }} • {{ gmdate('i:s', $comment['timestamp']) }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-4 text-gray-400">
                                        <i class="fas fa-comment-slash text-2xl mb-2"></i>
                                        <p class="text-sm">No comments in left version</p>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Right Comments -->
                            <div>
                                @if(count($interval['right_comments']) > 0)
                                    <div class="space-y-2">
                                        @foreach($interval['right_comments'] as $comment)
                                            <div class="flex items-start space-x-2 p-3 bg-green-50 rounded border-l-4 border-green-400">
                                                <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                                                    @if($comment['is_client_comment'])
                                                        <i class="fas fa-user text-white text-xs"></i>
                                                    @else
                                                        <i class="fas fa-user-tie text-white text-xs"></i>
                                                    @endif
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm text-gray-900">{{ $comment['comment'] }}</p>
                                                    <p class="text-xs text-gray-500">
                                                        @if($comment['is_client_comment'])
                                                            {{ $comment['client_email'] }} (Client)
                                                        @else
                                                            {{ $comment['user']['name'] ?? 'Producer' }}
                                                        @endif
                                                        • {{ gmdate('i:s', $comment['timestamp']) }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-4 text-gray-400">
                                        <i class="fas fa-comment-slash text-2xl mb-2"></i>
                                        <p class="text-sm">No comments in right version</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-comment-dots text-4xl mb-2"></i>
                        <p>No annotations found in either version</p>
                    </div>
                @endforelse
            </div>
        @endif
    </div>

    <!-- Comparison Legend -->
    <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-sm font-semibold text-gray-900 mb-3">Legend</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
            <div class="flex items-center space-x-2">
                <div class="w-4 h-4 bg-blue-500 rounded"></div>
                <span class="text-gray-700">Left version ({{ $leftSnapshot['snapshot_data']['version'] ?? 'Original' }})</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-4 h-4 bg-green-500 rounded"></div>
                <span class="text-gray-700">Right version ({{ $rightSnapshot['snapshot_data']['version'] ?? 'Current' }})</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-4 h-4 bg-yellow-200 border border-yellow-400 rounded"></div>
                <span class="text-gray-700">Timeline differences</span>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-play text-blue-600 w-4"></i>
                <span class="text-gray-700">Click to jump to timestamp</span>
            </div>
        </div>
    </div>
</div>

<script>
function fileComparison() {
    return {
        leftPlayer: null,
        rightPlayer: null,
        syncEnabled: @js($syncPlayback),
        
        init() {
            this.setupPlayers();
            this.setupEventListeners();
        },
        
        setupPlayers() {
            // Wait for players to be ready
            setTimeout(() => {
                this.leftPlayer = window.leftPlayer;
                this.rightPlayer = window.rightPlayer;
                
                if (this.syncEnabled) {
                    this.setupSyncPlayback();
                }
            }, 1000);
        },
        
        setupEventListeners() {
            // Listen for Livewire events
            Livewire.on('syncPlay', (data) => {
                if (data.target === 'left' && this.leftPlayer) {
                    this.leftPlayer.setCurrentTime(data.timestamp || 0);
                    this.leftPlayer.play();
                } else if (data.target === 'right' && this.rightPlayer) {
                    this.rightPlayer.setCurrentTime(data.timestamp || 0);
                    this.rightPlayer.play();
                }
            });
            
            Livewire.on('syncPause', (data) => {
                if (data.target === 'left' && this.leftPlayer) {
                    this.leftPlayer.pause();
                } else if (data.target === 'right' && this.rightPlayer) {
                    this.rightPlayer.pause();
                }
            });
            
            Livewire.on('syncSeek', (data) => {
                if (data.target === 'left' && this.leftPlayer) {
                    this.leftPlayer.setCurrentTime(data.timestamp);
                } else if (data.target === 'right' && this.rightPlayer) {
                    this.rightPlayer.setCurrentTime(data.timestamp);
                }
            });
            
            Livewire.on('seekToPosition', (data) => {
                const timestamp = data.timestamp;
                
                if (data.player === 'both' || data.player === 'left') {
                    if (this.leftPlayer) {
                        this.leftPlayer.setCurrentTime(timestamp);
                    }
                }
                
                if (data.player === 'both' || data.player === 'right') {
                    if (this.rightPlayer) {
                        this.rightPlayer.setCurrentTime(timestamp);
                    }
                }
            });
        },
        
        setupSyncPlayback() {
            if (!this.leftPlayer || !this.rightPlayer) return;
            
            // Sync play events
            this.leftPlayer.on('play', () => {
                if (this.syncEnabled && this.rightPlayer) {
                    @this.call('onPlayerEvent', 'left', 'play', this.leftPlayer.getCurrentTime());
                }
            });
            
            this.rightPlayer.on('play', () => {
                if (this.syncEnabled && this.leftPlayer) {
                    @this.call('onPlayerEvent', 'right', 'play', this.rightPlayer.getCurrentTime());
                }
            });
            
            // Sync pause events
            this.leftPlayer.on('pause', () => {
                if (this.syncEnabled) {
                    @this.call('onPlayerEvent', 'left', 'pause');
                }
            });
            
            this.rightPlayer.on('pause', () => {
                if (this.syncEnabled) {
                    @this.call('onPlayerEvent', 'right', 'pause');
                }
            });
            
            // Sync seek events
            this.leftPlayer.on('seek', () => {
                if (this.syncEnabled) {
                    @this.call('onPlayerEvent', 'left', 'seek', this.leftPlayer.getCurrentTime());
                }
            });
            
            this.rightPlayer.on('seek', () => {
                if (this.syncEnabled) {
                    @this.call('onPlayerEvent', 'right', 'seek', this.rightPlayer.getCurrentTime());
                }
            });
        },
        
        playBoth() {
            if (this.leftPlayer && this.rightPlayer) {
                // Sync to same timestamp and play both
                const timestamp = 0;
                this.leftPlayer.setCurrentTime(timestamp);
                this.rightPlayer.setCurrentTime(timestamp);
                
                setTimeout(() => {
                    this.leftPlayer.play();
                    this.rightPlayer.play();
                }, 100);
            }
        }
    }
}
</script>