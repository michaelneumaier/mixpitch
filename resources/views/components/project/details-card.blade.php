@props(['project', 'workflowColors' => []])

@php
    // Workflow-specific color schemes
    $colors = match($project->workflow_type) {
        'standard' => [
            'bg' => 'bg-gradient-to-br from-blue-50/95 to-indigo-50/90 dark:from-blue-950/95 dark:to-indigo-950/90',
            'border' => 'border-blue-200/50 dark:border-blue-700/50',
            'text_primary' => 'text-blue-900 dark:text-blue-100',
            'text_secondary' => 'text-blue-700 dark:text-blue-300',
            'text_muted' => 'text-blue-600 dark:text-blue-400',
            'icon' => 'text-blue-600 dark:text-blue-400',
            'accent_bg' => 'bg-blue-100/80 dark:bg-blue-900/80',
        ],
        'contest' => [
            'bg' => 'bg-gradient-to-br from-amber-50/95 to-yellow-50/90 dark:from-amber-950/95 dark:to-yellow-950/90',
            'border' => 'border-amber-200/50 dark:border-amber-700/50',
            'text_primary' => 'text-amber-900 dark:text-amber-100',
            'text_secondary' => 'text-amber-700 dark:text-amber-300',
            'text_muted' => 'text-amber-600 dark:text-amber-400',
            'icon' => 'text-amber-600 dark:text-amber-400',
            'accent_bg' => 'bg-amber-100/80 dark:bg-amber-900/80',
        ],
        'client_management' => [
            'bg' => 'bg-gradient-to-br from-purple-50/95 to-indigo-50/90 dark:from-purple-950/95 dark:to-indigo-950/90',
            'border' => 'border-purple-200/50 dark:border-purple-700/50',
            'text_primary' => 'text-purple-900 dark:text-purple-100',
            'text_secondary' => 'text-purple-700 dark:text-purple-300',
            'text_muted' => 'text-purple-600 dark:text-purple-400',
            'icon' => 'text-purple-600 dark:text-purple-400',
            'accent_bg' => 'bg-purple-100/80 dark:bg-purple-900/80',
        ],
        default => [
            'bg' => 'bg-gradient-to-br from-gray-50/95 to-slate-50/90 dark:from-gray-950/95 dark:to-slate-950/90',
            'border' => 'border-gray-200/50 dark:border-gray-700/50',
            'text_primary' => 'text-gray-900 dark:text-gray-100',
            'text_secondary' => 'text-gray-700 dark:text-gray-300',
            'text_muted' => 'text-gray-600 dark:text-gray-400',
            'icon' => 'text-gray-600 dark:text-gray-400',
            'accent_bg' => 'bg-gray-100/80 dark:bg-gray-900/80',
        ]
    };

    // Use passed colors or defaults
    $colors = array_merge($colors, $workflowColors);

    // Get collaboration types as array
    $collaborationTypes = is_array($project->collaboration_type)
        ? $project->collaboration_type
        : json_decode($project->collaboration_type ?? '[]', true);

    // Convert deadline from UTC to user timezone for display
    $deadlineFormatted = null;
    $deadlineForInput = null;
    if ($project->deadline) {
        $timezoneService = app(\App\Services\TimezoneService::class);
        $rawDeadline = $project->getRawOriginal('deadline');

        if ($rawDeadline) {
            $utcTime = null;
            if (strpos($rawDeadline, ':') !== false) {
                $utcTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $rawDeadline, 'UTC');
            } else {
                $utcTime = \Carbon\Carbon::createFromFormat('Y-m-d', $rawDeadline, 'UTC')->startOfDay();
            }

            $userTime = $timezoneService->convertToUserTimezone($utcTime, auth()->user());
            $deadlineFormatted = $userTime->format('M j, Y g:i A');
            $deadlineForInput = $userTime->format('Y-m-d\TH:i');
        }
    }
@endphp

<flux:card class="{{ $colors['bg'] }} {{ $colors['border'] }} backdrop-blur-sm shadow-lg">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-4">
        <flux:icon.information-circle class="w-5 h-5 {{ $colors['icon'] }}" />
        <flux:heading size="lg" class="{{ $colors['text_primary'] }}">
            Project Details
        </flux:heading>
    </div>

    {{-- Alpine component for inline editing --}}
    <div x-data="{
        editingArtist: false,
        editingGenre: false,
        editingDescription: false,
        showCollaborationTypes: false,
        editingCollaborationTypes: false,
        showBudget: false,
        editingBudget: false,
        showDeadline: false,
        editingDeadline: false,
        showNotes: false,
        editingNotes: false,

        // Current values
        artistName: '{{ addslashes($project->artist_name ?? '') }}',
        genre: '{{ addslashes($project->genre ?? '') }}',
        description: '{{ addslashes($project->description ?? '') }}',
        notes: '{{ addslashes($project->notes ?? '') }}',
        budgetType: '{{ $project->budget > 0 ? 'paid' : 'free' }}',
        budget: '{{ $project->budget ?? 0 }}',
        deadline: '{{ $deadlineForInput ?? '' }}',
        deadlineDisplay: '{{ $deadlineFormatted ?? '' }}',

        // Backup values for cancel reversion
        originalArtistName: '{{ addslashes($project->artist_name ?? '') }}',
        originalGenre: '{{ addslashes($project->genre ?? '') }}',
        originalDescription: '{{ addslashes($project->description ?? '') }}',
        originalNotes: '{{ addslashes($project->notes ?? '') }}',
        originalBudgetType: '{{ $project->budget > 0 ? 'paid' : 'free' }}',
        originalBudget: '{{ $project->budget ?? 0 }}',
        originalDeadline: '{{ $deadlineForInput ?? '' }}',
        originalDeadlineDisplay: '{{ $deadlineFormatted ?? '' }}',

        // Collaboration types
        collaborationTypes: {
            mixing: {{ in_array('Mixing', $collaborationTypes) ? 'true' : 'false' }},
            mastering: {{ in_array('Mastering', $collaborationTypes) ? 'true' : 'false' }},
            production: {{ in_array('Production', $collaborationTypes) ? 'true' : 'false' }},
            songwriting: {{ in_array('Songwriting', $collaborationTypes) ? 'true' : 'false' }},
            vocalTuning: {{ in_array('Vocal Tuning', $collaborationTypes) ? 'true' : 'false' }}
        },
        originalCollaborationTypes: {
            mixing: {{ in_array('Mixing', $collaborationTypes) ? 'true' : 'false' }},
            mastering: {{ in_array('Mastering', $collaborationTypes) ? 'true' : 'false' }},
            production: {{ in_array('Production', $collaborationTypes) ? 'true' : 'false' }},
            songwriting: {{ in_array('Songwriting', $collaborationTypes) ? 'true' : 'false' }},
            vocalTuning: {{ in_array('Vocal Tuning', $collaborationTypes) ? 'true' : 'false' }}
        }
    }" class="space-y-4">

        {{-- Artist Name --}}
        <div class="space-y-2">
            <div class="flex items-center gap-2">
                <flux:icon.microphone class="w-4 h-4 {{ $colors['icon'] }}" />
                <span class="text-sm font-semibold {{ $colors['text_secondary'] }}">Artist Name</span>
            </div>

            <div x-show="!editingArtist" class="flex items-center gap-2 group">
                <span class="text-sm {{ $colors['text_primary'] }}" x-text="artistName || 'Not set'"></span>
                <button
                    @click="editingArtist = true; $nextTick(() => $refs.artistInput.focus())"
                    class="opacity-0 group-hover:opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded touch-manipulation"
                    type="button"
                    aria-label="Edit artist name">
                    <flux:icon.pencil class="w-3 h-3 text-slate-500" />
                </button>
            </div>

            <div x-show="editingArtist" x-cloak class="flex items-center gap-1.5">
                <input
                    x-ref="artistInput"
                    type="text"
                    x-model="artistName"
                    @keydown.enter="$wire.updateProjectDetailsInline({ artist_name: artistName }).then(() => { originalArtistName = artistName; editingArtist = false; })"
                    @keydown.escape="artistName = originalArtistName; editingArtist = false"
                    class="flex-1 min-w-0 px-2 py-1.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Artist name"
                />
                <button
                    @click="$wire.updateProjectDetailsInline({ artist_name: artistName }).then(() => { originalArtistName = artistName; editingArtist = false; })"
                    class="shrink-0 p-1.5 text-green-600 hover:bg-green-50 dark:hover:bg-green-950 rounded-lg transition-colors touch-manipulation"
                    type="button">
                    <flux:icon.check class="w-4 h-4" />
                </button>
                <button
                    @click="artistName = originalArtistName; editingArtist = false"
                    class="shrink-0 p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-950 rounded-lg transition-colors touch-manipulation"
                    type="button">
                    <flux:icon.x-mark class="w-4 h-4" />
                </button>
            </div>
        </div>

        {{-- Genre --}}
        <div class="space-y-2">
            <div class="flex items-center gap-2">
                <flux:icon.musical-note class="w-4 h-4 {{ $colors['icon'] }}" />
                <span class="text-sm font-semibold {{ $colors['text_secondary'] }}">Genre</span>
            </div>

            <div x-show="!editingGenre" class="flex items-center gap-2 group">
                <span class="text-sm {{ $colors['text_primary'] }}" x-text="genre || 'Not set'"></span>
                <button
                    @click="editingGenre = true; $nextTick(() => $refs.genreInput.focus())"
                    class="opacity-0 group-hover:opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded touch-manipulation"
                    type="button"
                    aria-label="Edit genre">
                    <flux:icon.pencil class="w-3 h-3 text-slate-500" />
                </button>
            </div>

            <div x-show="editingGenre" x-cloak class="flex items-center gap-1.5">
                <input
                    x-ref="genreInput"
                    type="text"
                    x-model="genre"
                    @keydown.enter="$wire.updateProjectDetailsInline({ genre: genre }).then(() => { originalGenre = genre; editingGenre = false; })"
                    @keydown.escape="genre = originalGenre; editingGenre = false"
                    class="flex-1 min-w-0 px-2 py-1.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Genre"
                />
                <button
                    @click="$wire.updateProjectDetailsInline({ genre: genre }).then(() => { originalGenre = genre; editingGenre = false; })"
                    class="shrink-0 p-1.5 text-green-600 hover:bg-green-50 dark:hover:bg-green-950 rounded-lg transition-colors touch-manipulation"
                    type="button">
                    <flux:icon.check class="w-4 h-4" />
                </button>
                <button
                    @click="genre = originalGenre; editingGenre = false"
                    class="shrink-0 p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-950 rounded-lg transition-colors touch-manipulation"
                    type="button">
                    <flux:icon.x-mark class="w-4 h-4" />
                </button>
            </div>
        </div>

        {{-- Description --}}
        <div class="space-y-2">
            <div class="flex items-center justify-between group">
                <div class="flex items-center gap-2">
                    <flux:icon.document-text class="w-4 h-4 {{ $colors['icon'] }}" />
                    <span class="text-sm font-semibold {{ $colors['text_secondary'] }}">Description</span>
                </div>
                <button
                    x-show="!editingDescription"
                    @click="editingDescription = true; $nextTick(() => $refs.descriptionInput.focus())"
                    class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded touch-manipulation"
                    type="button"
                    aria-label="Edit description">
                    <flux:icon.pencil class="w-3 h-3 text-slate-500" />
                </button>
            </div>

            <div x-show="!editingDescription">
                <p class="text-sm {{ $colors['text_primary'] }} whitespace-pre-wrap" x-text="description || 'No description yet'"></p>
            </div>

            <div x-show="editingDescription" x-cloak class="space-y-2">
                <textarea
                    x-ref="descriptionInput"
                    x-model="description"
                    rows="4"
                    class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Describe your project..."></textarea>
                <div class="flex items-center gap-2">
                    <button
                        @click="$wire.updateProjectDetailsInline({ description: description }).then(() => { originalDescription = description; editingDescription = false; })"
                        class="px-3 py-1.5 text-sm text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors"
                        type="button">
                        Save
                    </button>
                    <button
                        @click="description = originalDescription; editingDescription = false"
                        class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                        type="button">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        {{-- Collaboration Types --}}
        @if(!$project->isClientManagement())
            <div class="pt-4 border-t {{ $colors['border'] }}">
                <button
                    @click="showCollaborationTypes = !showCollaborationTypes"
                    class="flex items-center justify-between w-full text-sm font-semibold {{ $colors['text_secondary'] }} hover:{{ $colors['text_primary'] }} transition-colors group"
                    type="button">
                    <div class="flex items-center gap-2">
                        <flux:icon.user-group class="w-4 h-4" />
                        <span>Collaboration Types</span>
                        <span class="text-xs {{ $colors['text_muted'] }}" x-text="'(' + ((collaborationTypes.mixing ? 1 : 0) + (collaborationTypes.mastering ? 1 : 0) + (collaborationTypes.production ? 1 : 0) + (collaborationTypes.songwriting ? 1 : 0) + (collaborationTypes.vocalTuning ? 1 : 0)) + ' selected)'"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div
                            x-show="!showCollaborationTypes && !editingCollaborationTypes"
                            @click.stop="showCollaborationTypes = true; editingCollaborationTypes = true"
                            class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded touch-manipulation cursor-pointer"
                            role="button"
                            tabindex="0"
                            @keydown.enter.stop="showCollaborationTypes = true; editingCollaborationTypes = true"
                            @keydown.space.prevent.stop="showCollaborationTypes = true; editingCollaborationTypes = true"
                            aria-label="Edit collaboration types">
                            <flux:icon.pencil class="w-3 h-3 text-slate-500" />
                        </div>
                        <flux:icon.chevron-down class="w-4 h-4 transition-transform" x-bind:class="showCollaborationTypes ? 'rotate-180' : ''" />
                    </div>
                </button>

                <div x-show="showCollaborationTypes" x-collapse class="mt-3 space-y-3">
                    {{-- View Mode: Show badges --}}
                    <div x-show="!editingCollaborationTypes">
                        <div class="flex flex-wrap gap-2 mb-2">
                            <template x-if="collaborationTypes.mixing">
                                <flux:badge size="sm" color="zinc">Mixing</flux:badge>
                            </template>
                            <template x-if="collaborationTypes.mastering">
                                <flux:badge size="sm" color="zinc">Mastering</flux:badge>
                            </template>
                            <template x-if="collaborationTypes.production">
                                <flux:badge size="sm" color="zinc">Production</flux:badge>
                            </template>
                            <template x-if="collaborationTypes.songwriting">
                                <flux:badge size="sm" color="zinc">Songwriting</flux:badge>
                            </template>
                            <template x-if="collaborationTypes.vocalTuning">
                                <flux:badge size="sm" color="zinc">Vocal Tuning</flux:badge>
                            </template>
                            <template x-if="!collaborationTypes.mixing && !collaborationTypes.mastering && !collaborationTypes.production && !collaborationTypes.songwriting && !collaborationTypes.vocalTuning">
                                <span class="text-sm {{ $colors['text_muted'] }}">None selected</span>
                            </template>
                        </div>
                        <button
                            @click="editingCollaborationTypes = true"
                            class="text-sm {{ $colors['text_muted'] }} hover:{{ $colors['text_secondary'] }}"
                            type="button">
                            <flux:icon.pencil class="w-3 h-3 inline mr-1" />
                            Edit types
                        </button>
                    </div>

                    {{-- Edit Mode: Show checkboxes --}}
                    <div x-show="editingCollaborationTypes" x-cloak class="space-y-3">
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="collaborationTypes.mixing"
                                    class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500 rounded"
                                />
                                <span class="text-sm {{ $colors['text_primary'] }}">Mixing</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="collaborationTypes.mastering"
                                    class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500 rounded"
                                />
                                <span class="text-sm {{ $colors['text_primary'] }}">Mastering</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="collaborationTypes.production"
                                    class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500 rounded"
                                />
                                <span class="text-sm {{ $colors['text_primary'] }}">Production</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="collaborationTypes.songwriting"
                                    class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500 rounded"
                                />
                                <span class="text-sm {{ $colors['text_primary'] }}">Songwriting</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="collaborationTypes.vocalTuning"
                                    class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500 rounded"
                                />
                                <span class="text-sm {{ $colors['text_primary'] }}">Vocal Tuning</span>
                            </label>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                @click="
                                    let selected = [];
                                    if (collaborationTypes.mixing) selected.push('Mixing');
                                    if (collaborationTypes.mastering) selected.push('Mastering');
                                    if (collaborationTypes.production) selected.push('Production');
                                    if (collaborationTypes.songwriting) selected.push('Songwriting');
                                    if (collaborationTypes.vocalTuning) selected.push('Vocal Tuning');
                                    $wire.updateCollaborationTypes(selected).then(() => {
                                        originalCollaborationTypes = { ...collaborationTypes };
                                        editingCollaborationTypes = false;
                                    });
                                "
                                class="px-3 py-1.5 text-sm text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors"
                                type="button">
                                Save
                            </button>
                            <button
                                @click="
                                    collaborationTypes.mixing = originalCollaborationTypes.mixing;
                                    collaborationTypes.mastering = originalCollaborationTypes.mastering;
                                    collaborationTypes.production = originalCollaborationTypes.production;
                                    collaborationTypes.songwriting = originalCollaborationTypes.songwriting;
                                    collaborationTypes.vocalTuning = originalCollaborationTypes.vocalTuning;
                                    editingCollaborationTypes = false;
                                "
                                class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                                type="button">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Budget (Standard Projects Only) --}}
        @if($project->isStandard())
            <div class="pt-4 border-t {{ $colors['border'] }}">
                <button
                    @click="showBudget = !showBudget"
                    class="flex items-center justify-between w-full text-sm font-semibold {{ $colors['text_secondary'] }} hover:{{ $colors['text_primary'] }} transition-colors group"
                    type="button">
                    <div class="flex items-center gap-2">
                        <flux:icon.currency-dollar class="w-4 h-4" />
                        <span>Budget</span>
                        <span x-show="budgetType === 'free'">
                            <flux:badge size="xs" color="zinc">Free</flux:badge>
                        </span>
                        <span x-show="budgetType === 'paid'">
                            <flux:badge size="xs" color="lime" x-text="'$' + parseFloat(budget).toFixed(2)"></flux:badge>
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div
                            x-show="!showBudget && !editingBudget"
                            @click.stop="showBudget = true; editingBudget = true"
                            class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded touch-manipulation cursor-pointer"
                            role="button"
                            tabindex="0"
                            @keydown.enter.stop="showBudget = true; editingBudget = true"
                            @keydown.space.prevent.stop="showBudget = true; editingBudget = true"
                            aria-label="Edit budget">
                            <flux:icon.pencil class="w-3 h-3 text-slate-500" />
                        </div>
                        <flux:icon.chevron-down class="w-4 h-4 transition-transform" x-bind:class="showBudget ? 'rotate-180' : ''" />
                    </div>
                </button>

                <div x-show="showBudget" x-collapse class="mt-3 space-y-3">
                    <div x-show="!editingBudget">
                        <div class="space-y-2">
                            <div class="text-sm {{ $colors['text_primary'] }}">
                                <span class="font-semibold">Type:</span>
                                <span x-text="budgetType === 'free' ? 'Free Project' : 'Paid Project'"></span>
                            </div>
                            <div x-show="budgetType === 'paid'" class="text-sm {{ $colors['text_primary'] }}">
                                <span class="font-semibold">Amount:</span>
                                <span>$<span x-text="parseFloat(budget).toFixed(2)"></span></span>
                            </div>
                        </div>
                        <button
                            @click="editingBudget = true"
                            class="mt-2 text-sm {{ $colors['text_muted'] }} hover:{{ $colors['text_secondary'] }}"
                            type="button">
                            <flux:icon.pencil class="w-3 h-3 inline mr-1" />
                            Edit budget
                        </button>
                    </div>

                    <div x-show="editingBudget" x-cloak class="space-y-3">
                        {{-- Budget Type Toggle --}}
                        <div class="space-y-2">
                            <label class="text-sm font-semibold {{ $colors['text_secondary'] }}">Budget Type</label>
                            <div class="flex gap-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="budgetType"
                                        value="free"
                                        x-model="budgetType"
                                        class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500"
                                    />
                                    <span class="text-sm {{ $colors['text_primary'] }}">Free</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="budgetType"
                                        value="paid"
                                        x-model="budgetType"
                                        class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500"
                                    />
                                    <span class="text-sm {{ $colors['text_primary'] }}">Paid</span>
                                </label>
                            </div>
                        </div>

                        {{-- Budget Amount (shown when Paid) --}}
                        <div x-show="budgetType === 'paid'" class="space-y-2">
                            <label class="text-sm font-semibold {{ $colors['text_secondary'] }}">Budget Amount</label>
                            <div class="flex items-center gap-2">
                                <span class="text-lg {{ $colors['text_primary'] }}">$</span>
                                <input
                                    x-ref="budgetInput"
                                    type="number"
                                    x-model="budget"
                                    min="0"
                                    max="999999.99"
                                    step="0.01"
                                    class="flex-1 px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="0.00"
                                />
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                @click="$wire.updateBudget({
                                    budget_type: budgetType,
                                    budget: budgetType === 'free' ? 0 : parseFloat(budget)
                                }).then(() => {
                                    if (budgetType === 'free') budget = 0;
                                    originalBudgetType = budgetType;
                                    originalBudget = budget;
                                    editingBudget = false;
                                })"
                                class="px-3 py-1.5 text-sm text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors"
                                type="button">
                                Save
                            </button>
                            <button
                                @click="budgetType = originalBudgetType; budget = originalBudget; editingBudget = false"
                                class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                                type="button">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Deadline (Standard & Client Management only) --}}
        @if($project->isStandard() || $project->isClientManagement())
            <div class="pt-4 border-t {{ $colors['border'] }}">
                <button
                    @click="showDeadline = !showDeadline"
                    class="flex items-center justify-between w-full text-sm font-semibold {{ $colors['text_secondary'] }} hover:{{ $colors['text_primary'] }} transition-colors group"
                    type="button">
                    <div class="flex items-center gap-2">
                        <flux:icon.calendar class="w-4 h-4" />
                        <span>Deadline</span>
                        <flux:badge size="xs" color="lime" x-show="deadlineDisplay" x-text="deadlineDisplay"></flux:badge>
                        <flux:badge size="xs" color="zinc" x-show="!deadlineDisplay">No deadline</flux:badge>
                    </div>
                    <div class="flex items-center gap-2">
                        <div
                            x-show="!showDeadline && !editingDeadline"
                            @click.stop="showDeadline = true; editingDeadline = true"
                            class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded touch-manipulation cursor-pointer"
                            role="button"
                            tabindex="0"
                            @keydown.enter.stop="showDeadline = true; editingDeadline = true"
                            @keydown.space.prevent.stop="showDeadline = true; editingDeadline = true"
                            aria-label="Edit deadline">
                            <flux:icon.pencil class="w-3 h-3 text-slate-500" />
                        </div>
                        <flux:icon.chevron-down class="w-4 h-4 transition-transform" x-bind:class="showDeadline ? 'rotate-180' : ''" />
                    </div>
                </button>

                <div x-show="showDeadline" x-collapse class="mt-3 space-y-3">
                    <div x-show="!editingDeadline">
                        <div class="text-sm {{ $colors['text_primary'] }}">
                            <span class="font-semibold">Due:</span>
                            <span x-text="deadlineDisplay || 'No deadline set'"></span>
                        </div>
                        <button
                            @click="editingDeadline = true"
                            class="mt-2 text-sm {{ $colors['text_muted'] }} hover:{{ $colors['text_secondary'] }}"
                            type="button">
                            <flux:icon.pencil class="w-3 h-3 inline mr-1" />
                            Edit deadline
                        </button>
                    </div>

                    <div x-show="editingDeadline" x-cloak class="space-y-3">
                        <div class="space-y-2">
                            <label class="text-sm font-semibold {{ $colors['text_secondary'] }}">Project Deadline</label>
                            <input
                                type="datetime-local"
                                x-model="deadline"
                                class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p class="text-xs {{ $colors['text_muted'] }}">Times are in your local timezone</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                @click="$wire.updateDeadline(deadline).then(() => {
                                    if (deadline) {
                                        const dt = new Date(deadline);
                                        const options = { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' };
                                        deadlineDisplay = dt.toLocaleString('en-US', options);
                                    } else {
                                        deadlineDisplay = '';
                                    }
                                    originalDeadline = deadline;
                                    originalDeadlineDisplay = deadlineDisplay;
                                    editingDeadline = false;
                                })"
                                class="px-3 py-1.5 text-sm text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors"
                                type="button">
                                Save
                            </button>
                            <button
                                @click="deadline = originalDeadline; deadlineDisplay = originalDeadlineDisplay; editingDeadline = false"
                                class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                                type="button">
                                Cancel
                            </button>
                            <button
                                x-show="deadline"
                                @click="deadline = ''; $wire.updateDeadline(null).then(() => {
                                    deadlineDisplay = '';
                                    originalDeadline = '';
                                    originalDeadlineDisplay = '';
                                    editingDeadline = false;
                                })"
                                class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950 rounded-lg transition-colors"
                                type="button">
                                Clear Deadline
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Notes (Collapsed by default) --}}
        <div class="pt-4 border-t {{ $colors['border'] }}">
            <button
                @click="showNotes = !showNotes"
                class="flex items-center justify-between w-full text-sm font-semibold {{ $colors['text_secondary'] }} hover:{{ $colors['text_primary'] }} transition-colors group"
                type="button">
                <div class="flex items-center gap-2">
                    <flux:icon.clipboard-document-list class="w-4 h-4" />
                    <span>Notes</span>
                    @if(!empty($project->notes))
                        <flux:badge size="xs" color="zinc">Has notes</flux:badge>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <div
                        x-show="!showNotes && !editingNotes"
                        @click.stop="showNotes = true; editingNotes = true; $nextTick(() => $refs.notesInput.focus())"
                        class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded touch-manipulation cursor-pointer"
                        role="button"
                        tabindex="0"
                        @keydown.enter.stop="showNotes = true; editingNotes = true; $nextTick(() => $refs.notesInput.focus())"
                        @keydown.space.prevent.stop="showNotes = true; editingNotes = true; $nextTick(() => $refs.notesInput.focus())"
                        aria-label="Edit notes">
                        <flux:icon.pencil class="w-3 h-3 text-slate-500" />
                    </div>
                    <flux:icon.chevron-down class="w-4 h-4 transition-transform" x-bind:class="showNotes ? 'rotate-180' : ''" />
                </div>
            </button>

            <div x-show="showNotes" x-collapse class="mt-3 space-y-2">
                <div x-show="!editingNotes">
                    <p class="text-sm {{ $colors['text_primary'] }} whitespace-pre-wrap" x-text="notes || 'No notes yet'"></p>
                    <button
                        @click="editingNotes = true; $nextTick(() => $refs.notesInput.focus())"
                        class="mt-2 text-sm {{ $colors['text_muted'] }} hover:{{ $colors['text_secondary'] }}"
                        type="button">
                        <flux:icon.pencil class="w-3 h-3 inline mr-1" />
                        Edit notes
                    </button>
                </div>

                <div x-show="editingNotes" x-cloak class="space-y-2">
                    <textarea
                        x-ref="notesInput"
                        x-model="notes"
                        rows="3"
                        class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Add private notes..."></textarea>
                    <div class="flex items-center gap-2">
                        <button
                            @click="$wire.updateProjectDetailsInline({ notes: notes }).then(() => { originalNotes = notes; editingNotes = false; })"
                            class="px-3 py-1.5 text-sm text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors"
                            type="button">
                            Save
                        </button>
                        <button
                            @click="notes = originalNotes; editingNotes = false"
                            class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                            type="button">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</flux:card>
