@props(['project', 'pitch', 'pitchEvents' => null])

@php
    // Get communication events from pitch events or use provided data
    $events = $pitchEvents ?? ($pitch ? $pitch->events()->orderBy('created_at', 'desc')->get() : collect());
    
    // Process events into conversation items similar to manage-client-project
    $conversationItems = $events->map(function ($event) {
        return [
            'id' => $event->id,
            'type' => $event->event_type,
            'content' => $event->comment,
            'date' => $event->created_at,
            'user' => $event->user,
            'metadata' => $event->metadata ?? [],
            'event' => $event,
        ];
    });
@endphp

<flux:card class="mb-6">
    <!-- Header -->
    <div class="mb-6 flex items-start justify-between">
        <div class="flex items-center gap-3">
            <flux:icon.chat-bubble-left-ellipsis class="text-blue-600 h-8 w-8" />
            <div>
                <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">
                    Project Communication
                </flux:heading>
                <flux:subheading class="text-gray-600 dark:text-gray-400">
                    Messages and updates about your project
                </flux:subheading>
            </div>
        </div>
        
        <!-- Producer Info Badge -->
        @if ($pitch && $pitch->user)
            <div class="bg-purple-50 border-purple-200 dark:bg-purple-900/20 dark:border-purple-800 rounded-lg border p-3">
                <div class="flex items-center gap-2 text-sm">
                    <flux:icon.musical-note class="text-purple-600 h-4 w-4" />
                    <div class="text-right">
                        <div class="text-purple-900 dark:text-purple-100 font-medium">{{ $pitch->user->name }}</div>
                        <div class="text-purple-600 dark:text-purple-400 text-xs">Producer</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Integrated Communication View -->
    <div x-data="{ 
        showMessageForm: false 
    }" class="space-y-4">
        <!-- Communication History -->
        <div class="space-y-3 max-h-96 overflow-y-auto">
            @forelse($conversationItems as $item)
                @php
                    // Get styling classes based on item type
                    $borderColor = match ($item['type']) {
                        'client_comment', 'client_message' => 'border-l-blue-400',
                        'producer_comment', 'producer_message' => 'border-l-purple-400',
                        'client_approval' => 'border-l-green-400',
                        'client_revisions_requested' => 'border-l-amber-400',
                        'status_change' => 'border-l-gray-400',
                        'file_uploaded' => 'border-l-indigo-400',
                        default => 'border-l-gray-300'
                    };
                    
                    $bgColor = match ($item['type']) {
                        'client_comment', 'client_message' => 'bg-blue-500',
                        'producer_comment', 'producer_message' => 'bg-purple-500',
                        'client_approval' => 'bg-green-500',
                        'client_revisions_requested' => 'bg-amber-500',
                        'status_change' => 'bg-gray-300',
                        'file_uploaded' => 'bg-indigo-300',
                        default => 'bg-gray-300'
                    };
                    
                    $title = match ($item['type']) {
                        'client_comment', 'client_message' => 'Your Message',
                        'producer_comment', 'producer_message' => 'Producer Message',
                        'client_approval' => 'You Approved',
                        'client_revisions_requested' => 'You Requested Revisions',
                        'status_change' => 'Status Update',
                        'file_uploaded' => 'File Activity',
                        default => 'Activity'
                    };
                @endphp
                
                <div class="bg-gray-50 dark:bg-gray-800 {{ $borderColor }} rounded border-l-4 p-4 group relative">
                    <div class="flex items-start gap-3">
                        <!-- Icon -->
                        <div class="{{ $bgColor }} flex h-6 w-6 items-center justify-center rounded-full">
                            @if($item['type'] === 'client_comment' || $item['type'] === 'client_message')
                                <flux:icon.user class="h-3 w-3 text-white" />
                            @elseif($item['type'] === 'producer_comment' || $item['type'] === 'producer_message')
                                <flux:icon.musical-note class="h-3 w-3 text-white" />
                            @elseif($item['type'] === 'client_approval')
                                <flux:icon.check class="h-3 w-3 text-white" />
                            @elseif($item['type'] === 'client_revisions_requested')
                                <flux:icon.pencil class="h-3 w-3 text-white" />
                            @elseif($item['type'] === 'status_change')
                                <flux:icon.arrow-path class="h-3 w-3 text-white" />
                            @elseif($item['type'] === 'file_uploaded')
                                <flux:icon.document class="h-3 w-3 text-white" />
                            @else
                                <flux:icon.chat-bubble-left class="h-3 w-3 text-white" />
                            @endif
                        </div>
                        
                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <!-- Header Line -->
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <flux:heading size="sm" class="text-gray-900 dark:text-gray-100">{{ $title }}</flux:heading>
                                @if($item['user'])
                                    <flux:text size="xs" class="text-gray-500 dark:text-gray-400">by {{ $item['user']->name }}</flux:text>
                                @endif
                                <flux:text size="xs" class="text-gray-500 dark:text-gray-400">{{ $item['date']->diffForHumans() }}</flux:text>
                            </div>
                            
                            <!-- Message Content -->
                            @if($item['content'])
                                <flux:text size="sm" class="text-gray-700 dark:text-gray-300 leading-relaxed">
                                    {{ $item['content'] }}
                                </flux:text>
                            @endif

                            <!-- Status change details -->
                            @if($item['type'] === 'status_change' && isset($item['metadata']['from_status']) && isset($item['metadata']['to_status']))
                                <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                    Changed from <span class="font-medium">{{ ucwords(str_replace('_', ' ', $item['metadata']['from_status'])) }}</span> 
                                    to <span class="font-medium">{{ ucwords(str_replace('_', ' ', $item['metadata']['to_status'])) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-8 text-center">
                    <div class="bg-blue-50 dark:bg-blue-900/20 mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full">
                        <flux:icon.chat-bubble-left-ellipsis class="text-blue-600 h-6 w-6" />
                    </div>
                    <flux:heading size="sm" class="text-gray-900 dark:text-gray-100 mb-2">
                        No messages yet
                    </flux:heading>
                    <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                        Communication with your producer will appear here
                    </flux:text>
                </div>
            @endforelse
        </div>

        <!-- Send Message Section -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <!-- Send Message Button (collapsed state) -->
            <div x-show="!showMessageForm">
                <flux:button 
                    @click="showMessageForm = true; $nextTick(() => $refs.clientMessageTextarea.focus())" 
                    variant="outline" 
                    class="w-full">
                    <flux:icon.paper-airplane class="mr-2" />
                    Send Message to Producer
                </flux:button>
            </div>

            <!-- Message Form (expanded state) -->
            <div x-show="showMessageForm" x-transition>
                <form method="POST" action="{{ URL::temporarySignedRoute('client.portal.comments.store', now()->addHours(24), ['project' => $project->id]) }}">
                    @csrf
                    <flux:field>
                        <flux:label for="clientMessage">Message to Producer</flux:label>
                        <flux:textarea 
                            name="comment"
                            id="clientMessage" 
                            x-ref="clientMessageTextarea"
                            rows="4"
                            placeholder="Ask questions, provide feedback, or share additional details about your project..."
                            required />
                    </flux:field>

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800 flex-1 rounded-lg border p-3">
                            <div class="flex items-center gap-2">
                                <flux:icon.information-circle class="text-green-600 h-4 w-4" />
                                <flux:text size="xs" class="text-green-700 dark:text-green-300">
                                    Producer will receive email notification
                                </flux:text>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <flux:button 
                                type="button" 
                                @click="showMessageForm = false" 
                                variant="outline" 
                                size="sm">
                                Cancel
                            </flux:button>
                            <flux:button 
                                type="submit" 
                                variant="primary" 
                                size="sm">
                                <flux:icon.paper-airplane class="mr-1" />
                                Send Message
                            </flux:button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</flux:card>