<div>
    {{-- Do your work, then step back. --}}
    <div class="mb-4">
        <button 
            wire:click="toggleHistory" 
            class="flex items-center text-sm font-medium text-gray-600 hover:text-gray-900"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            {{ $showHistory ? 'Hide History' : 'View History' }}
        </button>
    </div>

    @if ($showHistory)
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Pitch Timeline</h3>
            
            @if ($events->isEmpty())
                <p class="text-gray-500 text-sm italic">No events recorded for this pitch.</p>
            @else
                <div class="relative">
                    <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                    
                    <ul class="space-y-4">
                        @foreach ($events as $event)
                            <li class="relative pl-10">
                                <div class="absolute left-0 top-1 flex items-center justify-center w-10 h-10">
                                    <div class="relative flex items-center justify-center w-8 h-8 rounded-full bg-white border-2 border-gray-200 z-10">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" 
                                            stroke="currentColor" 
                                            class="w-5 h-5 {{ $this->getEventClass($event->event_type, $event->status) }}">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $this->getIconPath($event->event_type) }}" />
                                        </svg>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                    <div class="flex justify-between items-start">
                                        <span class="text-sm font-medium text-gray-800">
                                            {{ $event->user->name ?? 'System' }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            {{ $event->created_at->format('M j, Y g:i A') }}
                                        </span>
                                    </div>
                                    
                                    <div class="mt-1 text-sm text-gray-600">
                                        {{ $event->comment }}
                                    </div>
                                    
                                    @if ($event->event_type == 'status_change')
                                        <div class="mt-2">
                                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                                @if($event->status == \App\Models\Pitch::STATUS_APPROVED) bg-green-100 text-green-800
                                                @elseif($event->status == \App\Models\Pitch::STATUS_DENIED) bg-red-100 text-red-800
                                                @elseif($event->status == \App\Models\Pitch::STATUS_COMPLETED) bg-purple-100 text-purple-800
                                                @elseif($event->status == \App\Models\Pitch::STATUS_READY_FOR_REVIEW) bg-blue-100 text-blue-800
                                                @elseif($event->status == \App\Models\Pitch::STATUS_PENDING_REVIEW) bg-yellow-100 text-yellow-800
                                                @else bg-gray-100 text-gray-800
                                                @endif
                                            ">
                                                {{ ucwords(str_replace('_', ' ', $event->status)) }}
                                            </span>
                                        </div>
                                    @endif
                                    
                                    @if ($event->snapshot)
                                        <div class="mt-2 flex items-center text-sm text-blue-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                            </svg>
                                            <a href="{{ route('pitches.showSnapshot', ['pitch' => $pitch->id, 'pitchSnapshot' => $event->snapshot->id]) }}" class="hover:underline">
                                                View Snapshot (Version {{ $event->snapshot->snapshot_data['version'] ?? '?' }})
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif
</div>
