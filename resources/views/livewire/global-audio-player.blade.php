<div>
{{-- Global Audio Player Component --}}
<div x-data="globalAudioPlayer()" 
     x-effect="updateBodyPadding()"
     x-show="$store.audioPlayer.isVisible && $store.audioPlayer._initialized"
     x-transition:enter="transition ease-out duration-300" 
     x-transition:enter-start="transform translate-y-full opacity-0" 
     x-transition:enter-end="transform translate-y-0 opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="transform translate-y-0 opacity-100"
     x-transition:leave-end="transform translate-y-full opacity-0"
     x-cloak
     class="fixed bottom-0 right-0 z-50 bg-white/95 backdrop-blur-md border-t border-gray-200/50 shadow-lg"
     :class="{
         'left-0 lg:left-64': document.querySelector('[data-flux-sidebar]') && window.innerWidth >= 1024,
         'left-0': !document.querySelector('[data-flux-sidebar]') || window.innerWidth < 1024
     }"
     id="global-audio-player">
    
    {{-- Mini Player --}}
    <div x-show="$store.audioPlayer.showMiniPlayer && !$store.audioPlayer.showFullPlayer" class="px-4 py-3">
        <div class="flex items-center justify-between gap-4 mx-auto">
            {{-- Track Info --}}
            <div class="flex items-center gap-3 flex-1 min-w-0 cursor-pointer hover:bg-gray-50 rounded-lg p-2 -m-2 transition-colors" 
                 @click="openFullPlayer()" 
                 role="button" 
                 tabindex="0">
                
                {{-- Track Artwork/Icon --}}
                <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-md">
                    <i class="fas fa-music text-white text-lg"></i>
                </div>
                
                {{-- Track Details --}}
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-semibold text-gray-900 truncate" 
                        x-text="displayTrack.title">
                    </h4>
                    <p class="text-xs text-gray-600 truncate">
                        <span x-text="displayTrack.artist"></span>
                        <span x-show="displayTrack.project_title" x-text="'• ' + displayTrack.project_title"></span>
                        @if($currentTrack && isset($currentTrack['is_watermarked']) && $currentTrack['is_watermarked'] && isset($currentTrack['audio_processed']) && $currentTrack['audio_processed'])
                            @php
                                $shouldShowWatermarkBadge = false;
                                $fileForGateCheck = null;
                                if ($currentTrack['type'] === 'pitch_file') {
                                    $fileForGateCheck = \App\Models\PitchFile::find($currentTrack['id']);
                                }
                                
                                if (isset($currentTrack['client_mode']) && $currentTrack['client_mode']) {
                                    // In client portal mode, always show badge for watermarked files
                                    $shouldShowWatermarkBadge = true;
                                } elseif (Auth::check() && $fileForGateCheck) {
                                    // In main app, check Gate permission
                                    $shouldShowWatermarkBadge = Gate::allows('receivesWatermarked', $fileForGateCheck);
                                }
                            @endphp
                            @if($shouldShowWatermarkBadge)
                                <span class="inline-flex items-center gap-1 ml-2 px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">
                                    <i class="fas fa-shield-alt text-xs"></i>
                                    <span class="hidden sm:inline">Protected</span>
                                </span>
                            @endif
                        @endif
                        <span x-show="$store.audioPlayer.isOffline" class="inline-flex items-center gap-1 ml-2 text-amber-600">
                            <i class="fas fa-wifi-slash text-xs"></i>
                            <span class="text-xs">Offline</span>
                        </span>
                    </p>
                </div>
            </div>
            
            {{-- Playback Controls --}}
            <div class="flex items-center gap-2">
                {{-- Previous Track --}}
                <button wire:click="previousTrack" 
                        class="p-2 transition-all duration-150 rounded-full"
                        :class="$store.audioPlayer.queuePosition > 0 ? 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' : 'text-gray-300 cursor-not-allowed'"
                        :disabled="$store.audioPlayer.queuePosition <= 0">
                    <i class="fas fa-step-backward text-sm"></i>
                </button>
                
                {{-- Play/Pause Button --}}
                <button @click="togglePlaybackWithPersistentAudio()" 
                        class="flex items-center justify-center w-10 h-10 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-full shadow-lg hover:shadow-xl">
                    <i class="fas text-sm" :class="[$store.audioPlayer.isPlaying ? 'fa-pause' : 'fa-play', !$store.audioPlayer.isPlaying ? 'ml-0.5' : '']"></i>
                </button>
                
                {{-- Next Track --}}
                <button wire:click="nextTrack" 
                        class="p-2 transition-all duration-150 rounded-full"
                        :class="($store.audioPlayer.queuePosition < $store.audioPlayer.queue.length - 1) || $store.audioPlayer.repeatMode === 'all' ? 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' : 'text-gray-300 cursor-not-allowed'"
                        :disabled="($store.audioPlayer.queuePosition >= $store.audioPlayer.queue.length - 1) && $store.audioPlayer.repeatMode !== 'all'">
                    <i class="fas fa-step-forward text-sm"></i>
                </button>
                
                {{-- Shuffle Toggle --}}
                <button @click="$store.audioPlayer.toggleShuffle()" 
                        class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all duration-150"
                        :class="{ 'text-purple-600 bg-purple-100': $store.audioPlayer.shuffleMode }">
                    <i class="fas fa-random text-sm"></i>
                </button>
                
                {{-- Repeat Toggle --}}
                <button @click="$store.audioPlayer.cycleRepeatMode()" 
                        class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all duration-150 relative"
                        :class="{ 'text-purple-600 bg-purple-100': $store.audioPlayer.repeatMode !== 'off' }">
                    <i class="fas fa-redo text-sm"></i>
                    <span x-show="$store.audioPlayer.repeatMode === 'one'" 
                          class="absolute top-0 right-0 text-xs font-bold text-purple-600">1</span>
                </button>
                
                {{-- A-B Loop (Mini) --}}
                <div class="hidden sm:flex items-center gap-1 ml-2">
                    <button @click="setLoopPoint('start')" 
                            class="px-1.5 py-1 text-xs font-medium rounded transition-all duration-150"
                            :class="$store.audioPlayer.settingLoopPoint === 'start' ? 'bg-purple-600 text-white' : ($store.audioPlayer.loopStart !== null ? 'bg-purple-100 text-purple-700' : 'text-gray-500 hover:bg-gray-100')"
                            :title="$store.audioPlayer.loopStart !== null ? 'Loop start: ' + formatTime($store.audioPlayer.loopStart) : 'Set loop start'">
                        A
                    </button>
                    <button @click="setLoopPoint('end')" 
                            class="px-1.5 py-1 text-xs font-medium rounded transition-all duration-150"
                            :class="$store.audioPlayer.settingLoopPoint === 'end' ? 'bg-purple-600 text-white' : ($store.audioPlayer.loopEnd !== null ? 'bg-purple-100 text-purple-700' : 'text-gray-500 hover:bg-gray-100')"
                            :title="$store.audioPlayer.loopEnd !== null ? 'Loop end: ' + formatTime($store.audioPlayer.loopEnd) : 'Set loop end'">
                        B
                    </button>
                    <button x-show="$store.audioPlayer.loopStart !== null && $store.audioPlayer.loopEnd !== null"
                            @click="toggleLoop()" 
                            class="p-1.5 transition-all duration-150 rounded"
                            :class="$store.audioPlayer.loopEnabled ? 'text-purple-600 bg-purple-100' : 'text-gray-500 hover:bg-gray-100'"
                            title="Toggle loop">
                        <i class="fas fa-sync text-xs"></i>
                    </button>
                </div>
                
                {{-- Volume Control --}}
                <div class="hidden sm:flex items-center gap-2 ml-2">
                    <button wire:click="toggleMute" 
                            class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all duration-150">
                        <i x-show="$store.audioPlayer.isMuted" class="fas fa-volume-mute text-sm"></i>
                        <i x-show="!$store.audioPlayer.isMuted" class="fas fa-volume-up text-sm"></i>
                    </button>
                    
                    <div class="w-20">
                        <input type="range" 
                               min="0" 
                               max="1" 
                               step="0.1" 
                               wire:model.live="volume"
                               wire:change="setVolume($event.target.value)"
                               class="w-full h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer slider">
                    </div>
                </div>
                
                {{-- Close Button --}}
                <button wire:click="closePlayer" 
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-all duration-150">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        </div>
        
        {{-- Progress Bar --}}
        <div class="mt-2 mx-auto">
                <div class="flex items-center gap-2 text-xs text-gray-500 font-mono tabular-nums">
                    <span x-text="formatTime($store.audioPlayer.currentPosition)">00:00</span>
                
                <div class="flex-1 relative">
                    {{-- Loop markers for mini player --}}
                    <div x-show="($store.audioPlayer.loopStart !== null || $store.audioPlayer.loopEnd !== null) && $store.audioPlayer.duration > 0"
                         class="absolute inset-0 pointer-events-none z-10"
                         style="height: 32px;">
                        {{-- Start marker --}}
                        <div x-show="$store.audioPlayer.loopStart !== null"
                             class="absolute top-0 bottom-0 w-0.5 bg-green-500 opacity-75"
                             :style="{ left: (($store.audioPlayer.loopStart / $store.audioPlayer.duration) * 100) + '%' }">
                            <span class="absolute -top-4 left-0 transform -translate-x-1/2 text-[10px] font-medium text-green-600 bg-white px-0.5 rounded">A</span>
                        </div>
                        {{-- End marker --}}
                        <div x-show="$store.audioPlayer.loopEnd !== null"
                             class="absolute top-0 bottom-0 w-0.5 bg-red-500 opacity-75"
                             :style="{ left: (($store.audioPlayer.loopEnd / $store.audioPlayer.duration) * 100) + '%' }">
                            <span class="absolute -top-4 left-0 transform -translate-x-1/2 text-[10px] font-medium text-red-600 bg-white px-0.5 rounded">B</span>
                        </div>
                        {{-- Loop region --}}
                        <div x-show="$store.audioPlayer.loopStart !== null && $store.audioPlayer.loopEnd !== null"
                             class="absolute top-0 bottom-0 bg-purple-200 opacity-20"
                             :style="{ 
                                 left: (($store.audioPlayer.loopStart / $store.audioPlayer.duration) * 100) + '%',
                                 width: ((($store.audioPlayer.loopEnd - $store.audioPlayer.loopStart) / $store.audioPlayer.duration) * 100) + '%'
                             }">
                        </div>
                    </div>
                    {{-- Waveform container for WaveSurfer --}}
                    <div wire:ignore 
                         id="global-waveform" 
                         class="w-full h-8 cursor-pointer"
                         @click="handleWaveformClick($event)"
                         style="min-height: 32px;">
                    </div>

                    {{-- Floating Add Comment Button (follows playhead) --}}
                    <div x-show="$store.audioPlayer.currentTrack && ['pitch_file','project_file'].includes($store.audioPlayer.currentTrack.type) && $store.audioPlayer.duration > 0"
                         class="absolute -top-4 left-0 transform -translate-x-1/2 z-20 pointer-events-auto"
                         :style="{ left: (Math.max(0, Math.min(100, ($store.audioPlayer.currentPosition / $store.audioPlayer.duration) * 100))) + '%' , opacity: $store.audioPlayer.isPlaying ? 1 : 0.7 }">
                        <button type="button"
                                @click="window.globalAudioManager?.triggerAddCommentAtCurrentPosition()"
                                class="group w-7 h-7 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white rounded-full shadow-lg hover:shadow-xl flex items-center justify-center"
                                title="Add comment at current position">
                            <i class="fas fa-plus text-xs group-hover:scale-110 transition-transform"></i>
                        </button>
                    </div>

                    {{-- Mini Comment Tooltip (inline add) --}}
                    <div x-data="{ 
                                ts: null,
                                tf(s){ if(!s || isNaN(s)) return '00:00'; const m=Math.floor(s/60), r=Math.floor(s%60); return String(m).padStart(2,'0')+':'+String(r).padStart(2,'0'); },
                                clamp() {
                                    const container = document.getElementById('global-waveform-full');
                                    if (!container) return;
                                    const width = container.getBoundingClientRect().width || 0;
                                    const dur = $store.audioPlayer.duration || 1;
                                    const t = (typeof this.ts === 'number' && !isNaN(this.ts)) ? this.ts : ($store.audioPlayer.currentPosition || 0);
                                    const percent = Math.max(0, Math.min(100, (t / dur) * 100));
                                    const markerLeftPx = (percent / 100) * width;
                                    const tooltipWidth = this.$el.offsetWidth || 320;
                                    const margin = 8;
                                    // Default centered at marker
                                    this.$el.style.left = percent + '%';
                                    this.$el.style.transform = 'translateX(-50%)';
                                    // Near left edge
                                    if (markerLeftPx < (tooltipWidth / 2 + margin)) {
                                        this.$el.style.left = '0px';
                                        this.$el.style.transform = 'translateX(0)';
                                    }
                                    // Near right edge
                                    else if ((width - markerLeftPx) < (tooltipWidth / 2 + margin)) {
                                        this.$el.style.left = width + 'px';
                                        this.$el.style.transform = 'translateX(-100%)';
                                    }
                                }
                            }"
                         x-show="$wire.showAddCommentForm && $store.audioPlayer.currentTrack && ['pitch_file','project_file'].includes($store.audioPlayer.currentTrack.type) && $store.audioPlayer.duration > 0"
                         class="absolute -top-28 left-0 z-30"
                         x-init="window.addEventListener('resize', () => clamp());"
                         x-effect="if ($wire.showAddCommentForm) { const newTs = (typeof $wire.commentTimestamp === 'number' && !isNaN($wire.commentTimestamp)) ? $wire.commentTimestamp : ($store.audioPlayer.currentPosition || 0); if (ts !== newTs) { ts = newTs; $nextTick(() => clamp()); } } else { ts = null }"
                         x-cloak>
                        <div class="w-64 bg-white/95 backdrop-blur-md border border-white/20 rounded-xl shadow-xl p-3">
                            <div class="flex items-center gap-2 mb-2 text-xs text-gray-600">
                                <i class="fas fa-clock text-purple-600"></i>
                                <span x-text="tf(ts)">00:00</span>
                            </div>
                            <textarea wire:model="newComment"
                                      rows="2"
                                      placeholder="Add your comment..."
                                      class="w-full px-2 py-2 text-sm text-gray-700 bg-white/90 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400"></textarea>
                            <div class="flex items-center justify-end gap-2 mt-2">
                                <button type="button"
                                        @click="ts = null"
                                        wire:click="toggleCommentForm"
                                        class="px-2 py-1 text-xs text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded">
                                    Cancel
                                </button>
                                <button type="button"
                                        @click="ts = null"
                                        wire:click="addComment"
                                        class="px-3 py-1.5 text-xs font-medium text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 rounded shadow">
                                    Post
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Persistent audio element for streaming support --}}
                    <audio wire:ignore
                           id="persistent-audio-element" 
                           preload="metadata"
                           crossorigin="anonymous"
                           style="display: none;">
                    </audio>
                    
                    {{-- Persistent waveform container (hidden, used by audio manager) --}}
                    <div wire:ignore
                         id="persistent-waveform-container"
                         style="display: none;">
                        <div id="global-waveform-persistent"></div>
                    </div>
                    
                    {{-- Fallback progress bar if waveform not loaded --}}
                    <div x-show="!$store.audioPlayer.currentTrack || !$store.audioPlayer.currentTrack.waveform_data"
                         class="w-full bg-gray-200 rounded-full h-1 cursor-pointer hover:h-1.5 transition-all duration-150"
                         @click="seekFromProgressBar($event)">
                        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 h-full rounded-full transition-all duration-150"
                             :style="{ width: progressPercentage + '%' }"></div>
                    </div>
                </div>
                
                <span x-text="formatTime($store.audioPlayer.duration)">00:00</span>
            </div>
        </div>
    </div>
    
    {{-- Full Screen Player --}}
    <div x-show="$store.audioPlayer.showFullPlayer" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         class="fixed inset-0 bg-gray-900/95 backdrop-blur-xl z-60 flex items-center justify-center">
        
        <div class="w-full max-w-4xl mx-4 bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl overflow-hidden">
            {{-- Header --}}
            <div class="flex items-center justify-between p-6 border-b border-gray-200/50">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-music text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">
                            {{ $currentTrack['title'] ?? 'No Track' }}
                        </h2>
                        <p class="text-gray-600">
                            {{ $currentTrack['artist'] ?? '' }}
                            @if($currentTrack && isset($currentTrack['project_title']))
                                • {{ $currentTrack['project_title'] }}
                            @endif
                            @if($currentTrack && isset($currentTrack['is_watermarked']) && $currentTrack['is_watermarked'] && isset($currentTrack['audio_processed']) && $currentTrack['audio_processed'])
                                @php
                                    $shouldShowWatermarkBadge = false;
                                    $fileForGateCheck = null;
                                    if ($currentTrack['type'] === 'pitch_file') {
                                        $fileForGateCheck = \App\Models\PitchFile::find($currentTrack['id']);
                                    }
                                    
                                    if (isset($currentTrack['client_mode']) && $currentTrack['client_mode']) {
                                        // In client portal mode, always show badge for watermarked files
                                        $shouldShowWatermarkBadge = true;
                                    } elseif (Auth::check() && $fileForGateCheck) {
                                        // In main app, check Gate permission
                                        $shouldShowWatermarkBadge = Gate::allows('receivesWatermarked', $fileForGateCheck);
                                    }
                                @endphp
                                @if($shouldShowWatermarkBadge)
                                    <span class="inline-flex items-center gap-1 ml-2 px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                                        <i class="fas fa-shield-alt"></i>
                                        Protected
                                    </span>
                                @endif
                            @endif
                        </p>
                    </div>
                </div>
                
                <button wire:click="hideFullPlayer" 
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-all duration-150">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            {{-- Waveform Display --}}
            <div class="p-6">
                <div class="relative">
                    {{-- Loop markers --}}
                    <div x-show="$store.audioPlayer.loopStart !== null || $store.audioPlayer.loopEnd !== null"
                         class="absolute inset-0 pointer-events-none z-10">
                        {{-- Start marker --}}
                        <div x-show="$store.audioPlayer.loopStart !== null"
                             class="absolute top-0 bottom-0 w-0.5 bg-green-500"
                             :style="{ left: (($store.audioPlayer.loopStart / $store.audioPlayer.duration) * 100) + '%' }">
                            <span class="absolute -top-6 left-0 transform -translate-x-1/2 text-xs font-medium text-green-600 bg-white px-1 rounded">A</span>
                        </div>
                        
                        {{-- End marker --}}
                        <div x-show="$store.audioPlayer.loopEnd !== null"
                             class="absolute top-0 bottom-0 w-0.5 bg-red-500"
                             :style="{ left: (($store.audioPlayer.loopEnd / $store.audioPlayer.duration) * 100) + '%' }">
                            <span class="absolute -top-6 left-0 transform -translate-x-1/2 text-xs font-medium text-red-600 bg-white px-1 rounded">B</span>
                        </div>
                        
                        {{-- Loop region highlight --}}
                        <div x-show="$store.audioPlayer.loopStart !== null && $store.audioPlayer.loopEnd !== null"
                             class="absolute top-0 bottom-0 bg-purple-200 opacity-30"
                             :style="{ 
                                 left: (($store.audioPlayer.loopStart / $store.audioPlayer.duration) * 100) + '%',
                                 width: ((($store.audioPlayer.loopEnd - $store.audioPlayer.loopStart) / $store.audioPlayer.duration) * 100) + '%'
                             }">
                        </div>
                    </div>
                    
                    <div wire:ignore 
                         id="global-waveform-full" 
                         class="w-full mb-6 cursor-pointer duration-150 relative"
                         @click="handleWaveformClick($event, true)"
                         style="min-height: 120px; height: 120px;">
                        <!-- Overlay for loop/comment markers -->
                        <div class="comment-markers-overlay absolute inset-0 pointer-events-none z-10"></div>
                    </div>
                </div>
                
                {{-- Playback Controls --}}
                <div class="flex items-center justify-center gap-4 mb-6">
                    <button wire:click="previousTrack" 
                            class="p-3 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all duration-150">
                        <i class="fas fa-step-backward text-lg"></i>
                    </button>
                    
                    <button @click="togglePlaybackWithPersistentAudio()" 
                            class="flex items-center justify-center w-14 h-14 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-full shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                        <i class="fas text-lg" :class="[$store.audioPlayer.isPlaying ? 'fa-pause' : 'fa-play', !$store.audioPlayer.isPlaying ? 'ml-1' : '']"></i>
                    </button>
                    
                    <button wire:click="nextTrack" 
                            class="p-3 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all duration-150">
                        <i class="fas fa-step-forward text-lg"></i>
                    </button>
                </div>
                
                {{-- Time Display --}}
                <div class="flex items-center justify-between text-sm text-gray-600 mb-4 font-mono tabular-nums">
                    <span x-text="formatTime($store.audioPlayer.currentPosition)">00:00</span>
                    <span x-text="formatTime($store.audioPlayer.duration)">00:00</span>
                </div>
                
                {{-- Volume and Additional Controls --}}
                <div class="flex items-center justify-center gap-6">
                    <div class="flex items-center gap-2">
                        <button wire:click="toggleMute" 
                                class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all duration-150">
                            @if($isMuted)
                                <i class="fas fa-volume-mute"></i>
                            @else
                                <i class="fas fa-volume-up"></i>
                            @endif
                        </button>
                        
                        <div class="w-32">
                            <input type="range" 
                                   min="0" 
                                   max="1" 
                                   step="0.1" 
                                   wire:model.live="volume"
                                   wire:change="setVolume($event.target.value)"
                                   class="w-full h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer slider">
                        </div>
                    </div>
                    
                    {{-- Playback Rate Control --}}
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-medium text-gray-600">Speed:</label>
                        <select @change="$store.audioPlayer.setPlaybackRate(parseFloat($event.target.value)); window.globalAudioManager?.setPlaybackRate(parseFloat($event.target.value))"
                                :value="$store.audioPlayer.playbackRate"
                                class="text-xs border border-gray-300 rounded px-2 py-1 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="0.75">0.75x</option>
                            <option value="1">1x</option>
                            <option value="1.25">1.25x</option>
                            <option value="1.5">1.5x</option>
                            <option value="2">2x</option>
                        </select>
                    </div>
                    
                    {{-- Shuffle and Repeat Controls --}}
                    <div class="flex items-center gap-2">
                        <button @click="$store.audioPlayer.toggleShuffle()" 
                                class="hidden p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all duration-150"
                                :class="{ 'text-purple-600 bg-purple-100': $store.audioPlayer.shuffleMode }"
                                title="Shuffle">
                            <i class="fas fa-random text-sm"></i>
                        </button>
                        
                        <button @click="$store.audioPlayer.cycleRepeatMode()" 
                                class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all duration-150 relative"
                                :class="{ 'text-purple-600 bg-purple-100': $store.audioPlayer.repeatMode !== 'off' }"
                                :title="'Repeat: ' + $store.audioPlayer.repeatMode">
                            <i class="fas fa-redo text-sm"></i>
                            <span x-show="$store.audioPlayer.repeatMode === 'one'" 
                                  class="absolute top-0 right-0 text-xs font-bold text-purple-600">1</span>
                        </button>
                    </div>
                    
                    {{-- A-B Loop Controls --}}
                    <div class="flex items-center gap-1">
                        <button @click="setLoopPoint('start')" 
                                class="px-2 py-1 text-xs font-medium rounded transition-all duration-150"
                                :class="$store.audioPlayer.settingLoopPoint === 'start' ? 'bg-purple-600 text-white' : ($store.audioPlayer.loopStart !== null ? 'bg-purple-100 text-purple-700' : 'text-gray-600 hover:bg-gray-100')"
                                title="Set loop start point (A)">
                            A
                        </button>
                        
                        <button @click="setLoopPoint('end')" 
                                class="px-2 py-1 text-xs font-medium rounded transition-all duration-150"
                                :class="$store.audioPlayer.settingLoopPoint === 'end' ? 'bg-purple-600 text-white' : ($store.audioPlayer.loopEnd !== null ? 'bg-purple-100 text-purple-700' : 'text-gray-600 hover:bg-gray-100')"
                                title="Set loop end point (B)">
                            B
                        </button>
                        
                        <button @click="toggleLoop()" 
                                class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all duration-150"
                                :class="{ 'text-purple-600 bg-purple-100': $store.audioPlayer.loopEnabled }"
                                :disabled="!$store.audioPlayer.loopStart || !$store.audioPlayer.loopEnd"
                                title="Toggle A-B loop">
                            <i class="fas fa-sync text-sm"></i>
                        </button>
                        
                        <button x-show="$store.audioPlayer.loopStart !== null || $store.audioPlayer.loopEnd !== null"
                                @click="clearLoop()" 
                                class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition-all duration-150"
                                title="Clear loop">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                    
                    {{-- Comments Toggle (for pitch files) --}}
                    @if($currentTrack && isset($currentTrack['has_comments']) && $currentTrack['has_comments'])
                        <button wire:click="$toggle('showComments')" 
                                class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-all duration-150"
                                :class="{ 'bg-purple-100 text-purple-700': $wire.showComments }">
                            <i class="fas fa-comments"></i>
                            <span>Comments</span>
                        </button>
                    @endif
                </div>
            </div>
            
            {{-- Queue Section --}}
            <div x-show="$store.audioPlayer.queue.length > 1" class="border-t border-gray-200/50 p-6 max-h-80 overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Queue</h3>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-500">Drag to reorder</span>
                        <span class="text-sm text-gray-600" x-text="`${$store.audioPlayer.queue.length} tracks`"></span>
                    </div>
                </div>
                
                <div id="queue-sortable-list" class="space-y-2">
                    <template x-for="(track, index) in $store.audioPlayer.queue" :key="`queue-${track.type}-${track.id}`">
                        <div class="queue-item flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors"
                             :class="{ 'bg-purple-50 border border-purple-200': index === $store.audioPlayer.queuePosition }"
                             :data-track-id="track.id"
                             :data-track-type="track.type"
                             :data-index="index">
                            
                            {{-- Drag Handle --}}
                            <div class="drag-handle flex-shrink-0 w-6 h-6 text-gray-400 hover:text-gray-600 cursor-grab active:cursor-grabbing transition-colors">
                                <svg class="w-full h-full" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                                </svg>
                            </div>
                            
                            {{-- Track Icon --}}
                            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-purple-400 to-indigo-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-music text-white text-sm"></i>
                            </div>
                            
                            {{-- Track Info --}}
                            <div class="flex-1 min-w-0 cursor-pointer" @click="playTrackFromQueue(index)">
                                <h4 class="text-sm font-medium text-gray-900 truncate" x-text="track.title"></h4>
                                <p class="text-xs text-gray-600 truncate">
                                    <span x-text="track.artist"></span>
                                    <span x-show="track.project_title" x-text="'• ' + track.project_title"></span>
                                </p>
                            </div>
                            
                            {{-- Current Playing Indicator --}}
                            <div x-show="index === $store.audioPlayer.queuePosition" 
                                 class="flex-shrink-0 flex items-center text-purple-600">
                                <i x-show="$store.audioPlayer.isPlaying" class="fas fa-volume-up text-sm animate-pulse"></i>
                                <i x-show="!$store.audioPlayer.isPlaying" class="fas fa-pause text-sm"></i>
                            </div>
                            
                            {{-- Track Duration --}}
                            <div class="flex-shrink-0 text-xs text-gray-500" x-show="track.duration > 0">
                                <span x-text="formatTime(track.duration)"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
                    {{-- Comments Section --}}
                    @if($showComments && $currentTrack && in_array($currentTrack['type'], ['pitch_file','project_file']))
                <div class="border-t border-gray-200/50 p-6 max-h-80 overflow-y-auto">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Comments</h3>
                    
                    {{-- Add Comment Button --}}
                    <button wire:click="toggleCommentForm()" 
                            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-purple-700 hover:text-purple-900 hover:bg-purple-50 rounded-lg transition-all duration-150 mb-4">
                        <i class="fas fa-plus"></i>
                        <span>Add Comment</span>
                    </button>
                    
                    {{-- Add Comment Form --}}
                    @if($showAddCommentForm)
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
                                <i class="fas fa-clock"></i>
                                <span x-text="$wire.formatTime($wire.commentTimestamp)"></span>
                            </div>
                            <textarea wire:model="newComment" 
                                      placeholder="Add your comment..."
                                      rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                            <div class="flex items-center gap-2 mt-3">
                                <button wire:click="addComment" 
                                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    Post Comment
                                </button>
                                <button wire:click="toggleCommentForm" 
                                        class="px-4 py-2 text-gray-600 hover:text-gray-800 text-sm font-medium rounded-lg transition-colors">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    @endif
                    
                    {{-- Comments List --}}
                    <div class="space-y-4">
                        @forelse($comments as $comment)
                            <div class="flex gap-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-xs text-gray-600"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ $comment['user']['name'] ?? ($comment['client_email'] ? 'Client' : 'Unknown') }}
                                        </span>
                                        <span class="text-xs text-gray-500" x-text="$wire.formatTime({{ $comment['timestamp'] }})"></span>
                                        @if($comment['is_client_comment'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                Client
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-700">{{ $comment['comment'] }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 text-center py-8">No comments yet.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@script
<script>
// Create a centralized store for audio player state
Alpine.store('audioPlayer', {
    // UI State
    isVisible: false,
    showMiniPlayer: false,
    showFullPlayer: false,
    _initialized: false, // Prevent flash on load
    isLoading: false,
    loadingMessage: '',
    isOffline: false,
    
    // Track State
    currentTrack: null,
    isPlaying: false,
    currentPosition: 0,
    duration: 0,
    volume: 1.0,
    isMuted: false,
    
    // Queue State
    queue: [],
    queuePosition: 0,
    shuffleMode: false,
    repeatMode: 'off', // 'off', 'one', 'all'
    originalQueue: [], // For shuffle restoration
    playbackRate: 1.0, // 0.75x, 1x, 1.25x, 1.5x, 2x
    
    // A-B Loop State
    loopEnabled: false,
    loopStart: null,
    loopEnd: null,
    settingLoopPoint: null, // null, 'start', 'end'
    
    // Methods
    setTrack(track, queue = [], queuePosition = 0) {
        this.currentTrack = track;
        this.isVisible = true;
        this.showMiniPlayer = true;
        this.isLoading = false;
        this._initialized = true; // Mark as initialized when track is set
        
        // Update queue if provided
        if (queue.length > 0) {
            this.queue = [...queue];
            this.originalQueue = [...queue];
            this.queuePosition = queuePosition;
        }
    },
    
    setQueue(tracks, startIndex = 0) {
        this.queue = [...tracks];
        this.originalQueue = [...tracks];
        this.queuePosition = startIndex;
        
        if (tracks[startIndex]) {
            this.setTrack(tracks[startIndex]);
        }
    },
    
    nextTrack() {
        if (this.queue.length === 0) return null;
        
        if (this.queuePosition < this.queue.length - 1) {
            this.queuePosition++;
        } else if (this.repeatMode === 'all') {
            this.queuePosition = 0;
        } else {
            return null; // End of queue
        }
        
        const nextTrack = this.queue[this.queuePosition];
        if (nextTrack) {
            this.currentTrack = nextTrack;
            return nextTrack;
        }
        return null;
    },
    
    previousTrack() {
        if (this.queue.length === 0) return null;
        
        if (this.queuePosition > 0) {
            this.queuePosition--;
            const prevTrack = this.queue[this.queuePosition];
            if (prevTrack) {
                this.currentTrack = prevTrack;
                return prevTrack;
            }
        }
        return null;
    },
    
    toggleShuffle() {
        this.shuffleMode = !this.shuffleMode;
        
        if (this.shuffleMode) {
            // Shuffle the queue, keeping current track at position 0
            const currentTrack = this.queue[this.queuePosition];
            const otherTracks = this.queue.filter((_, index) => index !== this.queuePosition);
            
            // Fisher-Yates shuffle
            for (let i = otherTracks.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [otherTracks[i], otherTracks[j]] = [otherTracks[j], otherTracks[i]];
            }
            
            this.queue = [currentTrack, ...otherTracks];
            this.queuePosition = 0;
        } else {
            // Restore original queue order and find current track position
            this.queue = [...this.originalQueue];
            this.queuePosition = this.originalQueue.findIndex(track => 
                track.id === this.currentTrack?.id && track.type === this.currentTrack?.type
            );
            if (this.queuePosition === -1) this.queuePosition = 0;
        }
    },
    
    cycleRepeatMode() {
        const modes = ['off', 'all', 'one'];
        const currentIndex = modes.indexOf(this.repeatMode);
        this.repeatMode = modes[(currentIndex + 1) % modes.length];
    },
    
    setPlaybackRate(rate) {
        this.playbackRate = rate;
        // Will be handled by GlobalAudioManager
    },
    
    setLoading(message = 'Loading...') {
        this.isLoading = true;
        this.loadingMessage = message;
    },
    
    clearLoading() {
        this.isLoading = false;
        this.loadingMessage = '';
    },
    
    updatePosition(position) {
        // Only update if significantly different to prevent excessive updates
        if (Math.abs(position - this.currentPosition) > 0.5) {
            this.currentPosition = position;
        }
    },
    
    updatePlaybackState(isPlaying) {
        this.isPlaying = isPlaying;
    },
    
    reorderQueue(oldIndex, newIndex) {
        if (oldIndex === newIndex) return;
        
        // Move the track in the queue
        const movedTrack = this.queue.splice(oldIndex, 1)[0];
        this.queue.splice(newIndex, 0, movedTrack);
        
        // Update the queue position if necessary
        if (this.queuePosition === oldIndex) {
            // Currently playing track was moved
            this.queuePosition = newIndex;
        } else if (this.queuePosition > oldIndex && this.queuePosition <= newIndex) {
            // Current track position shifts left
            this.queuePosition--;
        } else if (this.queuePosition < oldIndex && this.queuePosition >= newIndex) {
            // Current track position shifts right
            this.queuePosition++;
        }
        
        // Update original queue to maintain shuffle state properly
        if (!this.shuffleMode) {
            this.originalQueue = [...this.queue];
        }
    },
    
    jumpToQueuePosition(index) {
        if (index < 0 || index >= this.queue.length) return;
        
        this.queuePosition = index;
        this.currentTrack = this.queue[index];
    },
    
    // A-B Loop Methods
    setLoopStart(position) {
        this.loopStart = position;
        if (this.loopEnd !== null && this.loopStart >= this.loopEnd) {
            // Reset end if start is after end
            this.loopEnd = null;
            this.loopEnabled = false;
        }
    },
    
    setLoopEnd(position) {
        this.loopEnd = position;
        if (this.loopStart !== null && this.loopEnd <= this.loopStart) {
            // Reset start if end is before start
            this.loopStart = null;
            this.loopEnabled = false;
        }
    },
    
    clearLoop() {
        this.loopStart = null;
        this.loopEnd = null;
        this.loopEnabled = false;
        this.settingLoopPoint = null;
    },
    
    toggleLoop() {
        if (this.loopStart !== null && this.loopEnd !== null) {
            this.loopEnabled = !this.loopEnabled;
        }
    },
    
    hide() {
        this.isVisible = false;
        this.showMiniPlayer = false;
        this.showFullPlayer = false;
        this.currentTrack = null;
        this.isPlaying = false;
        this.currentPosition = 0;
        this.duration = 0;
        this.queue = [];
        this.queuePosition = 0;
    }
});

Alpine.data('globalAudioPlayer', () => ({
        get progressPercentage() {
            const store = this.$store.audioPlayer;
            if (!store.duration || store.duration === 0) {
                return 0;
            }
            return (store.currentPosition / store.duration) * 100;
        },
        
        get displayTrack() {
            const store = this.$store.audioPlayer;
            if (store.isLoading) {
                return {
                    title: store.loadingMessage,
                    artist: '',
                    project_title: ''
                };
            }
            return store.currentTrack || {
                title: 'No Track',
                artist: '',
                project_title: ''
            };
        },
        
        seekFromProgressBar(event) {
            const store = this.$store.audioPlayer;
            const rect = event.currentTarget.getBoundingClientRect();
            const percent = (event.clientX - rect.left) / rect.width;
            const seekTime = percent * store.duration;
            
            // Update position immediately in the store for accurate loop point setting
            store.currentPosition = seekTime;
            
            this.$wire.seekTo(seekTime);
        },
        
        seekFromWaveform(event, isFullPlayer = false) {
            // If WaveSurfer is available and handles the click, let it handle seeking
            if (window.globalAudioManager && window.globalAudioManager.waveSurfer) {
                // WaveSurfer's built-in click-to-seek will handle this
                return;
            }
            
            // Fallback to manual calculation if WaveSurfer isn't available
            const store = this.$store.audioPlayer;
            const rect = event.currentTarget.getBoundingClientRect();
            const percent = (event.clientX - rect.left) / rect.width;
            const seekTime = percent * store.duration;
            this.$wire.seekTo(seekTime);
        },
        
        handleWaveformClick(event, isFullPlayer = false) {
            const store = this.$store.audioPlayer;
            
            // Calculate click position
            const rect = event.currentTarget.getBoundingClientRect();
            const percent = (event.clientX - rect.left) / rect.width;
            const clickTime = percent * store.duration;
            
            // If we're setting a loop point
            if (store.settingLoopPoint) {
                if (store.settingLoopPoint === 'start') {
                    store.setLoopStart(clickTime);
                } else if (store.settingLoopPoint === 'end') {
                    store.setLoopEnd(clickTime);
                }
                store.settingLoopPoint = null;
            } else {
                // Normal seeking
                this.seekFromWaveform(event, isFullPlayer);
            }
        },
        
        setLoopPoint(point) {
            const store = this.$store.audioPlayer;
            if (store.settingLoopPoint === point) {
                // Toggle off if clicking the same button
                store.settingLoopPoint = null;
            } else {
                // Get the actual current position from WaveSurfer if available
                let currentPos = store.currentPosition;
                if (window.globalAudioManager && window.globalAudioManager.waveSurfer) {
                    currentPos = window.globalAudioManager.waveSurfer.getCurrentTime() || 0;
                }
                
                // Set the loop point at the actual playhead position
                if (point === 'start') {
                    store.setLoopStart(currentPos);
                    // Update audio manager
                    if (window.globalAudioManager && store.loopEnd !== null) {
                        window.globalAudioManager.setLoop(currentPos, store.loopEnd);
                    }
                } else if (point === 'end') {
                    store.setLoopEnd(currentPos);
                    // Update audio manager
                    if (window.globalAudioManager && store.loopStart !== null) {
                        window.globalAudioManager.setLoop(store.loopStart, currentPos);
                    }
                }
            }
        },
        
        toggleLoop() {
            this.$store.audioPlayer.toggleLoop();
            if (this.$store.audioPlayer.loopEnabled) {
                // Notify the audio manager about loop settings
                if (window.globalAudioManager) {
                    window.globalAudioManager.setLoop(
                        this.$store.audioPlayer.loopStart, 
                        this.$store.audioPlayer.loopEnd
                    );
                }
            } else {
                // Disable loop in audio manager
                if (window.globalAudioManager) {
                    window.globalAudioManager.clearLoop();
                }
            }
        },
        
        clearLoop() {
            this.$store.audioPlayer.clearLoop();
            if (window.globalAudioManager) {
                window.globalAudioManager.clearLoop();
            }
        },
        
        playTrackFromQueue(index) {
            const store = this.$store.audioPlayer;
            const track = store.queue[index];
            
            if (!track) return;
            
            // Update queue position
            store.queuePosition = index;
            store.currentTrack = track;
            
            // Play the selected track
            if (track.type === 'pitch_file') {
                this.$wire.playPitchFile(track.id, false, '');
            } else if (track.type === 'project_file') {
                this.$wire.playProjectFile(track.id);
            }
        },
        
        formatTime(seconds) {
            if (!seconds || isNaN(seconds)) {
                return '00:00';
            }
            
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            
            return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
        },
        
        updateBodyPadding() {
            // Primary: CSS variable drives padding; persists across wire:navigate/PWA
            const isVisible = this.$store.audioPlayer.isVisible && this.$store.audioPlayer.showMiniPlayer;
            const offset = isVisible ? '120px' : '0px';
            document.body.style.setProperty('--global-audio-player-offset', offset);
            document.documentElement.style.setProperty('--global-audio-player-offset', offset);

            // Optional fallback via class (harmless if left)
            if (isVisible) {
                document.body.classList.add('global-audio-player-active');
            } else {
                document.body.classList.remove('global-audio-player-active');
            }
        },
        
        // Persistent audio commands removed - persist handles persistence
        
        playTrackWithPersistentAudio(track) {
            if (!track) {
                console.error('No track provided to playTrackWithPersistentAudio');
                return;
            }

            console.log('Playing track with persistent audio:', track.title);
            
            // Set the track in Alpine store - persist will handle the rest
            this.$store.audioPlayer.setTrack(track);
        },
        
        togglePlaybackWithPersistentAudio() {
            // Just use the existing Livewire method - persist handles persistence
            this.$wire.togglePlayback();
        },
        
        init() {
            // Initialize the global audio manager when component mounts
            this.$nextTick(() => {
                if (window.globalAudioManager && !window.globalAudioManager.isReady()) {
                    // Try to initialize with the mini player container first
                    if (!window.globalAudioManager.initializeWaveSurfer('#global-waveform')) {
                        // If that fails, try the full player container
                        window.globalAudioManager.initializeWaveSurfer('#global-waveform-full');
                    }
                }
                
                // Register this component with the global audio manager
                if (window.globalAudioManager) {
                    window.globalAudioManager.registerLivewireComponent(this.$wire);
                    // Also register the Alpine store for direct updates
                    window.globalAudioManager.registerAlpineStore(this.$store.audioPlayer);
                }
                
                // Initialize drag and drop for queue reordering
                this.initializeDragAndDrop();
                
                // Sync initial state from Livewire to Alpine store
                this.syncFromLivewire();
                
                // Apply padding immediately if player is already visible (important for navigation)
                this.updateBodyPadding();
                
                // Listen for Livewire events - simplified for persist
                this.$wire.$on('startPersistentAudio', (event) => {
                    console.log('Starting audio for track:', event.track);
                    if (event.track) {

                        // Call handlePlayTrack and then explicitly start playback
                        this.$wire.handlePlayTrack(event.track).then(() => {
                            // After track is set up, start playback
                            setTimeout(() => {
                                console.log('Auto-starting playback after track setup');
                                this.togglePlaybackWithPersistentAudio();
                            }, 100);
                        });
                    }
                });
                
                // Also listen for the global Livewire event
                Livewire.on('startPersistentAudio', (event) => {
                    console.log('Global audio event received:', event);
                    if (event.track) {
                        // Call handlePlayTrack and then explicitly start playback
                        this.$wire.handlePlayTrack(event.track).then(() => {
                            // After track is set up, start playback
                            setTimeout(() => {
                                console.log('Auto-starting playback after track setup');
                                this.togglePlaybackWithPersistentAudio();
                            }, 100);
                        });
                    }
                });
            });
            
            // Re-apply padding after Livewire navigate morphs the DOM
            window.addEventListener('livewire:navigated', () => {
                // Delay slightly to ensure new markup is in place
                setTimeout(() => this.updateBodyPadding(), 0);
            });
            
            // Setup keyboard shortcuts
            this.setupKeyboardShortcuts();
            
            // Monitor offline/online status
            this.monitorNetworkStatus();
            
            // Monitor sidebar state changes
            this.monitorSidebarState();
            
            // Navigation listeners removed - using simpler CSS approach
            
            // Watch for Livewire property changes and sync to store
            this.$wire.$watch('currentTrack', (value) => {
                if (value) {
                    this.$store.audioPlayer.setTrack(value);
                }
            });
            
            this.$wire.$watch('isPlaying', (value) => {
                this.$store.audioPlayer.updatePlaybackState(value);
            });
            
            this.$wire.$watch('currentPosition', (value) => {
                this.$store.audioPlayer.updatePosition(value);
            });
            
            this.$wire.$watch('duration', (value) => {
                this.$store.audioPlayer.duration = value;
            });
            
            this.$wire.$watch('showFullPlayer', (value) => {
                this.$store.audioPlayer.showFullPlayer = value;
            });
            
            this.$wire.$watch('isVisible', (value) => {
                this.$store.audioPlayer.isVisible = value;
                // Update body padding when visibility changes
                this.updateBodyPadding();
            });
            
            this.$wire.$watch('showMiniPlayer', (value) => {
                this.$store.audioPlayer.showMiniPlayer = value;
                // Update body padding when mini player visibility changes
                this.updateBodyPadding();
            });
            
            // Listen for full player toggle
            Livewire.on('fullPlayerToggled', (event) => {
                console.log('Full player toggled:', event.visible);
                this.$store.audioPlayer.showFullPlayer = event.visible;
            });
            
            // Listen for player closed event
            Livewire.on('playerClosed', () => {
                console.log('Player closed, hiding Alpine store');
                this.$store.audioPlayer.hide();
                // Remove body padding when player is closed
                this.updateBodyPadding();
            });
            
            // Listen for track ended to handle auto-advancement
            Livewire.on('trackEnded', () => {
                const store = this.$store.audioPlayer;
                if (store.repeatMode === 'one') {
                    // Already handled by Livewire
                    return;
                }
                
                if (store.queue.length > 0) {
                    const nextTrack = store.nextTrack();
                    if (nextTrack) {
                        // Play the next track
                        if (nextTrack.type === 'pitch_file') {
                            this.$wire.playPitchFile(nextTrack.id, false, '');
                        } else if (nextTrack.type === 'project_file') {
                            this.$wire.playProjectFile(nextTrack.id);
                        }
                    }
                }
            });

            // Listen for audio state saving before navigation
            Livewire.on('saveAudioStateForNavigation', (event) => {
                console.log('Saving audio state for navigation:', event);
                this.saveAudioStateToServiceWorker(event.state);
            });
        },
        
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                // Only handle shortcuts if no input is focused and player is visible
                if (document.activeElement.tagName === 'INPUT' || 
                    document.activeElement.tagName === 'TEXTAREA' ||
                    !this.$store.audioPlayer.isVisible) {
                    return;
                }
                
                switch(e.code) {
                    case 'Space':
                        e.preventDefault();
                        this.togglePlaybackWithPersistentAudio();
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        if (e.shiftKey) {
                            // Skip forward 10 seconds
                            const newTime = Math.min(this.$store.audioPlayer.currentPosition + 10, this.$store.audioPlayer.duration);
                            this.$wire.seekTo(newTime);
                        } else {
                            // Next track
                            this.$wire.nextTrack();
                        }
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        if (e.shiftKey) {
                            // Skip backward 10 seconds
                            const newTime = Math.max(this.$store.audioPlayer.currentPosition - 10, 0);
                            this.$wire.seekTo(newTime);
                        } else {
                            // Previous track
                            this.$wire.previousTrack();
                        }
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        // Volume up
                        const newVolumeUp = Math.min(this.$store.audioPlayer.volume + 0.1, 1.0);
                        this.$wire.setVolume(newVolumeUp);
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        // Volume down
                        const newVolumeDown = Math.max(this.$store.audioPlayer.volume - 0.1, 0);
                        this.$wire.setVolume(newVolumeDown);
                        break;
                    case 'KeyM':
                        e.preventDefault();
                        this.$wire.toggleMute();
                        break;
                    case 'KeyF':
                        e.preventDefault();
                        // Toggle full player
                        if (this.$store.audioPlayer.showFullPlayer) {
                            this.$wire.hideFullPlayer();
                        } else {
                            this.$wire.showFullPlayer();
                        }
                        break;
                }
            });
        },
        
        initializeDragAndDrop() {
            // We'll use a simple HTML5 drag and drop implementation
            // This method will be called when the DOM is ready
            const self = this;
            
            // We need to wait for the queue to be populated before setting up drag and drop
            const setupDragAndDrop = () => {
                const queueList = document.getElementById('queue-sortable-list');
                if (!queueList) return;
                
                let draggedElement = null;
                let draggedIndex = null;
                
                // Add event listeners to existing and new queue items
                const addDragListeners = () => {
                    const queueItems = queueList.querySelectorAll('.queue-item');
                    
                    queueItems.forEach((item, index) => {
                        const dragHandle = item.querySelector('.drag-handle');
                        if (!dragHandle) return;
                        
                        item.draggable = true;
                        
                        // Remove existing listeners to prevent duplicates
                        item.removeEventListener('dragstart', handleDragStart);
                        item.removeEventListener('dragover', handleDragOver);
                        item.removeEventListener('drop', handleDrop);
                        item.removeEventListener('dragend', handleDragEnd);
                        
                        // Add drag event listeners
                        item.addEventListener('dragstart', handleDragStart);
                        item.addEventListener('dragover', handleDragOver);
                        item.addEventListener('drop', handleDrop);
                        item.addEventListener('dragend', handleDragEnd);
                    });
                };
                
                const handleDragStart = (e) => {
                    draggedElement = e.target;
                    draggedIndex = parseInt(e.target.dataset.index);
                    e.dataTransfer.effectAllowed = 'move';
                    e.target.style.opacity = '0.5';
                };
                
                const handleDragOver = (e) => {
                    if (e.preventDefault) {
                        e.preventDefault();
                    }
                    e.dataTransfer.dropEffect = 'move';
                    return false;
                };
                
                const handleDrop = (e) => {
                    if (e.stopPropagation) {
                        e.stopPropagation();
                    }
                    
                    if (draggedElement !== e.target) {
                        const dropIndex = parseInt(e.target.dataset.index);
                        if (!isNaN(dropIndex) && !isNaN(draggedIndex) && dropIndex !== draggedIndex) {
                            // Reorder the queue in the Alpine store
                            self.$store.audioPlayer.reorderQueue(draggedIndex, dropIndex);
                            
                            // Update Livewire with the new queue order
                            self.$wire.updateQueueOrder(self.$store.audioPlayer.queue, self.$store.audioPlayer.queuePosition);
                        }
                    }
                    
                    return false;
                };
                
                const handleDragEnd = (e) => {
                    e.target.style.opacity = '';
                    draggedElement = null;
                    draggedIndex = null;
                };
                
                // Initial setup
                addDragListeners();
                
                // Watch for queue changes and re-setup drag and drop
                const observer = new MutationObserver((mutations) => {
                    let shouldResetup = false;
                    mutations.forEach((mutation) => {
                        if (mutation.type === 'childList' && mutation.target === queueList) {
                            shouldResetup = true;
                        }
                    });
                    if (shouldResetup) {
                        setTimeout(addDragListeners, 100); // Small delay for Alpine to finish rendering
                    }
                });
                
                observer.observe(queueList, {
                    childList: true,
                    subtree: true
                });
            };
            
            // Setup drag and drop with a delay to ensure the queue is rendered
            setTimeout(setupDragAndDrop, 500);
        },
        
        monitorSidebarState() {
            // Watch for sidebar visibility changes
            const resizeObserver = new ResizeObserver(() => {
                // Force re-evaluation of the player's left position
                this.$el.dispatchEvent(new Event('resize'));
            });
            
            const sidebar = document.querySelector('[data-flux-sidebar]');
            if (sidebar) {
                resizeObserver.observe(sidebar);
            }
            
            // Also watch window resize
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    this.$el.dispatchEvent(new Event('resize'));
                }, 100);
            });
        },
        
        monitorNetworkStatus() {
            // Initial status
            this.$store.audioPlayer.isOffline = !navigator.onLine;
            
            // Monitor status changes
            window.addEventListener('online', () => {
                this.$store.audioPlayer.isOffline = false;
                console.log('Global Audio Player: Back online');
                
                // Show notification
                if (window.Notification && Notification.permission === 'granted') {
                    new Notification('MixPitch', {
                        body: 'You\'re back online',
                        icon: '/icons/icon-192x192.png',
                        badge: '/icons/icon-96x96.png',
                        tag: 'mixpitch-online'
                    });
                }
            });
            
            window.addEventListener('offline', () => {
                this.$store.audioPlayer.isOffline = true;
                console.log('Global Audio Player: Went offline');
                
                // Show notification
                if (window.Notification && Notification.permission === 'granted') {
                    new Notification('MixPitch', {
                        body: 'You\'re offline - cached audio will still play',
                        icon: '/icons/icon-192x192.png',
                        badge: '/icons/icon-96x96.png',
                        tag: 'mixpitch-offline'
                    });
                }
            });
        },
        
        openFullPlayer() {
            console.log('Opening full player via navigation...');
            // Call the Livewire method that handles navigation to the universal audio player
            this.$wire.navigateToFullPlayer();
        },
        
        syncFromLivewire() {
            // Sync current Livewire state to Alpine store
            const store = this.$store.audioPlayer;
            store.isVisible = this.$wire.isVisible;
            store.showMiniPlayer = this.$wire.showMiniPlayer;
            store.showFullPlayer = this.$wire.showFullPlayer;
            store.currentTrack = this.$wire.currentTrack;
            store.isPlaying = this.$wire.isPlaying;
            store.currentPosition = this.$wire.currentPosition;
            store.duration = this.$wire.duration;
            store.volume = this.$wire.volume;
            store.isMuted = this.$wire.isMuted;
            
            // Mark as initialized after first sync
            store._initialized = true;
            
            // Apply padding after sync to ensure proper spacing
            this.updateBodyPadding();
        },

}));
</script>
@endscript

<style>
/* Custom slider styling */
.slider {
    background: linear-gradient(to right, #8B5CF6 0%, #6366F1 var(--slider-progress, 0%), #E5E7EB var(--slider-progress, 0%), #E5E7EB 100%);
}

.slider::-webkit-slider-thumb {
    appearance: none;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8B5CF6, #6366F1);
    cursor: pointer;
    border: 2px solid white;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    transition: transform 0.15s ease;
}

.slider::-webkit-slider-thumb:hover {
    transform: scale(1.1);
}

.slider::-moz-range-thumb {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8B5CF6, #6366F1);
    cursor: pointer;
    border: 2px solid white;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    transition: transform 0.15s ease;
}

.slider::-moz-range-thumb:hover {
    transform: scale(1.1);
}

/* Drag and Drop Styling */
.queue-item {
    transition: all 0.15s ease;
}

.queue-item:hover .drag-handle {
    opacity: 1;
}

.queue-item[draggable="true"] {
    position: relative;
}

.queue-item[draggable="true"]:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.queue-item.dragging {
    opacity: 0.5;
    transform: rotate(5deg);
    z-index: 1000;
}

.queue-item.drag-over {
    border-color: #8B5CF6;
    background-color: rgba(139, 92, 246, 0.05);
}

.drag-handle {
    opacity: 0.6;
    transition: opacity 0.15s ease;
}

.drag-handle:hover {
    opacity: 1;
}

.drag-handle:active {
    cursor: grabbing !important;
}

/* Sortable placeholder styling */
.sortable-ghost {
    opacity: 0.4;
    background: rgba(139, 92, 246, 0.1);
    border: 2px dashed #8B5CF6;
}

.sortable-chosen {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: rotate(2deg);
}

.sortable-drag {
    opacity: 0.8;
    transform: rotate(5deg);
    z-index: 1000;
}

/* A-B Loop Styling */
.loop-marker {
    transition: all 0.15s ease;
}

.loop-region {
    pointer-events: none;
}

/* Override waveform container z-index to ensure markers appear on top */
#global-waveform-full {
    position: relative;
    z-index: 1;
}

/* Loop button states */
.loop-button-active {
    background-color: rgba(139, 92, 246, 0.1);
    color: #8B5CF6;
}

.loop-setting {
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.6; }
    100% { opacity: 1; }
}

</style>
</div>