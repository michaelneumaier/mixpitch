@props(['component', 'project', 'conversationItems', 'workflowColors' => [], 'semanticColors' => []])

<flux:card class="mb-2">
    <!-- Header with Client Information -->
    <div class="mb-6 flex items-start justify-between">
        <div class="flex items-center gap-3">
            <flux:icon.chat-bubble-left-ellipsis variant="solid"
                class="{{ $workflowColors['icon'] }} h-8 w-8" />
            <div>
                <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                    Client Communication
                </flux:heading>
                <flux:subheading class="{{ $workflowColors['text_muted'] }}">
                    Connect with your client and track project conversations
                </flux:subheading>
            </div>
        </div>
        
        <!-- Client Info Badge -->
        @if ($project->client_name || $project->client_email)
            <div class="{{ $workflowColors['accent_bg'] }} {{ $workflowColors['accent_border'] }} rounded-lg border p-3">
                <div class="flex items-center gap-2 text-sm">
                    <flux:icon.user-circle class="{{ $workflowColors['icon'] }} h-4 w-4" />
                    <div class="text-right">
                        @if ($project->client_name)
                            <div class="{{ $workflowColors['text_primary'] }} font-medium">{{ $project->client_name }}</div>
                        @endif
                        @if ($project->client_email)
                            <div class="{{ $workflowColors['text_muted'] }} text-xs">{{ $project->client_email }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Integrated Communication View -->
    <div x-data="{ 
        showMessageForm: false,
        init() {
            // Listen for successful message submission to close form
            Livewire.on('messageAdded', () => {
                this.showMessageForm = false;
            });
        }
    }" class="space-y-4">
        <!-- Communication History -->
        <div class="space-y-1 max-h-96 overflow-y-auto">
            @forelse($conversationItems as $item)
                @php
                    // Get styling classes based on item type
                    $borderColor = match ($item['type']) {
                        'client_message' => 'border-l-blue-400',
                        'producer_message' => 'border-l-purple-400',
                        'approval' => 'border-l-green-400',
                        'revision_request' => 'border-l-amber-400',
                        'recall' => 'border-l-orange-400',
                        'status_update' => 'border-l-gray-400',
                        'file_activity' => 'border-l-indigo-400',
                        default => 'border-l-gray-300'
                    };
                    
                    $bgColor = match ($item['type']) {
                        'client_message' => 'bg-blue-500',
                        'producer_message' => 'bg-purple-500',
                        'approval' => 'bg-green-500',
                        'revision_request' => 'bg-amber-500',
                        'recall' => 'bg-orange-500',
                        'status_update' => 'bg-gray-500',
                        'file_activity' => 'bg-indigo-500',
                        default => 'bg-gray-400'
                    };
                    
                    $icon = match ($item['type']) {
                        'client_message' => 'fas fa-comment',
                        'producer_message' => 'fas fa-reply',
                        'approval' => 'fas fa-check',
                        'revision_request' => 'fas fa-edit',
                        'recall' => 'fas fa-undo',
                        'status_update' => 'fas fa-exchange-alt',
                        'file_activity' => 'fas fa-file',
                        default => 'fas fa-circle'
                    };
                    
                    $title = match ($item['type']) {
                        'client_message' => 'Client Message',
                        'producer_message' => 'Your Message',
                        'approval' => 'Client Approval',
                        'revision_request' => 'Revision Request',
                        'recall' => 'Submission Recalled',
                        'status_update' => 'Status Update',
                        'file_activity' => 'File Activity',
                        default => 'Activity'
                    };
                @endphp
                
                <div class="{{ $workflowColors['accent_bg'] }} {{ $borderColor }} rounded border-l-3 p-2 group relative">
                    <div class="flex items-start gap-2">
                        <!-- Compact Icon -->
                        <div class="{{ $bgColor }} flex h-5 w-5 items-center justify-center rounded">
                            <i class="{{ $icon }} text-xs text-white"></i>
                        </div>
                        
                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <!-- Header Line -->
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="{{ $workflowColors['text_primary'] }} text-sm font-medium">{{ $title }}</span>
                                @if($item['user'])
                                    <span class="{{ $workflowColors['text_muted'] }} text-xs">by {{ $item['user']->name }}</span>
                                @endif
                                <span class="{{ $workflowColors['text_muted'] }} text-xs">{{ $item['date']->diffForHumans() }}</span>
                            </div>
                            
                            <!-- Message Content (if exists) -->
                            @if($item['content'])
                                <div class="mt-1 text-sm {{ $workflowColors['text_secondary'] }} leading-snug">
                                    {{ $item['content'] }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Delete Button (only for producer messages) -->
                    @if($item['type'] === 'producer_message' && isset($item['event']))
                        <button 
                            @click="if(confirm('Delete this message?')) $wire.deleteProducerComment({{ $item['event']->id }})"
                            class="absolute top-1 right-1 h-4 w-4 rounded-full bg-red-500 text-white hover:bg-red-600 flex items-center justify-center transition-colors duration-200"
                            title="Delete message">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    @endif
                </div>
            @empty
                <div class="py-6 text-center">
                    <div class="{{ $workflowColors['accent_bg'] }} mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full">
                        <flux:icon.chat-bubble-left-ellipsis class="{{ $workflowColors['icon'] }} h-5 w-5" />
                    </div>
                    <flux:heading size="sm" class="{{ $workflowColors['text_primary'] }} mb-1">
                        No communication yet
                    </flux:heading>
                    <flux:text size="xs" class="{{ $workflowColors['text_muted'] }}">
                        Messages and updates will appear here
                    </flux:text>
                </div>
            @endforelse
        </div>

        <!-- Send Message Section -->
        <div class="border-t {{ $workflowColors['accent_border'] }} pt-4">
            <!-- Send Message Button (collapsed state) -->
            <div x-show="!showMessageForm">
                <flux:button 
                    @click="showMessageForm = true; $nextTick(() => $refs.messageTextarea.focus())" 
                    variant="outline" 
                    icon="paper-airplane"
                    class="w-full">
                    Send Message
                </flux:button>
            </div>

            <!-- Message Form (expanded state) -->
            <div x-show="showMessageForm" x-transition>
                <form wire:submit.prevent="addProducerComment">
                    <flux:field>
                        <flux:label for="newComment">Message to Client</flux:label>
                        <flux:textarea 
                            wire:model.defer="newComment" 
                            id="newComment" 
                            x-ref="messageTextarea"
                            rows="4"
                            placeholder="Share updates, ask questions, or provide additional context..." />
                        <flux:error name="newComment" />
                    </flux:field>

                    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="{{ $semanticColors['success']['bg'] }} {{ $semanticColors['success']['border'] }} flex-1 rounded-lg border p-2">
                            <div class="flex items-center gap-2">
                                <flux:icon.information-circle
                                    class="{{ $semanticColors['success']['icon'] }} h-4 w-4" />
                                <span class="{{ $semanticColors['success']['text'] }} text-xs">
                                    Client will receive email notification
                                </span>
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
                                icon="paper-airplane"
                                wire:loading.attr="disabled"
                                size="sm">
                                <span wire:loading.remove>Send</span>
                                <span wire:loading>Sending...</span>
                            </flux:button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</flux:card>