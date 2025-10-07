<flux:card class="mb-2 border-amber-200 bg-amber-50">
    <div>
        <flux:heading size="lg">
            <flux:icon name="arrow-uturn-left" class="mr-2" />
            Respond to Feedback
        </flux:heading>
    </div>
    <div>
        <!-- Client Feedback Display -->
        @if ($this->statusFeedbackMessage)
            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500">
                        <flux:icon name="chat-bubble-left-ellipsis" class="h-4 w-4 text-white" />
                    </div>
                    <div class="flex-1">
                        <div class="mb-2 flex items-center gap-2">
                            <flux:heading size="sm" class="text-blue-900">
                                Client Feedback
                            </flux:heading>
                            @if ($this->latestFeedbackEvent)
                                <flux:text size="xs" class="text-blue-600">
                                    {{ $this->latestFeedbackEvent->created_at_for_user->diffForHumans() }}
                                </flux:text>
                            @endif
                        </div>
                        <div class="rounded bg-white p-3 text-sm text-gray-800 shadow-sm">
                            {{ $this->statusFeedbackMessage }}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- File Comments Summary -->
        @if ($this->fileCommentsSummary->count() > 0)
            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-500">
                        <flux:icon name="document-text" class="h-4 w-4 text-white" />
                    </div>
                    <div class="flex-1">
                        <div class="mb-3 flex items-center gap-2">
                            <flux:heading size="sm" class="text-amber-900">
                                File Comments Overview
                            </flux:heading>
                            <flux:badge variant="warning" size="xs">
                                {{ $this->fileCommentsTotals['unresolved'] }}
                                unresolved of
                                {{ $this->fileCommentsTotals['total'] }} total
                            </flux:badge>
                        </div>

                        <div class="space-y-2">
                            @foreach ($this->fileCommentsSummary as $summary)
                                <div class="rounded bg-white p-3 shadow-sm">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="mb-1 flex items-center gap-2">
                                                <flux:text weight="medium" size="sm" class="text-gray-900">
                                                    {{ $summary['file']->file_name }}
                                                </flux:text>
                                                @if ($summary['needs_attention'])
                                                    <flux:badge variant="warning" size="xs">
                                                        {{ $summary['unresolved_count'] }}
                                                        need attention
                                                    </flux:badge>
                                                @else
                                                    <flux:badge variant="success" size="xs">
                                                        All resolved
                                                    </flux:badge>
                                                @endif
                                            </div>

                                            @if ($summary['latest_unresolved'])
                                                <flux:text size="xs" class="line-clamp-2 text-gray-600">
                                                    Latest:
                                                    "{{ Str::limit($summary['latest_unresolved']->comment, 80) }}"
                                                </flux:text>
                                                <flux:text size="xs" class="text-gray-500">
                                                    —
                                                    {{ $summary['latest_unresolved']->is_client_comment ? ($this->project->client_name ?: 'Client') : 'Producer' }},
                                                    {{ $summary['latest_unresolved']->created_at_for_user->diffForHumans() }}
                                                </flux:text>
                                            @endif
                                        </div>

                                        <div class="text-right">
                                            <flux:text size="xs" class="text-gray-500">
                                                {{ $summary['total_comments'] }}
                                                total
                                            </flux:text>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3 text-center">
                            <flux:text size="xs" class="text-amber-700">
                                💡 Navigate to individual files below to respond to
                                specific comments
                            </flux:text>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Previous Responses Section -->
        @if ($this->previousResponses->count() > 0)
            <div class="mb-6 rounded-lg border border-purple-200 bg-purple-50 p-4">
                <div class="mb-3 flex items-center gap-2">
                    <flux:icon name="chat-bubble-left-right" class="h-5 w-5 text-purple-600" />
                    <flux:heading size="sm" class="text-purple-900">
                        Your Previous Responses
                    </flux:heading>
                    <flux:badge variant="outline" size="xs">
                        {{ $this->previousResponses->count() }}
                    </flux:badge>
                </div>

                <div class="space-y-2">
                    @foreach ($this->previousResponses as $response)
                        <div class="rounded bg-white p-3 shadow-sm">
                            <div class="mb-2 flex items-center gap-2">
                                <flux:text size="xs" class="text-purple-600">
                                    {{ $response->created_at_for_user->format('M j, Y g:i A') }}
                                </flux:text>
                                <flux:text size="xs" class="text-gray-500">
                                    ({{ $response->created_at_for_user->diffForHumans() }})
                                </flux:text>
                            </div>
                            <flux:text size="sm" class="text-gray-800">
                                {{ $response->comment }}
                            </flux:text>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Response Form -->
        <flux:field>
            <flux:label>Your Response to Client Feedback</flux:label>
            <flux:textarea wire:model.lazy="responseToFeedback" rows="4"
                placeholder="Explain what changes you've made in response to the feedback..." />
            <flux:error name="responseToFeedback" />

            <!-- Send Response Button -->
            @if ($this->statusFeedbackMessage || $this->fileCommentsSummary->count() > 0)
                <div class="mt-3">
                    <flux:button wire:click="sendFeedbackResponse" variant="primary" size="sm" icon="paper-airplane"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>Send Response</span>
                        <span wire:loading>Sending...</span>
                    </flux:button>
                    <flux:text size="xs" class="ml-2 text-gray-600">
                        This will notify the client without changing project
                        status
                    </flux:text>
                </div>
            @endif
        </flux:field>
    </div>
</flux:card>
