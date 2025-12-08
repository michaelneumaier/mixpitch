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
            'color' => 'blue',
        ],
        'contest' => [
            'bg' => 'bg-gradient-to-br from-amber-50/95 to-yellow-50/90 dark:from-amber-950/95 dark:to-yellow-950/90',
            'border' => 'border-amber-200/50 dark:border-amber-700/50',
            'text_primary' => 'text-amber-900 dark:text-amber-100',
            'text_secondary' => 'text-amber-700 dark:text-amber-300',
            'text_muted' => 'text-amber-600 dark:text-amber-400',
            'icon' => 'text-amber-600 dark:text-amber-400',
            'accent_bg' => 'bg-amber-100/80 dark:bg-amber-900/80',
            'color' => 'amber',
        ],
        'client_management' => [
            'bg' => 'bg-gradient-to-br from-purple-50/95 to-indigo-50/90 dark:from-purple-950/95 dark:to-indigo-950/90',
            'border' => 'border-purple-200/50 dark:border-purple-700/50',
            'text_primary' => 'text-purple-900 dark:text-purple-100',
            'text_secondary' => 'text-purple-700 dark:text-purple-300',
            'text_muted' => 'text-purple-600 dark:text-purple-400',
            'icon' => 'text-purple-600 dark:text-purple-400',
            'accent_bg' => 'bg-purple-100/80 dark:bg-purple-900/80',
            'color' => 'purple',
        ],
        default => [
            'bg' => 'bg-gradient-to-br from-gray-50/95 to-slate-50/90 dark:from-gray-950/95 dark:to-slate-950/90',
            'border' => 'border-gray-200/50 dark:border-gray-700/50',
            'text_primary' => 'text-gray-900 dark:text-gray-100',
            'text_secondary' => 'text-gray-700 dark:text-gray-300',
            'text_muted' => 'text-gray-600 dark:text-gray-400',
            'icon' => 'text-gray-600 dark:text-gray-400',
            'accent_bg' => 'bg-gray-100/80 dark:bg-gray-900/80',
            'color' => 'gray',
        ]
    };

    // Use passed colors or defaults
    $colors = array_merge($colors, $workflowColors);
@endphp

<div>
    {{-- Alpine component for inline editing --}}
    <div x-data="{
        editingClientEmail: false,
        editingClientName: false,
        editingArtist: false,
        editingGenre: false,
        editingDescription: false,
        editingCollaborationTypes: false,
        editingBudget: false,
        editingDeadline: false,
        editingLicense: false,
        editingNotes: false
    }">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

            {{-- Client Information Card (Client Management Projects Only) --}}
            @if($project->isClientManagement())
                <flux:card class="overflow-hidden {{ $colors['bg'] }} {{ $colors['border'] }} backdrop-blur-sm shadow-lg">
                    <div class="-mt-4 -mx-4 lg:-mt-5 lg:-mx-5 xl:-mt-6 xl:-mx-6 px-4 py-2 {{ $colors['accent_bg'] }} border-b {{ $colors['border'] }} rounded-t-2xl">
                        <div class="flex items-center gap-3">
                        <flux:icon.user-circle class="w-5 h-5 {{ $colors['icon'] }}" />
                        <flux:heading size="lg" class="{{ $colors['text_primary'] }}">
                            Client Information
                        </flux:heading>
                        </div>
                    </div>

                    <div class="pt-4 space-y-4">
                        {{-- Client Email --}}
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 group">
                                <flux:icon.envelope class="w-4 h-4 {{ $colors['icon'] }}" />
                                <span class="text-sm font-semibold {{ $colors['text_secondary'] }}">Client Email</span>
                                <button
                                    x-show="!editingClientEmail"
                                    @click="editingClientEmail = true; $nextTick(() => $refs.clientEmailInput.focus())"
                                    class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded touch-manipulation"
                                    type="button"
                                    aria-label="Edit client email">
                                    <flux:icon.pencil class="w-3 h-3 text-slate-500" />
                                </button>
                            </div>

                            <div x-show="!editingClientEmail">
                                <span class="text-sm {{ $colors['text_primary'] }}" x-text="$wire.clientEmail || 'Not set'"></span>
                            </div>

                            <div x-show="editingClientEmail" x-cloak class="flex items-center gap-1.5">
                                <input
                                    x-ref="clientEmailInput"
                                    type="email"
                                    wire:model.blur="clientEmail"
                                    @keydown.enter="$wire.updateClientInfo({ client_email: $wire.clientEmail }).then(() => editingClientEmail = false)"
                                    @keydown.escape="$wire.$refresh(); editingClientEmail = false"
                                    class="flex-1 min-w-0 px-2 py-1.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                    placeholder="client@example.com"
                                />
                                <button
                                    @click="$wire.updateClientInfo({ client_email: $wire.clientEmail }).then(() => editingClientEmail = false)"
                                    class="shrink-0 p-1.5 text-green-600 hover:bg-green-50 dark:hover:bg-green-950 rounded-lg transition-colors touch-manipulation"
                                    type="button">
                                    <flux:icon.check class="w-4 h-4" />
                                </button>
                                <button
                                    @click="$wire.$refresh(); editingClientEmail = false"
                                    class="shrink-0 p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-950 rounded-lg transition-colors touch-manipulation"
                                    type="button">
                                    <flux:icon.x-mark class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        {{-- Client Name --}}
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 group">
                                <flux:icon.user class="w-4 h-4 {{ $colors['icon'] }}" />
                                <span class="text-sm font-semibold {{ $colors['text_secondary'] }}">Client Name</span>
                                <button
                                    x-show="!editingClientName"
                                    @click="editingClientName = true; $nextTick(() => $refs.clientNameInput.focus())"
                                    class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded touch-manipulation"
                                    type="button"
                                    aria-label="Edit client name">
                                    <flux:icon.pencil class="w-3 h-3 text-slate-500" />
                                </button>
                            </div>

                            <div x-show="!editingClientName">
                                <span class="text-sm {{ $colors['text_primary'] }}" x-text="$wire.clientName || 'Not set'"></span>
                            </div>

                            <div x-show="editingClientName" x-cloak class="flex items-center gap-1.5">
                                <input
                                    x-ref="clientNameInput"
                                    type="text"
                                    wire:model.blur="clientName"
                                    @keydown.enter="$wire.updateClientInfo({ client_name: $wire.clientName }).then(() => editingClientName = false)"
                                    @keydown.escape="$wire.$refresh(); editingClientName = false"
                                    class="flex-1 min-w-0 px-2 py-1.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                    placeholder="Client name"
                                />
                                <button
                                    @click="$wire.updateClientInfo({ client_name: $wire.clientName }).then(() => editingClientName = false)"
                                    class="shrink-0 p-1.5 text-green-600 hover:bg-green-50 dark:hover:bg-green-950 rounded-lg transition-colors touch-manipulation"
                                    type="button">
                                    <flux:icon.check class="w-4 h-4" />
                                </button>
                                <button
                                    @click="$wire.$refresh(); editingClientName = false"
                                    class="shrink-0 p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-950 rounded-lg transition-colors touch-manipulation"
                                    type="button">
                                    <flux:icon.x-mark class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        {{-- Client Account Status --}}
                        @if($project->client_user_id)
                            <div class="flex items-center gap-2 text-sm {{ $colors['text_muted'] }}">
                                <flux:icon.check-circle variant="solid" class="w-4 h-4 text-green-600 dark:text-green-400" />
                                <span>Client has MixPitch account</span>
                            </div>
                        @endif

                        {{-- Resend Invite Button --}}
                        <div class="pt-2">
                            <flux:button
                                wire:click="resendClientInvite"
                                icon="paper-airplane"
                                size="sm"
                                variant="primary"
                                color="{{ $colors['color'] }}"
                                class="w-full">
                                Resend Client Invite
                            </flux:button>
                        </div>
                    </div>
                </flux:card>
            @endif

            {{-- Basic Details Card (Artist Name, Genre, Description) --}}
            <flux:card class="overflow-hidden {{ $colors['bg'] }} {{ $colors['border'] }} backdrop-blur-sm shadow-lg">
                <div class="-mt-4 -mx-4 lg:-mt-5 lg:-mx-5 xl:-mt-6 xl:-mx-6 px-4 py-2 {{ $colors['accent_bg'] }} border-b {{ $colors['border'] }} rounded-t-2xl">
                    <div class="flex items-center gap-3">
                    <flux:icon.information-circle class="w-5 h-5 {{ $colors['icon'] }}" />
                    <flux:heading size="lg" class="{{ $colors['text_primary'] }}">
                        Basic Details
                    </flux:heading>
                    </div>
                </div>

                <div class="pt-4 space-y-4">
                    {{-- Artist Name --}}
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 group">
                            <flux:icon.microphone class="w-4 h-4 {{ $colors['icon'] }}" />
                            <span class="text-sm font-semibold {{ $colors['text_secondary'] }}">Artist Name</span>
                            <button
                                x-show="!editingArtist"
                                @click="editingArtist = true; $nextTick(() => $refs.artistInput.focus())"
                                class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded touch-manipulation"
                                type="button"
                                aria-label="Edit artist name">
                                <flux:icon.pencil class="w-3 h-3 text-slate-500" />
                            </button>
                        </div>

                        <div x-show="!editingArtist">
                            <span class="text-sm {{ $colors['text_primary'] }}" x-text="$wire.artistName || 'Not set'"></span>
                        </div>

                        <div x-show="editingArtist" x-cloak class="flex items-center gap-1.5">
                            <input
                                x-ref="artistInput"
                                type="text"
                                wire:model.blur="artistName"
                                @keydown.enter="$wire.updateArtistName().then(() => editingArtist = false)"
                                @keydown.escape="$wire.$refresh(); editingArtist = false"
                                class="flex-1 min-w-0 px-2 py-1.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Artist name"
                            />
                            <button
                                @click="$wire.updateArtistName().then(() => editingArtist = false)"
                                class="shrink-0 p-1.5 text-green-600 hover:bg-green-50 dark:hover:bg-green-950 rounded-lg transition-colors touch-manipulation"
                                type="button">
                                <flux:icon.check class="w-4 h-4" />
                            </button>
                            <button
                                @click="$wire.$refresh(); editingArtist = false"
                                class="shrink-0 p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-950 rounded-lg transition-colors touch-manipulation"
                                type="button">
                                <flux:icon.x-mark class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    {{-- Genre --}}
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 group">
                            <flux:icon.musical-note class="w-4 h-4 {{ $colors['icon'] }}" />
                            <span class="text-sm font-semibold {{ $colors['text_secondary'] }}">Genre</span>
                            <button
                                x-show="!editingGenre"
                                @click="editingGenre = true"
                                class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded touch-manipulation"
                                type="button"
                                aria-label="Edit genre">
                                <flux:icon.pencil class="w-3 h-3 text-slate-500" />
                            </button>
                        </div>

                        <div x-show="!editingGenre">
                            <span class="text-sm {{ $colors['text_primary'] }}" x-text="$wire.genre || 'Not set'"></span>
                        </div>

                        <div x-show="editingGenre" x-cloak class="flex items-center gap-1.5">
                            <select
                                wire:model.blur="genre"
                                class="flex-1 min-w-0 px-2 py-1.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select genre</option>
                                @foreach($this->availableGenres as $genreOption)
                                    <option value="{{ $genreOption }}">{{ $genreOption }}</option>
                                @endforeach
                            </select>
                            <button
                                @click="$wire.updateGenre().then(() => editingGenre = false)"
                                class="shrink-0 p-1.5 text-green-600 hover:bg-green-50 dark:hover:bg-green-950 rounded-lg transition-colors touch-manipulation"
                                type="button">
                                <flux:icon.check class="w-4 h-4" />
                            </button>
                            <button
                                @click="$wire.$refresh(); editingGenre = false"
                                class="shrink-0 p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-950 rounded-lg transition-colors touch-manipulation"
                                type="button">
                                <flux:icon.x-mark class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 group">
                            <flux:icon.document-text class="w-4 h-4 {{ $colors['icon'] }}" />
                            <span class="text-sm font-semibold {{ $colors['text_secondary'] }}">Description</span>
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
                            <p class="text-sm {{ $colors['text_primary'] }} whitespace-pre-wrap" x-text="$wire.description || 'No description yet'"></p>
                        </div>

                        <div x-show="editingDescription" x-cloak class="space-y-2">
                            <textarea
                                x-ref="descriptionInput"
                                wire:model.blur="description"
                                rows="4"
                                class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Describe your project..."></textarea>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="$wire.updateDescription().then(() => editingDescription = false)"
                                    class="px-3 py-1.5 text-sm text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors"
                                    type="button">
                                    Save
                                </button>
                                <button
                                    @click="$wire.$refresh(); editingDescription = false"
                                    class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                                    type="button">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- Collaboration Types Card --}}
            @if(!$project->isClientManagement())
                <flux:card class="overflow-hidden {{ $colors['bg'] }} {{ $colors['border'] }} backdrop-blur-sm shadow-lg">
                    <div class="-mt-4 -mx-4 lg:-mt-5 lg:-mx-5 xl:-mt-6 xl:-mx-6 px-4 py-2 {{ $colors['accent_bg'] }} border-b {{ $colors['border'] }} rounded-t-2xl">
                        <div class="flex items-center gap-3">
                        <flux:icon.user-group class="w-5 h-5 {{ $colors['icon'] }}" />
                        <flux:heading size="lg" class="{{ $colors['text_primary'] }}">
                            Collaboration Types
                        </flux:heading>
                        <span class="text-xs {{ $colors['text_muted'] }}" x-text="'(' + (($wire.collaborationTypes.mixing ? 1 : 0) + ($wire.collaborationTypes.mastering ? 1 : 0) + ($wire.collaborationTypes.production ? 1 : 0) + ($wire.collaborationTypes.songwriting ? 1 : 0) + ($wire.collaborationTypes.vocalTuning ? 1 : 0)) + ' selected)'"></span>
                        </div>
                    </div>

                    <div class="pt-4 space-y-3">
                        {{-- View Mode: Show badges --}}
                        <div x-show="!editingCollaborationTypes">
                            <div class="flex flex-wrap gap-2 mb-2">
                                <template x-if="$wire.collaborationTypes.mixing">
                                    <flux:badge size="sm" color="zinc">Mixing</flux:badge>
                                </template>
                                <template x-if="$wire.collaborationTypes.mastering">
                                    <flux:badge size="sm" color="zinc">Mastering</flux:badge>
                                </template>
                                <template x-if="$wire.collaborationTypes.production">
                                    <flux:badge size="sm" color="zinc">Production</flux:badge>
                                </template>
                                <template x-if="$wire.collaborationTypes.songwriting">
                                    <flux:badge size="sm" color="zinc">Songwriting</flux:badge>
                                </template>
                                <template x-if="$wire.collaborationTypes.vocalTuning">
                                    <flux:badge size="sm" color="zinc">Vocal Tuning</flux:badge>
                                </template>
                                <template x-if="!$wire.collaborationTypes.mixing && !$wire.collaborationTypes.mastering && !$wire.collaborationTypes.production && !$wire.collaborationTypes.songwriting && !$wire.collaborationTypes.vocalTuning">
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
                                        wire:model.live="collaborationTypes.mixing"
                                        class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500 rounded"
                                    />
                                    <span class="text-sm {{ $colors['text_primary'] }}">Mixing</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model.live="collaborationTypes.mastering"
                                        class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500 rounded"
                                    />
                                    <span class="text-sm {{ $colors['text_primary'] }}">Mastering</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model.live="collaborationTypes.production"
                                        class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500 rounded"
                                    />
                                    <span class="text-sm {{ $colors['text_primary'] }}">Production</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model.live="collaborationTypes.songwriting"
                                        class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500 rounded"
                                    />
                                    <span class="text-sm {{ $colors['text_primary'] }}">Songwriting</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model.live="collaborationTypes.vocalTuning"
                                        class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500 rounded"
                                    />
                                    <span class="text-sm {{ $colors['text_primary'] }}">Vocal Tuning</span>
                                </label>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    @click="$wire.updateCollaborationTypes().then(() => editingCollaborationTypes = false)"
                                    class="px-3 py-1.5 text-sm text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors"
                                    type="button">
                                    Save
                                </button>
                                <button
                                    @click="$wire.$refresh(); editingCollaborationTypes = false"
                                    class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                                    type="button">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </flux:card>
            @endif

            {{-- Budget Card (Standard Projects Only) --}}
            @if($project->isStandard())
                <flux:card class="overflow-hidden {{ $colors['bg'] }} {{ $colors['border'] }} backdrop-blur-sm shadow-lg">
                    <div class="-mt-4 -mx-4 lg:-mt-5 lg:-mx-5 xl:-mt-6 xl:-mx-6 p-4 lg:p-5 xl:p-6 {{ $colors['accent_bg'] }} border-b {{ $colors['border'] }} rounded-t-2xl">
                        <div class="flex items-center gap-3">
                            <flux:icon.currency-dollar class="w-5 h-5 {{ $colors['icon'] }}" />
                            <flux:heading size="lg" class="{{ $colors['text_primary'] }}">
                                Budget
                            </flux:heading>
                            <span x-show="$wire.budgetType === 'free'">
                                <flux:badge size="xs" color="zinc">Free</flux:badge>
                            </span>
                            <span x-show="$wire.budgetType === 'paid'">
                                <flux:badge size="xs" color="lime" x-text="'$' + parseFloat($wire.budget).toFixed(2)"></flux:badge>
                            </span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div x-show="!editingBudget">
                            <div class="space-y-2">
                                <div class="text-sm {{ $colors['text_primary'] }}">
                                    <span class="font-semibold">Type:</span>
                                    <span x-text="$wire.budgetType === 'free' ? 'Free Project' : 'Paid Project'"></span>
                                </div>
                                <div x-show="$wire.budgetType === 'paid'" class="text-sm {{ $colors['text_primary'] }}">
                                    <span class="font-semibold">Amount:</span>
                                    <span>$<span x-text="parseFloat($wire.budget).toFixed(2)"></span></span>
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
                                            wire:model.live="budgetType"
                                            class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500"
                                        />
                                        <span class="text-sm {{ $colors['text_primary'] }}">Free</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            name="budgetType"
                                            value="paid"
                                            wire:model.live="budgetType"
                                            class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500"
                                        />
                                        <span class="text-sm {{ $colors['text_primary'] }}">Paid</span>
                                    </label>
                                </div>
                            </div>

                            {{-- Budget Amount (shown when Paid) --}}
                            <div x-show="$wire.budgetType === 'paid'" class="space-y-2">
                                <label class="text-sm font-semibold {{ $colors['text_secondary'] }}">Budget Amount</label>
                                <div class="flex items-center gap-2">
                                    <span class="text-lg {{ $colors['text_primary'] }}">$</span>
                                    <input
                                        x-ref="budgetInput"
                                        type="number"
                                        wire:model.blur="budget"
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
                                    @click="$wire.updateBudget().then(() => editingBudget = false)"
                                    class="px-3 py-1.5 text-sm text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors"
                                    type="button">
                                    Save
                                </button>
                                <button
                                    @click="$wire.$refresh(); editingBudget = false"
                                    class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                                    type="button">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </flux:card>
            @endif

            {{-- Deadline Card (Standard & Client Management only) --}}
            @if($project->isStandard() || $project->isClientManagement())
                <flux:card class="overflow-hidden {{ $colors['bg'] }} {{ $colors['border'] }} backdrop-blur-sm shadow-lg">
                    <div class="-mt-4 -mx-4 lg:-mt-5 lg:-mx-5 xl:-mt-6 xl:-mx-6 px-4 py-2 {{ $colors['accent_bg'] }} border-b {{ $colors['border'] }} rounded-t-2xl">
                        <div class="flex items-center gap-3">
                            <flux:icon.calendar class="w-5 h-5 {{ $colors['icon'] }}" />
                            <flux:heading size="lg" class="{{ $colors['text_primary'] }}">
                                Deadline
                            </flux:heading>
                            <flux:badge size="xs" color="lime" x-show="$wire.deadlineDisplay" x-text="$wire.deadlineDisplay"></flux:badge>
                            <flux:badge size="xs" color="zinc" x-show="!$wire.deadlineDisplay">No deadline</flux:badge>
                        </div>
                    </div>

                    <div class="pt-4 space-y-3">
                        <div x-show="!editingDeadline">
                            <div class="text-sm {{ $colors['text_primary'] }}">
                                <span class="font-semibold">Due:</span>
                                <span x-text="$wire.deadlineDisplay || 'No deadline set'"></span>
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
                                    wire:model.blur="deadline"
                                    class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                />
                                <p class="text-xs {{ $colors['text_muted'] }}">Times are in your local timezone</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    @click="$wire.updateDeadline().then(() => editingDeadline = false)"
                                    class="px-3 py-1.5 text-sm text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors"
                                    type="button">
                                    Save
                                </button>
                                <button
                                    @click="$wire.$refresh(); editingDeadline = false"
                                    class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                                    type="button">
                                    Cancel
                                </button>
                                <button
                                    x-show="$wire.deadline"
                                    @click="$wire.clearDeadline().then(() => editingDeadline = false)"
                                    class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950 rounded-lg transition-colors"
                                    type="button">
                                    Clear Deadline
                                </button>
                            </div>
                        </div>
                    </div>
                </flux:card>
            @endif

            {{-- License Agreement Card --}}
            <flux:card class="overflow-hidden {{ $colors['bg'] }} {{ $colors['border'] }} backdrop-blur-sm shadow-lg">
                <div class="-mt-4 -mx-4 lg:-mt-5 lg:-mx-5 xl:-mt-6 xl:-mx-6 px-4 py-2 {{ $colors['accent_bg'] }} border-b {{ $colors['border'] }} rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <flux:icon.document-text class="w-5 h-5 {{ $colors['icon'] }}" />
                        <flux:heading size="lg" class="{{ $colors['text_primary'] }}">
                            License
                        </flux:heading>
                        @if($project->licenseTemplate)
                            <flux:badge size="xs" color="lime">License Selected</flux:badge>
                        @else
                            <flux:badge size="xs" color="zinc">Platform Default</flux:badge>
                        @endif
                    </div>
                </div>

                <div class="pt-4 space-y-4">
                    {{-- View Mode --}}
                    <div x-show="!editingLicense">
                        {{-- Current Template Info --}}
                        @if($project->licenseTemplate)
                            <button
                                wire:click="previewLicenseTemplate"
                                type="button"
                                class="{{ $colors['accent_bg'] }} hover:brightness-90 rounded-lg p-3 mb-3 w-full text-left transition-all group">
                                <div class="flex items-start gap-2 mb-2">
                                    <flux:icon.document class="w-4 h-4 {{ $colors['icon'] }} mt-0.5" />
                                    <div class="flex-1">
                                        <div class="text-sm font-semibold {{ $colors['text_primary'] }}">
                                            {{ $project->licenseTemplate->name }}
                                        </div>
                                        <div class="text-xs {{ $colors['text_muted'] }} mt-0.5">
                                            {{ $project->licenseTemplate->category_name ?? 'License Template' }}
                                        </div>
                                    </div>
                                    <flux:icon.eye class="w-4 h-4 {{ $colors['text_muted'] }} opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity" />
                                </div>
                                @if($project->licenseTemplate->use_case)
                                    <div class="flex gap-1 flex-wrap">
                                        <flux:badge size="xs" color="zinc">
                                            {{ $project->licenseTemplate->use_case_name }}
                                        </flux:badge>
                                    </div>
                                @endif
                            </button>
                        @endif

                        {{-- License Settings Summary --}}
                        <div class="space-y-2">
                            <div class="text-sm {{ $colors['text_primary'] }}">
                                <span class="font-semibold">Require Agreement:</span>
                                <span>{{ $requiresAgreement ? 'Yes' : 'No' }}</span>
                            </div>
                            @if($licenseNotes)
                                <div class="text-sm {{ $colors['text_primary'] }}">
                                    <span class="font-semibold">Notes:</span>
                                    <p class="mt-1 whitespace-pre-wrap">{{ $licenseNotes }}</p>
                                </div>
                            @endif
                        </div>

                        <button
                            @click="editingLicense = true"
                            class="mt-2 text-sm {{ $colors['text_muted'] }} hover:{{ $colors['text_secondary'] }}"
                            type="button">
                            <flux:icon.pencil class="w-3 h-3 inline mr-1" />
                            Edit License
                        </button>
                    </div>

                    {{-- Edit Mode --}}
                    <div x-show="editingLicense" x-cloak class="space-y-3">
                        {{-- Current Template Info --}}
                        @if($this->selectedTemplate)
                            <div class="{{ $colors['accent_bg'] }} rounded-lg p-3">
                                <div class="flex items-start gap-2 mb-2">
                                    <flux:icon.document class="w-4 h-4 {{ $colors['icon'] }} mt-0.5" />
                                    <div class="flex-1">
                                        <div class="text-sm font-semibold {{ $colors['text_primary'] }}">
                                            {{ $this->selectedTemplate->name }}
                                        </div>
                                        <div class="text-xs {{ $colors['text_muted'] }} mt-0.5">
                                            {{ $this->selectedTemplate->category_name ?? 'License Template' }}
                                        </div>
                                    </div>
                                </div>
                                @if($this->selectedTemplate->use_case)
                                    <div class="flex gap-1 flex-wrap">
                                        <flux:badge size="xs" color="zinc">
                                            {{ $this->selectedTemplate->use_case_name }}
                                        </flux:badge>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Require Agreement Toggle --}}
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model.live="requiresAgreement"
                                    class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500 rounded"
                                />
                                <span class="text-sm {{ $colors['text_primary'] }}">Require license agreement</span>
                            </label>
                            <p class="text-xs {{ $colors['text_muted'] }} ml-6">
                                Contributors must agree to terms before participating
                            </p>
                        </div>

                        {{-- Template Selection --}}
                        <div class="space-y-2">
                            <label class="text-sm font-semibold {{ $colors['text_secondary'] }}">License Template</label>
                            <button
                                wire:click="openLicenseTemplateSelector"
                                class="flex items-center gap-2 px-3 py-1.5 text-sm {{ $colors['text_primary'] }} border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                                type="button">
                                <flux:icon.document-plus class="w-4 h-4" />
                                @if($selectedTemplateId)
                                    Change Template
                                @else
                                    Select Template
                                @endif
                            </button>

                            @if($selectedTemplateId)
                                <button
                                    wire:click="clearTemplate"
                                    class="text-sm {{ $colors['text_muted'] }} hover:{{ $colors['text_secondary'] }}"
                                    type="button">
                                    Clear selection
                                </button>
                            @endif
                        </div>

                        {{-- License Notes --}}
                        <div class="space-y-2">
                            <label class="text-sm font-semibold {{ $colors['text_secondary'] }}">License Notes (Optional)</label>
                            <textarea
                                wire:model.blur="licenseNotes"
                                rows="3"
                                class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Add any custom notes or clarifications..."></textarea>
                            <p class="text-xs {{ $colors['text_muted'] }}">
                                Internal notes about license usage or special terms
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                @click="$wire.updateLicenseSettings().then(() => editingLicense = false)"
                                class="px-3 py-1.5 text-sm text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors"
                                type="button">
                                Save
                            </button>
                            <button
                                @click="$wire.$refresh(); editingLicense = false"
                                class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                                type="button">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- Notes Card --}}
            <flux:card class="overflow-hidden {{ $colors['bg'] }} {{ $colors['border'] }} backdrop-blur-sm shadow-lg">
                <div class="-mt-4 -mx-4 lg:-mt-5 lg:-mx-5 xl:-mt-6 xl:-mx-6 px-4 py-2 {{ $colors['accent_bg'] }} border-b {{ $colors['border'] }} rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <flux:icon.clipboard-document-list class="w-5 h-5 {{ $colors['icon'] }}" />
                        <flux:heading size="lg" class="{{ $colors['text_primary'] }}">
                            Notes
                        </flux:heading>
                        @if(!empty($notes))
                            <flux:badge size="xs" color="zinc">Has notes</flux:badge>
                        @endif
                    </div>
                </div>

                <div class="pt-4 space-y-2">
                    <div x-show="!editingNotes">
                        <p class="text-sm {{ $colors['text_primary'] }} whitespace-pre-wrap" x-text="$wire.notes || 'No notes yet'"></p>
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
                            wire:model.blur="notes"
                            rows="3"
                            class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Add private notes..."></textarea>
                        <div class="flex items-center gap-2">
                            <button
                                @click="$wire.updateNotes().then(() => editingNotes = false)"
                                class="px-3 py-1.5 text-sm text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors"
                                type="button">
                                Save
                            </button>
                            <button
                                @click="$wire.$refresh(); editingNotes = false"
                                class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                                type="button">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </flux:card>

        </div>
    </div>

    {{-- License Template Selector Modal --}}
    <flux:modal name="license-template-selector" wire:model.self="showLicenseTemplateModal" variant="flyout">
        <livewire:components.license-selector
            :selected-template-id="$selectedTemplateId"
            :requires-agreement="$requiresAgreement"
            :template-picker-only="true"
            wire:key="license-selector-{{ $project->id }}" />
    </flux:modal>

    {{-- License Preview Modal --}}
    @if($previewTemplate)
        <flux:modal name="license-preview" wire:model.self="showLicensePreviewModal" class="max-w-3xl">
            <div class="space-y-6">
                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ $previewTemplate->name }}</flux:heading>
                    <div class="flex gap-2 mt-2 flex-wrap">
                        <flux:badge size="xs" color="zinc">{{ $previewTemplate->category_name }}</flux:badge>
                        @if($previewTemplate->use_case)
                            <flux:badge size="xs" color="blue">{{ $previewTemplate->use_case_name }}</flux:badge>
                        @endif
                        @if($previewTemplate->getUsageCount() > 0)
                            <flux:badge size="xs" color="green">Used {{ $previewTemplate->getUsageCount() }} times</flux:badge>
                        @endif
                    </div>
                </div>

                {{-- Description --}}
                @if($previewTemplate->description)
                    <div>
                        <flux:subheading>About this template</flux:subheading>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $previewTemplate->description }}</p>
                    </div>
                @endif

                {{-- License Terms at a Glance --}}
                @if($previewTemplate->terms)
                    <div>
                        <flux:subheading>License terms at a glance</flux:subheading>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mt-2">
                            {{-- Commercial Use --}}
                            <div class="flex items-center gap-2 text-sm">
                                @if($previewTemplate->terms['commercial_use'] ?? false)
                                    <flux:icon.check-circle class="w-4 h-4 text-green-600 dark:text-green-400" />
                                    <span class="text-green-700 dark:text-green-300">Commercial use</span>
                                @else
                                    <flux:icon.x-circle class="w-4 h-4 text-red-600 dark:text-red-400" />
                                    <span class="text-red-700 dark:text-red-300">No commercial use</span>
                                @endif
                            </div>

                            {{-- Attribution --}}
                            <div class="flex items-center gap-2 text-sm">
                                @if($previewTemplate->terms['attribution_required'] ?? false)
                                    <flux:icon.information-circle class="w-4 h-4 text-orange-600 dark:text-orange-400" />
                                    <span class="text-orange-700 dark:text-orange-300">Credit required</span>
                                @else
                                    <flux:icon.check-circle class="w-4 h-4 text-green-600 dark:text-green-400" />
                                    <span class="text-green-700 dark:text-green-300">No credit needed</span>
                                @endif
                            </div>

                            {{-- Modification --}}
                            <div class="flex items-center gap-2 text-sm">
                                @if($previewTemplate->terms['modification_allowed'] ?? false)
                                    <flux:icon.check-circle class="w-4 h-4 text-green-600 dark:text-green-400" />
                                    <span class="text-green-700 dark:text-green-300">Edits allowed</span>
                                @else
                                    <flux:icon.x-circle class="w-4 h-4 text-red-600 dark:text-red-400" />
                                    <span class="text-red-700 dark:text-red-300">No edits</span>
                                @endif
                            </div>

                            {{-- Distribution --}}
                            <div class="flex items-center gap-2 text-sm">
                                @if($previewTemplate->terms['distribution_allowed'] ?? false)
                                    <flux:icon.check-circle class="w-4 h-4 text-green-600 dark:text-green-400" />
                                    <span class="text-green-700 dark:text-green-300">Distribution allowed</span>
                                @else
                                    <flux:icon.x-circle class="w-4 h-4 text-red-600 dark:text-red-400" />
                                    <span class="text-red-700 dark:text-red-300">No distribution</span>
                                @endif
                            </div>

                            {{-- Sync Licensing --}}
                            <div class="flex items-center gap-2 text-sm">
                                @if($previewTemplate->terms['sync_licensing_allowed'] ?? false)
                                    <flux:icon.check-circle class="w-4 h-4 text-green-600 dark:text-green-400" />
                                    <span class="text-green-700 dark:text-green-300">Sync licensing</span>
                                @else
                                    <flux:icon.x-circle class="w-4 h-4 text-red-600 dark:text-red-400" />
                                    <span class="text-red-700 dark:text-red-300">No sync licensing</span>
                                @endif
                            </div>

                            {{-- Broadcast --}}
                            <div class="flex items-center gap-2 text-sm">
                                @if($previewTemplate->terms['broadcast_allowed'] ?? false)
                                    <flux:icon.check-circle class="w-4 h-4 text-green-600 dark:text-green-400" />
                                    <span class="text-green-700 dark:text-green-300">Broadcast allowed</span>
                                @else
                                    <flux:icon.x-circle class="w-4 h-4 text-red-600 dark:text-red-400" />
                                    <span class="text-red-700 dark:text-red-300">No broadcast</span>
                                @endif
                            </div>

                            {{-- Streaming --}}
                            <div class="flex items-center gap-2 text-sm">
                                @if($previewTemplate->terms['streaming_allowed'] ?? false)
                                    <flux:icon.check-circle class="w-4 h-4 text-green-600 dark:text-green-400" />
                                    <span class="text-green-700 dark:text-green-300">Streaming allowed</span>
                                @else
                                    <flux:icon.x-circle class="w-4 h-4 text-red-600 dark:text-red-400" />
                                    <span class="text-red-700 dark:text-red-300">No streaming</span>
                                @endif
                            </div>

                            {{-- Territory --}}
                            <div class="flex items-center gap-2 text-sm col-span-2 md:col-span-1">
                                <flux:icon.globe-americas class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                <span class="text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $previewTemplate->terms['territory'] ?? 'worldwide')) }}</span>
                            </div>

                            {{-- Duration --}}
                            <div class="flex items-center gap-2 text-sm col-span-2 md:col-span-1">
                                <flux:icon.clock class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                <span class="text-gray-700 dark:text-gray-300">{{ ucfirst($previewTemplate->terms['duration'] ?? 'perpetual') }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Full License Agreement --}}
                <div>
                    <flux:subheading>Full license agreement</flux:subheading>
                    <div class="max-h-96 overflow-y-auto mt-2">
                        <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-900">
                            @if($previewTemplate->content)
                                {{ $previewTemplate->content }}
                            @else
                                <div class="text-gray-500 dark:text-gray-400 italic">No license content available for this template.</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="ghost">Close</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
