@props(['project', 'workflowColors' => []])

@php
    // Client management specific color scheme (purple)
    $colors = array_merge([
        'bg' => 'bg-gradient-to-br from-purple-50/95 to-indigo-50/90 dark:from-purple-950/95 dark:to-indigo-950/90',
        'border' => 'border-purple-200/50 dark:border-purple-700/50',
        'text_primary' => 'text-purple-900 dark:text-purple-100',
        'text_secondary' => 'text-purple-700 dark:text-purple-300',
        'text_muted' => 'text-purple-600 dark:text-purple-400',
        'icon' => 'text-purple-600 dark:text-purple-400',
        'accent_bg' => 'bg-purple-100/80 dark:bg-purple-900/80',
        'accent_border' => 'border-purple-200 dark:border-purple-800',
    ], $workflowColors);
@endphp

<flux:card class="{{ $colors['bg'] }} {{ $colors['border'] }} backdrop-blur-sm shadow-lg">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-4">
        <flux:icon.user-circle class="w-5 h-5 {{ $colors['icon'] }}" />
        <flux:heading size="lg" class="{{ $colors['text_primary'] }}">
            Client Information
        </flux:heading>
    </div>

    {{-- Alpine component for inline editing --}}
    <div x-data="{
        editingClientEmail: false,
        editingClientName: false,

        clientEmail: '{{ addslashes($project->client_email ?? '') }}',
        clientName: '{{ addslashes($project->client_name ?? '') }}'
    }" class="space-y-4">

        {{-- Client Email --}}
        <div class="space-y-2">
            <div class="flex items-center gap-2">
                <flux:icon.envelope class="w-4 h-4 {{ $colors['icon'] }}" />
                <span class="text-sm font-semibold {{ $colors['text_secondary'] }}">Client Email</span>
            </div>

            <div x-show="!editingClientEmail" class="flex items-center gap-2 group">
                <span class="text-sm {{ $colors['text_primary'] }}" x-text="clientEmail || 'Not set'"></span>
                <button
                    @click="editingClientEmail = true; $nextTick(() => $refs.emailInput.focus())"
                    class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded touch-manipulation"
                    type="button"
                    aria-label="Edit client email">
                    <flux:icon.pencil class="w-3 h-3 text-slate-500" />
                </button>
            </div>

            <div x-show="editingClientEmail" x-cloak class="flex items-center gap-1.5">
                <input
                    x-ref="emailInput"
                    type="email"
                    x-model="clientEmail"
                    @keydown.enter="$wire.updateClientInfo({ client_email: clientEmail }).then(() => { editingClientEmail = false; })"
                    @keydown.escape="clientEmail = '{{ addslashes($project->client_email ?? '') }}'; editingClientEmail = false"
                    class="flex-1 min-w-0 px-2 py-1.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                    placeholder="client@example.com"
                />
                <button
                    @click="$wire.updateClientInfo({ client_email: clientEmail }).then(() => { editingClientEmail = false; })"
                    class="shrink-0 p-1.5 text-green-600 hover:bg-green-50 dark:hover:bg-green-950 rounded-lg transition-colors touch-manipulation"
                    type="button">
                    <flux:icon.check class="w-4 h-4" />
                </button>
                <button
                    @click="clientEmail = '{{ addslashes($project->client_email ?? '') }}'; editingClientEmail = false"
                    class="shrink-0 p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-950 rounded-lg transition-colors touch-manipulation"
                    type="button">
                    <flux:icon.x-mark class="w-4 h-4" />
                </button>
            </div>
        </div>

        {{-- Client Name --}}
        <div class="space-y-2">
            <div class="flex items-center gap-2">
                <flux:icon.user class="w-4 h-4 {{ $colors['icon'] }}" />
                <span class="text-sm font-semibold {{ $colors['text_secondary'] }}">Client Name</span>
            </div>

            <div x-show="!editingClientName" class="flex items-center gap-2 group">
                <span class="text-sm {{ $colors['text_primary'] }}" x-text="clientName || 'Not set'"></span>
                <button
                    @click="editingClientName = true; $nextTick(() => $refs.nameInput.focus())"
                    class="opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded touch-manipulation"
                    type="button"
                    aria-label="Edit client name">
                    <flux:icon.pencil class="w-3 h-3 text-slate-500" />
                </button>
            </div>

            <div x-show="editingClientName" x-cloak class="flex items-center gap-1.5">
                <input
                    x-ref="nameInput"
                    type="text"
                    x-model="clientName"
                    @keydown.enter="$wire.updateClientInfo({ client_name: clientName }).then(() => { editingClientName = false; })"
                    @keydown.escape="clientName = '{{ addslashes($project->client_name ?? '') }}'; editingClientName = false"
                    class="flex-1 min-w-0 px-2 py-1.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                    placeholder="Client name"
                />
                <button
                    @click="$wire.updateClientInfo({ client_name: clientName }).then(() => { editingClientName = false; })"
                    class="shrink-0 p-1.5 text-green-600 hover:bg-green-50 dark:hover:bg-green-950 rounded-lg transition-colors touch-manipulation"
                    type="button">
                    <flux:icon.check class="w-4 h-4" />
                </button>
                <button
                    @click="clientName = '{{ addslashes($project->client_name ?? '') }}'; editingClientName = false"
                    class="shrink-0 p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-950 rounded-lg transition-colors touch-manipulation"
                    type="button">
                    <flux:icon.x-mark class="w-4 h-4" />
                </button>
            </div>
        </div>

        {{-- Client Portal Link (if client has account) --}}
        @if($project->client_user_id)
            <div class="pt-4 border-t {{ $colors['accent_border'] }}">
                <div class="flex items-center gap-2 text-sm {{ $colors['text_muted'] }}">
                    <flux:icon.check-circle variant="solid" class="w-4 h-4 text-green-600 dark:text-green-400" />
                    <span>Client has MixPitch account</span>
                </div>
            </div>
        @endif

        {{-- Resend Invite Button --}}
        <div class="pt-4 border-t {{ $colors['accent_border'] }}">
            <flux:button
                wire:click="resendClientInvite"
                variant="ghost"
                size="sm"
                class="w-full">
                <flux:icon.paper-airplane class="w-4 h-4" />
                Resend Client Invite
            </flux:button>
        </div>
    </div>
</flux:card>
