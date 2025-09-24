@props(['project', 'pitch'])

<flux:card class="mb-6">
    <div class="mb-6 flex items-center gap-3">
        <flux:icon.chat-bubble-left-right class="text-purple-500" />
        <div>
            <flux:heading size="lg">Project Communication</flux:heading>
            <flux:subheading>Stay in touch with your producer throughout the project</flux:subheading>
        </div>
    </div>

    {{-- Comment Form --}}
    <div class="mb-6 rounded-xl bg-blue-50 p-6 dark:bg-blue-900/20">
        <form action="{{ URL::temporarySignedRoute('client.portal.comments.store', now()->addHours(24), ['project' => $project->id]) }}"
            method="POST">
            @csrf
            <flux:field>
                <flux:label for="comment">Add a Comment</flux:label>
                <flux:textarea name="comment" id="comment" rows="4" required
                    placeholder="Share your thoughts, ask questions, or provide additional feedback...">
                    {{ old('comment') }}</flux:textarea>
                @error('comment')
                    <flux:error name="comment" />
                @enderror
            </flux:field>
            <flux:button type="submit" variant="primary" class="mt-4">
                <flux:icon.paper-airplane class="mr-2" />
                Submit Comment
            </flux:button>
        </form>
    </div>

    {{-- Comment History --}}
    <div class="space-y-4">
        <div class="mb-4 flex items-center gap-2">
            <flux:icon.clock class="text-gray-600" />
            <flux:heading size="sm">Project Activity</flux:heading>
        </div>

        @forelse ($pitch->events->whereIn('event_type', ['client_comment', 'producer_comment', 'status_change', 'client_approved', 'client_revisions_requested']) as $event)
            <div
                class="{{ $event->event_type === 'client_comment' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200' : 'bg-gray-50 dark:bg-gray-800 border-gray-200' }} rounded-xl border p-4">
                <div class="flex items-start gap-3">
                    <div
                        class="{{ $event->event_type === 'client_comment' ? 'bg-blue-500' : 'bg-gray-500' }} flex h-8 w-8 items-center justify-center rounded-lg">
                        @if ($event->event_type === 'client_comment')
                            <flux:icon.user class="h-4 w-4 text-white" />
                        @else
                            <flux:icon.user-circle class="h-4 w-4 text-white" />
                        @endif
                    </div>

                    <div class="flex-1">
                        <div class="mb-2 flex items-center justify-between">
                            <flux:heading size="sm"
                                class="{{ $event->event_type === 'client_comment' ? 'text-blue-900' : 'text-gray-900' }}">
                                @if ($event->event_type === 'client_comment' && isset($event->metadata['client_email']))
                                    You ({{ $event->metadata['client_email'] }})
                                @elseif($event->user)
                                    {{ $event->user->name }} (Producer)
                                @else
                                    System Event [{{ $event->event_type }}]
                                @endif
                            </flux:heading>
                            <flux:text size="xs" class="text-gray-500">
                                {{ $event->created_at->diffForHumans() }}</flux:text>
                        </div>

                        @if ($event->comment)
                            <flux:text class="whitespace-pre-wrap">{{ $event->comment }}</flux:text>
                        @endif

                        @if ($event->status)
                            <flux:badge variant="ghost" size="sm" class="mt-2">
                                Status: {{ Str::title(str_replace('_', ' ', $event->status)) }}
                            </flux:badge>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="py-8 text-center">
                <flux:icon.chat-bubble-left-ellipsis class="mx-auto mb-3 text-gray-400" size="xl" />
                <flux:heading size="sm" class="mb-2">No activity yet</flux:heading>
                <flux:subheading>Comments and project updates will appear here</flux:subheading>
            </div>
        @endforelse
    </div>
</flux:card>

