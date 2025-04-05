{{-- blade-formatter-disable --}}
<div class="waveform-player-container relative">
    <div class="bg-white rounded-lg overflow-hidden">
        <!-- File Header with Name and Controls -->
        <div class="bg-gradient-to-r from-primary/10 to-secondary/10 px-6 py-4 border-b border-gray-200">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">{{ $file->file_name }}</h2>
                    <p class="text-sm text-gray-500">Added {{ $file->created_at->diffForHumans() }} &bull; {{
                        $file->formattedSize }}</p>
                </div>
                @if($isInCard ?? false)
                    {{-- Minimal buttons for card view --}}
                @else
                    {{-- Full controls for dedicated view --}}
                    <div class="flex space-x-2 items-center">
                        <a href="{{ route('pitch-files.download', ['file' => $file->id]) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-download mr-1"></i> Download
                        </a>
                         @if(auth()->check() && auth()->user()->can('delete', $file))
                            <button wire:click="$dispatch('open-delete-modal', { fileId: {{ $file->id }} })" class="btn btn-error btn-sm">
                                <i class="fas fa-trash-alt mr-1"></i> Delete
                            </button>
                        @endif
                        <a href="{{ \App\Helpers\RouteHelpers::pitchUrl($file->pitch) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Pitch
                        </a>
                    </div>
                @endif
            </div>
        </div>
        <div class="p-6">
            <!-- Audio Waveform Container -->
            <div class="waveform-container">
                <div class="relative">
                    <!-- Waveform Visualization -->
                    <div id="waveform" class="h-32 rounded-md overflow-hidden bg-gray-100" wire:ignore>
                        <!-- Waveform will be rendered here -->
                    </div>

                    <!-- Comment Markers that will be positioned on the waveform -->
                    <div class="comment-markers absolute inset-0 pointer-events-none">
                        @if($duration > 0)
                        @foreach($comments as $comment)
                        @php
                        $position = ($comment->timestamp / max(0.1, $duration)) * 100;
                        $position = min(max($position, 0), 100);
                        $tooltipClass = $position < 15 ? 'left-0 transform-none' : ($position> 85 ? 'left-auto right-0
                            transform-none' :
                            'left-0 transform -translate-x-1/2');
                            @endphp
                            <div class="absolute h-full w-0.5 z-10 cursor-pointer pointer-events-auto group"
                                style="left: {{ $position }}%; background-color: rgba(59, 130, 246, 0.6);"
                                x-data="{ showTooltip: false }" @mouseenter="showTooltip = true"
                                @mouseleave="showTooltip = false"
                                @click="$dispatch('comment-marker-clicked', { timestamp: {{ $comment->timestamp }} })">
                                <div
                                    class="h-3 w-3 rounded-full -ml-1 bg-blue-500 absolute top-0 group-hover:scale-110 transition-transform">
                                </div>

                                <!-- Comment Tooltip -->
                                <div x-show="showTooltip" x-cloak
                                    class="absolute bottom-full mb-2 p-2 bg-white rounded-lg shadow-lg border border-gray-200 w-64 z-50 {{ $tooltipClass }}"
                                    @click.stop>
                                    <div class="text-xs text-gray-500 flex items-center mb-1">
                                        <img src="{{ $comment->user->profile_photo_url }}"
                                            alt="{{ $comment->user->name }}" class="h-4 w-4 rounded-full mr-1">
                                        <span class="font-medium">{{ $comment->user->name }}</span>
                                        <span class="mx-1">•</span>
                                        <span>{{ $comment->formattedTimestamp }}</span>
                                    </div>
                                    <div class="text-sm text-gray-800 font-medium">
                                        {{ \Illuminate\Support\Str::limit($comment->comment, 100) }}
                                    </div>
                                    @if($comment->resolved)
                                    <div class="mt-1 text-xs text-green-600 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Resolved
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                            @endif
                    </div>
                </div>
            </div>

            <!-- Timeline for the waveform -->
            <div id="waveform-timeline" class="h-8 mt-1 relative" wire:ignore>
                <!-- Timeline will be rendered here -->
            </div>

            <!-- Playback Controls -->
            <div class="flex items-center justify-between mt-4">
                <div class="flex items-center space-x-3" wire:ignore>
                    <button id="playPauseBtn" x-data="{ isPlaying: false }"
                        x-on:click="isPlaying = !isPlaying; $dispatch('toggle-playback', { playing: isPlaying })"
                        x-on:playback-state-changed.window="isPlaying = $event.detail.playing"
                        :class="{'bg-primary': true, 'text-white': true, 'hover:bg-primary-focus': true, 'transition-colors': true}"
                        class="w-10 h-10 flex items-center justify-center rounded-full">

                        <!-- Play icon (shown when paused) -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" x-show="!isPlaying" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>

                        <!-- Pause icon (shown when playing) -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" x-show="isPlaying" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                    <div class="flex items-center space-x-1 text-xs">
                        <div id="currentTime" class="text-sm font-medium">00:00</div>
                        <div class="text-sm text-gray-500">/</div>
                        <div id="totalDuration" class="text-sm text-gray-500">00:00</div>
                    </div>
                </div>

                <div class="flex items-center">
                    <button type="button" @click="$wire.toggleCommentForm(wavesurfer.getCurrentTime())"
                        class="flex items-center text-sm text-primary hover:text-primary-focus hover:underline">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4v-5z" />
                        </svg>
                        Add Comment at Current Position
                    </button>
                </div>
            </div>
        </div>
        <!-- Add Comment Form -->
        <div x-data="{ show: @entangle('showAddCommentForm') }" x-show="show" x-cloak
            class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200 transition-all duration-300">
            <div class="flex items-center text-sm text-gray-600 mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-primary" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Adding comment at {{ sprintf("%02d:%02d", floor($commentTimestamp / 60), $commentTimestamp % 60) }}
            </div>
            <textarea wire:model="newComment" placeholder="Add your comment here..."
                class="w-full px-3 py-2 text-gray-700 border rounded-lg focus:outline-none focus:border-primary focus:ring focus:ring-primary/20"
                rows="2"></textarea>
            <div class="flex justify-end mt-2 space-x-2">
                <button type="button" @click="show = false; $wire.showAddCommentForm = false"
                    class="btn btn-ghost btn-sm">Cancel</button>
                <button type="button" wire:click="addComment" class="btn btn-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Comment
                </button>
            </div>
        </div>
    </div>

    <!-- Comments Section -->
    <div class="comments-section px-6 pb-6">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-primary" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            Comments <span class="text-gray-500 text-sm ml-2">({{ count($comments) }})</span>
        </h3>

        @if(count($comments) > 0)
        <div class="space-y-4">
            @foreach($comments as $comment)
            <div id="comment-{{ $comment->id }}"
                class="flex items-start space-x-3 p-4 rounded-lg {{ $comment->resolved ? 'bg-green-50 border border-green-100' : 'bg-white border border-gray-200' }}">
                <div class="flex-shrink-0">
                    <img src="{{ $comment->user->profile_photo_url }}" alt="{{ $comment->user->name }}"
                        class="h-10 w-10 rounded-full">
                </div>
                <div class="flex-grow">
                    <div class="flex justify-between">
                        <div>
                            <div class="font-medium text-gray-900">{{ $comment->user->name }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $comment->created_at->diffForHumans() }} at
                                <button type="button" @click="$wire.seekTo({{ $comment->timestamp }})"
                                    class="font-medium text-primary hover:underline">
                                    {{ $comment->formattedTimestamp }}
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button type="button" @click="$wire.toggleReplyForm({{ $comment->id }})"
                                class="text-xs text-blue-500 hover:text-blue-700 mr-2">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Reply
                                </div>
                            </button>

                            @if(Auth::id() === $comment->user_id || Auth::id() === $file->pitch->user_id)
                            <button type="button" @click="$wire.toggleResolveComment({{ $comment->id }})"
                                class="text-sm {{ $comment->resolved ? 'text-green-600 hover:text-green-700' : 'text-gray-500 hover:text-gray-700' }}">
                                @if($comment->resolved)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                @endif
                            </button>
                            <button type="button" @click="$wire.confirmDelete({{ $comment->id }})"
                                class="text-sm text-red-500 hover:text-red-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                            @endif
                        </div>
                    </div>
                    <div class="mt-1 text-sm text-gray-700 whitespace-pre-line">
                        {{ $comment->comment }}
                    </div>
                    @if($comment->resolved)
                    <div class="mt-2 text-sm font-medium text-green-600 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Resolved
                    </div>
                    @endif

                    <!-- Reply Form -->
                    @if($showReplyForm && $replyToCommentId === $comment->id)
                    <div class="mt-3 pl-4 border-l-2 border-gray-200">
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <textarea wire:model="replyText" placeholder="Write your reply here..."
                                class="w-full px-3 py-2 text-sm text-gray-700 border rounded-lg focus:outline-none focus:border-primary focus:ring focus:ring-primary/20"
                                rows="2"></textarea>
                            <div class="flex justify-end mt-2 space-x-2">
                                <button type="button" wire:click="toggleReplyForm"
                                    class="btn btn-ghost btn-xs">Cancel</button>
                                <button type="button" wire:click="submitReply" class="btn btn-primary btn-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Reply
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Replies Section -->
                    @if($comment->has_replies)
                    <div class="mt-3 space-y-3 pl-6 border-l-2 border-gray-100">
                        @foreach($comment->replies as $reply)
                        <div id="comment-{{ $reply->id }}" class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                            <div class="flex-shrink-0">
                                <img src="{{ $reply->user->profile_photo_url }}" alt="{{ $reply->user->name }}"
                                    class="h-8 w-8 rounded-full">
                            </div>
                            <div class="flex-grow">
                                <div class="flex justify-between">
                                    <div>
                                        <div class="font-medium text-gray-900 text-sm">{{ $reply->user->name }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ $reply->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button type="button" @click="$wire.toggleReplyForm({{ $reply->id }})"
                                            class="text-xs text-blue-500 hover:text-blue-700 mr-1">
                                            <div class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                    viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Reply
                                            </div>
                                        </button>

                                        @if(Auth::id() === $reply->user_id || Auth::id() === $file->pitch->user_id)
                                        <button type="button" @click="$wire.confirmDelete({{ $reply->id }})"
                                            class="text-xs text-red-500 hover:text-red-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                                <div class="mt-1 text-sm text-gray-700 whitespace-pre-line">
                                    {{ $reply->comment }}
                                </div>

                                <!-- Nested Reply Form -->
                                @if($showReplyForm && $replyToCommentId === $reply->id)
                                <div class="mt-3 pl-4 border-l-2 border-gray-200">
                                    <div class="p-3 bg-gray-100 rounded-lg">
                                        <textarea wire:model="replyText" placeholder="Write your reply here..."
                                            class="w-full px-3 py-2 text-sm text-gray-700 border rounded-lg focus:outline-none focus:border-primary focus:ring focus:ring-primary/20"
                                            rows="2"></textarea>
                                        <div class="flex justify-end mt-2 space-x-2">
                                            <button type="button" wire:click="toggleReplyForm"
                                                class="btn btn-ghost btn-xs">Cancel</button>
                                            <button type="button" wire:click="submitReply"
                                                class="btn btn-primary btn-xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                </svg>
                                                Reply
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Nested Replies (Replies to Replies) -->
                                @if($reply->has_replies)
                                <div class="mt-3 space-y-3 pl-4 border-l-2 border-gray-100">
                                    @foreach($reply->replies as $nestedReply)
                                    <div id="comment-{{ $nestedReply->id }}"
                                        class="flex items-start space-x-3 p-3 bg-gray-100 rounded-lg">
                                        <div class="flex-shrink-0">
                                            <img src="{{ $nestedReply->user->profile_photo_url }}"
                                                alt="{{ $nestedReply->user->name }}" class="h-7 w-7 rounded-full">
                                        </div>
                                        <div class="flex-grow">
                                            <div class="flex justify-between">
                                                <div>
                                                    <div class="font-medium text-gray-900 text-xs">{{
                                                        $nestedReply->user->name }}</div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ $nestedReply->created_at->diffForHumans() }}
                                                    </div>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <button type="button"
                                                        @click="$wire.toggleReplyForm({{ $nestedReply->id }})"
                                                        class="text-xs text-blue-500 hover:text-blue-700 mr-1">
                                                        <div class="flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                                viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd"
                                                                    d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            Reply
                                                        </div>
                                                    </button>

                                                    @if(Auth::id() === $nestedReply->user_id || Auth::id() ===
                                                    $file->pitch->user_id)
                                                    <button type="button"
                                                        @click="$wire.confirmDelete({{ $nestedReply->id }})"
                                                        class="text-xs text-red-500 hover:text-red-700">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="mt-1 text-xs text-gray-700 whitespace-pre-line">
                                                {{ $nestedReply->comment }}
                                            </div>

                                            <!-- Further Nested Reply Form -->
                                            @if($showReplyForm && $replyToCommentId === $nestedReply->id)
                                            <div class="mt-2 pl-3 border-l-2 border-gray-200">
                                                <div class="p-2 bg-gray-200 rounded-lg">
                                                    <textarea wire:model="replyText"
                                                        placeholder="Write your reply here..."
                                                        class="w-full px-3 py-2 text-xs text-gray-700 border rounded-lg focus:outline-none focus:border-primary focus:ring focus:ring-primary/20"
                                                        rows="2"></textarea>
                                                    <div class="flex justify-end mt-2 space-x-2">
                                                        <button type="button" wire:click="toggleReplyForm"
                                                            class="btn btn-ghost btn-xs">Cancel</button>
                                                        <button type="button" wire:click="submitReply"
                                                            class="btn btn-primary btn-xs">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1"
                                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                            </svg>
                                                            Reply
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-8 bg-gray-50 rounded-lg border border-gray-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-3" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <p class="text-gray-500">No comments yet. Add a comment to start the conversation!</p>
            <button type="button" @click="$wire.toggleCommentForm(wavesurfer ? wavesurfer.getCurrentTime() : 0)"
                class="btn btn-primary btn-sm mt-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add First Comment
            </button>
        </div>
        @endif
    </div>
    <!-- Delete Comment Confirmation Modal -->
    <div x-data="{ show: @entangle('showDeleteConfirmation') }" x-show="show" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true"></div>

            <!-- Modal panel -->
            <div x-show="show" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div
                        class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Delete Comment
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Are you sure you want to delete this comment? This action cannot be undone and will also
                                delete all replies to this comment.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button type="button" wire:click="deleteComment"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </button>
                    <button type="button" wire:click="cancelDelete"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let wavesurfer;
    let readyFired = false;
    let persistedDuration = 0;
    let audioLoaded = false;
    let audioLoadPromise = null; // Add this to track the loading promise
    let lastPlayedPosition = 0; // Track the last played position

    // Helper function to format time
    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    // We need to ensure we initialize audio properly, avoiding duplicate loads
    const initializeAudio = (audioUrl) => {
        // If we've already started loading the audio, return the existing promise
        if (audioLoadPromise) {
            console.log('Audio loading already in progress, reusing promise');
            return audioLoadPromise;
        }

        // If already loaded, just resolve immediately
        if (audioLoaded) {
            console.log('Audio already loaded, resolving immediately');
            return Promise.resolve();
        }

        console.log('Starting new audio load');

        // Create the loading promise
        audioLoadPromise = new Promise((resolve) => {
            // Set flag before loading to prevent race conditions
            audioLoaded = true;

            // Create an audio element we can control (this alone might trigger a preload)
            const audio = new Audio();
            audio.preload = 'none'; // Try to prevent auto-loading
            wavesurfer.options.media = audio;

            // Set up the ready handler first
            wavesurfer.once('ready', () => {
                console.log('WaveSurfer ready event fired from audio load');
                persistedDuration = wavesurfer.getDuration();
                document.getElementById('totalDuration').textContent = formatTime(persistedDuration);

                // Update comment markers with the actual duration
                updateCommentMarkers(persistedDuration);

                // Mark our promise as resolved - this is a custom property
                audioLoadPromise.isResolved = true;

                resolve();
            });

            // Then load the URL
            console.log('Calling WaveSurfer load with URL');
            wavesurfer.load(audioUrl);
        });

        return audioLoadPromise;
    };

    // Helper function to update comment markers when duration changes
    const updateCommentMarkers = (duration) => {
        console.log('updateCommentMarkers called with duration:', duration);

        // Check for valid duration
        if (!duration || isNaN(duration) || duration <= 0) {
            console.warn('Invalid duration value:', duration);
            duration = 1; // Fallback to prevent division by zero
        }

        // Update the Livewire component's duration property using the instance ID finder method
        const livewireComponent = Livewire.find('{{ $_instance->getId() }}');
        if (livewireComponent) {
            livewireComponent.set('duration', duration);
        } else {
            console.error("Could not find Livewire component instance to update duration.");
        }

        // Force a refresh to update the comment markers (may need adjustment based on how comments load)
        // If comments are part of the main component render cycle, setting duration might be enough.
        // If they need explicit refresh, dispatching an event might be better.
        Livewire.dispatch('refresh'); // Consider if this is the best approach or if a targeted event is better
        console.log('Comment markers update triggered with duration:', duration);
    };

    document.addEventListener('livewire:initialized', () => {
        console.log('Initializing WaveSurfer component');
        const livewireComponentId = '{{ $_instance->getId() }}'; // Get the Livewire component ID

        // Debounce function to limit UI updates or other operations
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Create a function to dispatch critical Livewire events with debouncing
        const dispatchLivewireEvent = debounce((eventName, data = {}) => {
             const component = Livewire.find(livewireComponentId);
             if (component) {
                 component.dispatch(eventName, data);
             } else {
                 // Fallback or global dispatch if needed, though usually targetted is better
                 Livewire.dispatch(eventName, data);
             }
        }, 500); // Dispatch at most every 500ms

        // Track current timestamp in a variable without sending to server
        let currentPlayerTime = 0;

        // Initialize WaveSurfer
        wavesurfer = WaveSurfer.create({
            container: '#waveform',
            waveColor: '#d1d5db',
            progressColor: '#4f46e5',
            cursorColor: '#4f46e5',
            barWidth: 4,
            barRadius: 3,
            cursorWidth: 1,
            height: 128,
            //barGap: 2,
            normalize: true,
            responsive: true,
            fillParent: true,
            //minPxPerSec: 2, // Ensure minimum zoom level
            splitChannels: false, // Keep as one waveform
            dragToSeek: true, // Allow seeking by dragging
        });

        // Load audio file
        const audioUrl = @js($file -> fullFilePath);
        console.log('Loading audio URL:', audioUrl);

        // Check if we have pre-generated waveform data
        const hasPreGeneratedPeaks = @js($file -> waveform_processed && $file -> waveform_peaks);

        if (hasPreGeneratedPeaks) {
            console.log('Using pre-generated waveform data');
            // Load audio with pre-generated peaks
            const peaks = @js($file -> waveform_peaks_array);

            // Debug the peaks data
            console.log('Peaks data type:', typeof peaks);
            console.log('Is peaks an array?', Array.isArray(peaks));
            console.log('Peaks sample:', peaks && peaks.length > 0 ? peaks.slice(0, 10) : peaks);

            // Set peaks directly - this will visualize without loading audio
            if (peaks && Array.isArray(peaks[0])) {
                // We have min/max peaks format
                const minPeaks = peaks.map(point => point[0]);
                const maxPeaks = peaks.map(point => point[1]);

                // Initialize the waveform with the pre-generated peaks
                // Use options instead of setPeaks to avoid auto-loading
                wavesurfer.options.peaks = [minPeaks, maxPeaks];
                console.log('Set min/max peaks');

                // Use the stored duration if available, otherwise estimate
                const storedDuration = @js($file-> duration ?? null);

                // Set a fake duration based on the peaks array length if stored one is not available
                const fakeLength = maxPeaks && maxPeaks.length ? maxPeaks.length : 0;
                // Avoid division by zero and ensure we have a positive value
                const estimatedDuration = fakeLength > 0 ? (fakeLength / 67) * 60 : 60; // Default to 60 seconds if we can't calculate

                // Use actual duration if available, otherwise use estimate
                const displayDuration = storedDuration || estimatedDuration;
                console.log('Duration info:', { stored: storedDuration, estimated: estimatedDuration, using: displayDuration });

                wavesurfer.options.duration = displayDuration;
                persistedDuration = displayDuration;

                // Force a redraw to show the peaks
                if (typeof wavesurfer.drawBuffer === 'function') {
                    wavesurfer.drawBuffer();
                }

                 // Mark waveform as "ready-like" state for the visualization
                 setTimeout(() => {
                     document.getElementById('waveform').classList.add('loaded');
                     document.getElementById('totalDuration').textContent = formatTime(displayDuration);

                     // Update comment markers with the duration
                     updateCommentMarkers(displayDuration);

                     // Set initial state (paused) - important for peak-only load
                     window.dispatchEvent(new CustomEvent('playback-state-changed', {
                         detail: { playing: false }
                     }));
                 }, 100);
             } else {
                console.log('Peaks format is not as expected, falling back to normal loading');
                initializeAudio(audioUrl); // Call initializeAudio here
             }

            // Audio will be loaded on first play - audioLoaded is false initially
        } else {
             console.log('No pre-generated waveform data available, generating on the fly');
             initializeAudio(audioUrl); // Call initializeAudio here
        }

        // Listen for the Alpine.js play/pause toggle event
        window.addEventListener('toggle-playback', (event) => {
            console.log('Toggle playback event received:', event.detail.playing);

            try {
                if (event.detail.playing) {
                    // Check if audio is already loaded (only relevant if peaks were pre-gen)
                    if (hasPreGeneratedPeaks && !audioLoaded) {
                        console.log('First play - loading audio');

                        // Initialize audio and then play
                        initializeAudio(audioUrl).then(() => { // Pass audioUrl
                            console.log('Audio loaded, starting playback');
                            setTimeout(() => {
                                wavesurfer.play();
                                // Ensure the time display is updated after playback starts
                                updateTimeDisplay();
                            }, 100);
                        });

                        return; // Wait for audio to load before playing
                    }

                    // Audio might be in process of loading even if audioLoaded flag is true
                    // Or if peaks weren't pre-generated, initializeAudio might have been called earlier
                    if (audioLoadPromise && !audioLoadPromise.isResolved) {
                        console.log('Audio still loading, waiting to play');
                        audioLoadPromise.then(() => {
                            console.log('Now playing after audio load completed');
                            wavesurfer.play();
                        });
                        return;
                    }

                    // Normal play when audio is already loaded or load wasn't needed initially
                    console.log('Playing with already loaded audio or pre-generated peaks');
                    const playPromise = wavesurfer.play();

                    // Modern browsers return a promise from audio.play()
                    if (playPromise !== undefined) {
                        playPromise
                            .then(() => {
                                console.log('Playback started successfully');
                            })
                            .catch(error => {
                                console.error('Playback failed:', error);
                                // If playback fails, update UI to reflect paused state
                                window.dispatchEvent(new CustomEvent('playback-state-changed', {
                                    detail: { playing: false }
                                }));
                            });
                    }
                } else {
                    wavesurfer.pause();
                    console.log('Playback paused');
                }
            } catch (e) {
                console.error('Error toggling playback:', e);
                // If there's an error, ensure UI reflects paused state
                window.dispatchEvent(new CustomEvent('playback-state-changed', {
                    detail: { playing: false }
                }));
            }
        });

        // Before unloading the page, stop any audio to prevent memory leaks
        window.addEventListener('beforeunload', () => {
            if (wavesurfer) {
                wavesurfer.pause();
                // Don't destroy as it might cause errors if already in process of unloading
            }
        });

        wavesurfer.on('ready', () => {
            console.log('WaveSurfer ready event fired');

            // Get actual duration or fallback to estimated duration if needed
            persistedDuration = wavesurfer.getDuration() || persistedDuration; // Use WS duration if available
            document.getElementById('totalDuration').textContent = formatTime(persistedDuration);
            document.getElementById('waveform').classList.add('loaded');

            // Prevent duplicate handling if loading audio after setting peaks
            if (!readyFired) {
                readyFired = true;

                // Set initial state (paused) if not already set by peak load
                 if (!hasPreGeneratedPeaks) {
                     window.dispatchEvent(new CustomEvent('playback-state-changed', {
                         detail: { playing: false }
                     }));
                 }

                // Update comment markers with the actual duration
                updateCommentMarkers(persistedDuration);

                // Use a deferred dispatch to avoid batching with other updates
                setTimeout(() => {
                    // Dispatch our custom event to signal waveform is ready
                    dispatchLivewireEvent('waveformReady');
                }, 100);
            }
        });

        // Ensure current time display is always accurate
        const updateTimeDisplay = () => {
            if (!wavesurfer) return;

            // We need to check if getCurrentTime is null/undefined, not if it's falsy
            // since 0 is a valid time position but evaluates to false in JS
            const currentTime = wavesurfer.getCurrentTime() !== undefined ?
                wavesurfer.getCurrentTime() : lastPlayedPosition;

            // Only update lastPlayedPosition if we have a valid non-zero time or if we're at the start
            if (currentTime > 0 || wavesurfer.isPlaying()) {
                lastPlayedPosition = currentTime;
            }

            // Ensure duration is available, fallback if necessary
             const duration = persistedDuration || (wavesurfer.getDuration && wavesurfer.getDuration()) || 0;

            document.getElementById('currentTime').textContent = formatTime(currentTime);
            document.getElementById('totalDuration').textContent = formatTime(duration);
        };

        wavesurfer.on('play', () => {
            console.log('WaveSurfer play event');

            // Notify Alpine.js about the state change
            window.dispatchEvent(new CustomEvent('playback-state-changed', {
                detail: { playing: true }
            }));

            // Notify Livewire
            // dispatchLivewireEvent('playbackStarted'); // Decide if needed
        });

        wavesurfer.on('pause', () => {
            console.log('WaveSurfer pause event');

            // Explicitly grab the current time when pausing and store it
            const pausePosition = wavesurfer.getCurrentTime();
            if (pausePosition !== undefined && pausePosition >= 0) { // Check >= 0
                 lastPlayedPosition = pausePosition;
                 console.log('Storing pause position:', lastPlayedPosition);
             }

            // Make sure current time doesn't reset when pausing
            updateTimeDisplay();

            // Notify Alpine.js about the state change
            window.dispatchEvent(new CustomEvent('playback-state-changed', {
                detail: { playing: false }
            }));

            // Notify Livewire
            // dispatchLivewireEvent('playbackPaused'); // Decide if needed
        });

        wavesurfer.on('finish', () => {
            console.log('WaveSurfer finish event');

            // Reset last played position to the start or end? Let's set to end.
             lastPlayedPosition = persistedDuration || 0;
             updateTimeDisplay(); // Show end time

            // Notify Alpine.js about the state change
            window.dispatchEvent(new CustomEvent('playback-state-changed', {
                detail: { playing: false }
            }));

            // Notify Livewire
            // dispatchLivewireEvent('playbackFinished'); // Decide if needed
        });

        // Update time display during playback
        wavesurfer.on('audioprocess', () => {
            const currentTime = wavesurfer.getCurrentTime();
            currentPlayerTime = currentTime; // Store locally without sending to server
             if (currentTime !== undefined && currentTime >= 0) {
                 lastPlayedPosition = currentTime; // Update the last position
             }

            // Update the time display
            updateTimeDisplay();
        });

        // Comment marker click handler
        window.addEventListener('comment-marker-clicked', event => {
            console.log('Comment marker clicked at timestamp:', event.detail.timestamp);
            const timestamp = event.detail.timestamp;
            const duration = persistedDuration || (wavesurfer.getDuration && wavesurfer.getDuration()) || 0;

            if (duration <= 0) {
                 console.warn("Cannot seek, duration unknown or zero.");
                 return;
             }

            // Handle seeking for pre-generated peaks when audio isn't loaded yet
            if (hasPreGeneratedPeaks && !audioLoaded) {
                // Load audio first, then seek when ready
                console.log('Loading audio before seeking from comment marker');

                initializeAudio(audioUrl).then(() => { // Pass audioUrl
                    console.log('Audio loaded from comment marker, seeking to', timestamp);
                    const seekDuration = wavesurfer.getDuration(); // Get fresh duration after load
                     if (seekDuration > 0) {
                         wavesurfer.seekTo(timestamp / seekDuration);
                     } else {
                        console.warn("Cannot seek after load, duration still zero.");
                     }
                     wavesurfer.pause(); // Ensure paused after seek
                    // Update display after seeking
                    updateTimeDisplay();
                     // Ensure Alpine state is paused
                     window.dispatchEvent(new CustomEvent('playback-state-changed', { detail: { playing: false } }));
                });

                // Notify Alpine.js about state change (to paused) immediately
                window.dispatchEvent(new CustomEvent('playback-state-changed', {
                    detail: { playing: false }
                }));

                return;
            }

            // Audio might be in process of loading even if audioLoaded flag is true
            if (audioLoadPromise && !audioLoadPromise.isResolved) {
                console.log('Audio still loading, waiting to seek from comment marker');
                audioLoadPromise.then(() => {
                    console.log('Seeking after audio load completed from comment marker');
                     const seekDuration = wavesurfer.getDuration();
                     if (seekDuration > 0) {
                         wavesurfer.seekTo(timestamp / seekDuration);
                     } else {
                         console.warn("Cannot seek after load, duration still zero.");
                     }
                     wavesurfer.pause(); // Ensure paused after seek
                    updateTimeDisplay();
                     // Ensure Alpine state is paused
                     window.dispatchEvent(new CustomEvent('playback-state-changed', { detail: { playing: false } }));
                });
                return;
            }

            // Normal seeking when audio is already loaded
            console.log('Normal seeking from comment marker, audio already loaded');
            wavesurfer.seekTo(timestamp / duration);
            wavesurfer.pause();

            // Update time display
            updateTimeDisplay();

            // Notify Alpine.js about state change
            window.dispatchEvent(new CustomEvent('playback-state-changed', {
                detail: { playing: false }
            }));
        });

        // Livewire event listeners
        Livewire.on('seekToPosition', ({ timestamp }) => {
            console.log('Livewire event: seekToPosition', timestamp);
            const duration = persistedDuration || (wavesurfer.getDuration && wavesurfer.getDuration()) || 0;

             if (duration <= 0) {
                 console.warn("Cannot seek via Livewire, duration unknown or zero.");
                 return;
             }

            // Handle seeking for pre-generated peaks when audio isn't loaded yet
            if (hasPreGeneratedPeaks && !audioLoaded) {
                // Load audio first, then seek when ready
                console.log('Loading audio before seeking from seekToPosition event');

                initializeAudio(audioUrl).then(() => { // Pass audioUrl
                    console.log('Audio loaded from seekToPosition, seeking to', timestamp);
                    const seekDuration = wavesurfer.getDuration(); // Get fresh duration
                     if (seekDuration > 0) {
                         wavesurfer.seekTo(timestamp / seekDuration);
                     } else {
                         console.warn("Cannot seek after load, duration still zero.");
                     }
                     wavesurfer.pause(); // Ensure paused
                    // Update display after seeking
                    updateTimeDisplay();
                     // Ensure Alpine state is paused
                     window.dispatchEvent(new CustomEvent('playback-state-changed', { detail: { playing: false } }));
                });

                // Notify Alpine.js about state change (to paused) immediately
                window.dispatchEvent(new CustomEvent('playback-state-changed', {
                    detail: { playing: false }
                }));

                return;
            }

            // Audio might be in process of loading even if audioLoaded flag is true
            if (audioLoadPromise && !audioLoadPromise.isResolved) {
                console.log('Audio still loading, waiting to seek from seekToPosition');
                audioLoadPromise.then(() => {
                    console.log('Seeking after audio load completed from seekToPosition');
                     const seekDuration = wavesurfer.getDuration();
                     if (seekDuration > 0) {
                         wavesurfer.seekTo(timestamp / seekDuration);
                     } else {
                         console.warn("Cannot seek after load, duration still zero.");
                     }
                     wavesurfer.pause(); // Ensure paused
                    updateTimeDisplay();
                     // Ensure Alpine state is paused
                     window.dispatchEvent(new CustomEvent('playback-state-changed', { detail: { playing: false } }));
                });
                return;
            }

            // Normal seeking when audio is already loaded
            console.log('Normal seeking from seekToPosition, audio already loaded');
            wavesurfer.seekTo(timestamp / duration);
            wavesurfer.pause();

            // Update time display
            updateTimeDisplay();

            // Notify Alpine.js about state change
            window.dispatchEvent(new CustomEvent('playback-state-changed', {
                detail: { playing: false }
            }));
        });

        Livewire.on('pausePlayback', () => {
            console.log('Livewire event: pausePlayback');
            wavesurfer.pause();

            // Notify Alpine.js about state change
            window.dispatchEvent(new CustomEvent('playback-state-changed', {
                detail: { playing: false }
            }));
        });

        // Listen for comment added event (if needed for refresh)
        Livewire.on('commentAdded', () => {
             console.log('Livewire event: commentAdded - triggering refresh');
             // Check if a full component refresh is desired/needed here
             // Maybe only re-fetch/re-render comments part?
             Livewire.dispatch('refresh'); // Or a more specific event
         });

        // Add timeline implementation
        const timeline = document.querySelector('#waveform-timeline');
        if (timeline) {
            const setupTimeline = (renderDuration) => {
                 if (!renderDuration || isNaN(renderDuration) || renderDuration <= 0) {
                     console.warn("Timeline setup skipped: Invalid duration", renderDuration);
                     return;
                 }
                 timeline.innerHTML = ''; // Clear any existing content

                // Create a container for the timeline
                const container = document.createElement('div');
                container.className = 'timeline-container';
                container.style.position = 'relative';
                container.style.height = '100%';

                // Determine appropriate time interval based on duration
                let interval = 30; // Default 30 seconds

                if (renderDuration < 60) {
                    interval = 10; // 10 seconds for short audio
                } else if (renderDuration < 180) {
                    interval = 20; // 20 seconds for medium audio
                } else if (renderDuration > 600) {
                    interval = 60; // 1 minute for long audio
                }

                // Create time marks at regular intervals
                for (let time = 0; time <= renderDuration; time += interval) {
                    if (time > renderDuration && time !== interval) break; // Avoid mark way past end unless it's the first

                    const percent = time / renderDuration;
                    const mark = document.createElement('div');
                    mark.className = 'timeline-mark';
                    mark.style.left = `${percent * 100}%`;
                    mark.textContent = formatTime(time);

                    container.appendChild(mark);
                }

                // Always add the end time if it's not exactly divisible by the interval and > 0
                 const lastTime = Math.floor(renderDuration);
                 if (lastTime > 0 && lastTime % interval !== 0) {
                     const percent = lastTime / renderDuration;
                     const mark = document.createElement('div');
                     mark.className = 'timeline-mark';
                     mark.style.left = `${percent * 100}%`;
                     // To avoid clutter, only show end time if sufficiently far from last interval mark
                     const lastIntervalTime = Math.floor(lastTime / interval) * interval;
                     if ((lastTime - lastIntervalTime) / renderDuration > 0.05) { // e.g., if end is > 5% past last mark
                         mark.textContent = formatTime(lastTime);
                     }
                     container.appendChild(mark);
                 }


                timeline.appendChild(container);
             };

             // Setup timeline if duration is known from pre-generated peaks
             if (hasPreGeneratedPeaks && persistedDuration > 0) {
                 console.log("Setting up timeline with pre-generated duration:", persistedDuration);
                 setupTimeline(persistedDuration);
             }

             // Also set up timeline when wavesurfer is ready (for non-pre-generated case)
            wavesurfer.on('ready', () => {
                 const readyDuration = wavesurfer.getDuration();
                 console.log("Setting up timeline on ready event with duration:", readyDuration);
                 setupTimeline(readyDuration);
             });

             // Re-render timeline if comments update causes duration change (unlikely but possible)
             Livewire.hook('morph.updated', ({ el, component }) => {
                 // Check if the duration property specifically changed if possible,
                 // or just re-render timeline if this component updates.
                 if (component.id === livewireComponentId) {
                     const currentDuration = persistedDuration || (wavesurfer && wavesurfer.getDuration());
                     if (currentDuration > 0) {
                        // Small delay to ensure DOM might be settled
                        setTimeout(() => setupTimeline(currentDuration), 50);
                     }
                 }
             });
        }
    });
</script>
{{-- blade-formatter-enable --}}
<style>
    /* Custom timeline styles */
    .timeline-mark {
        position: absolute;
        top: 0;
        font-size: 10px;
        color: #6b7280;
        transform: translateX(-50%);
    }

    .timeline-container {
        border-top: 1px solid #e5e7eb;
        padding-top: 4px;
    }
</style>
@endpush