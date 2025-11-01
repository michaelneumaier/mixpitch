<div x-data="{
    postponed: false,
    init() {
        // Check if license has been postponed in this session
        const key = 'license_postponed_{{ $project->id }}';
        this.postponed = sessionStorage.getItem(key) === 'true';

        // If postponed, don't show modal
        if (this.postponed) {
            @this.set('showModal', false);
        }
    }
}"
@postpone-license.window="
    // Store postponement in sessionStorage
    sessionStorage.setItem('license_postponed_' + $event.detail.projectId, 'true');
    postponed = true;
"
@open-license-modal.window="
    // Clear postponement and open modal
    sessionStorage.removeItem('license_postponed_{{ $project->id }}');
    postponed = false;
    $wire.openModal();
"
@view-license-terms.window="
    console.log('Opening view-license-terms modal');
    $flux.modal('view-license-terms').show();
">
    <flux:modal wire:model="showModal" name="client-license-agreement" class="max-w-4xl" variant="flyout">
        <div class="p-6">
            <!-- Modal Header -->
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2.5 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <flux:icon name="document-text" size="lg" class="text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">License Agreement Review</flux:heading>
                    <flux:text size="sm" class="text-slate-600 dark:text-slate-400">for "{{ $project->name }}"</flux:text>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="space-y-6 mb-6">
                <!-- Welcome Section -->
                <flux:card class="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800">
                    <div class="flex items-start gap-3">
                        <div class="p-2.5 bg-blue-100 dark:bg-blue-900 rounded-lg">
                            <flux:icon name="information-circle" size="sm" class="text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="flex-1">
                            <flux:heading size="sm" class="text-blue-800 dark:text-blue-200 mb-1">License Agreement Required</flux:heading>
                            <flux:text size="sm" class="text-blue-700 dark:text-blue-300">
                                This project requires you to review and agree to specific license terms before proceeding. Please take a moment to review the terms below.
                            </flux:text>
                        </div>
                    </div>
                </flux:card>

                @if($project->licenseTemplate)
                    <!-- License Template Info -->
                    <flux:card>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-emerald-100 dark:bg-emerald-900 rounded-lg">
                                <flux:icon name="document-check" size="sm" class="text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <flux:heading size="sm" class="text-slate-800 dark:text-slate-200">License Details</flux:heading>
                        </div>

                        <div class="mb-4">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <flux:heading size="xs" class="text-slate-900 dark:text-slate-100 mb-1">{{ $project->licenseTemplate->name }}</flux:heading>
                                    @if($project->licenseTemplate->description)
                                        <flux:text size="sm" class="text-slate-700 dark:text-slate-300">{{ $project->licenseTemplate->description }}</flux:text>
                                    @endif
                                </div>
                                @if($project->licenseTemplate->category)
                                    <flux:badge color="zinc" size="sm">
                                        {{ ucwords(str_replace(['-', '_'], ' ', $project->licenseTemplate->category)) }}
                                    </flux:badge>
                                @endif
                            </div>

                            @if($project->licenseTemplate->terms)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-4">
                                    @if($project->licenseTemplate->terms['commercial_use'] ?? false)
                                        <div class="flex items-center gap-2">
                                            <flux:icon name="check-circle" size="sm" class="text-emerald-600 dark:text-emerald-400" />
                                            <flux:text size="sm" class="text-slate-700 dark:text-slate-300">Commercial Use Allowed</flux:text>
                                        </div>
                                    @endif
                                    @if($project->licenseTemplate->terms['attribution_required'] ?? false)
                                        <div class="flex items-center gap-2">
                                            <flux:icon name="user" size="sm" class="text-emerald-600 dark:text-emerald-400" />
                                            <flux:text size="sm" class="text-slate-700 dark:text-slate-300">Attribution Required</flux:text>
                                        </div>
                                    @endif
                                    @if($project->licenseTemplate->terms['modification_allowed'] ?? false)
                                        <div class="flex items-center gap-2">
                                            <flux:icon name="pencil" size="sm" class="text-emerald-600 dark:text-emerald-400" />
                                            <flux:text size="sm" class="text-slate-700 dark:text-slate-300">Modification Allowed</flux:text>
                                        </div>
                                    @endif
                                    @if($project->licenseTemplate->terms['exclusive_rights'] ?? false)
                                        <div class="flex items-center gap-2">
                                            <flux:icon name="sparkles" size="sm" class="text-emerald-600 dark:text-emerald-400" />
                                            <flux:text size="sm" class="text-slate-700 dark:text-slate-300">Exclusive Rights</flux:text>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <flux:separator class="my-4" />

                        <!-- Full License Content -->
                        <div class="mb-4">
                            <flux:subheading class="mb-2">Full License Terms</flux:subheading>
                            <flux:card class="bg-gray-50 dark:bg-gray-900 max-h-96 overflow-y-auto">
                                <flux:text size="sm" class="whitespace-pre-line text-slate-700 dark:text-slate-300">
                                    {!! nl2br(e($project->licenseTemplate->generateLicenseContent($project))) !!}
                                </flux:text>
                            </flux:card>
                        </div>
                    </flux:card>
                @endif

                @if($project->license_notes)
                    <!-- Additional License Notes -->
                    <flux:callout color="blue" icon="information-circle">
                        <flux:callout.heading>Additional Notes</flux:callout.heading>
                        <flux:callout.text class="whitespace-pre-wrap">
                            {{ $project->license_notes }}
                        </flux:callout.text>
                    </flux:callout>
                @endif

                <!-- Agreement Checkbox -->
                <flux:card class="bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 {{ $errorMessage ? 'ring-2 ring-red-500' : '' }}">
                    <flux:field>
                        <flux:checkbox
                            wire:model="agreed"
                            label="I have read and agree to the license terms"
                            description="By checking this box, you confirm that you have read, understood, and agree to be bound by the license terms above."
                        />
                    </flux:field>

                    @if($errorMessage)
                        <flux:error class="mt-2">{{ $errorMessage }}</flux:error>
                    @endif
                </flux:card>
            </div>

            <!-- Modal Footer -->
            <div class="flex flex-col sm:flex-row gap-3 sm:justify-end" x-data="{ processing: @entangle('isProcessing'), agreed: @entangle('agreed') }">
                <flux:button
                    variant="ghost"
                    wire:click="postpone"
                    x-bind:disabled="processing">
                    Review Later
                </flux:button>
                <flux:button
                    variant="primary"
                    wire:click="signAgreement"
                    x-bind:disabled="processing || !agreed">
                    <span wire:loading.remove wire:target="signAgreement" class="flex items-center gap-2">
                        <flux:icon name="check-circle" size="sm" />
                        I Agree
                    </span>
                    <span wire:loading wire:target="signAgreement" class="flex items-center gap-2">
                        <flux:icon name="arrow-path" size="sm" class="animate-spin" />
                        Processing...
                    </span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- View-Only License Terms Modal --}}
    @if($project->licenseTemplate)
        <flux:modal name="view-license-terms" class="max-w-4xl">
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2.5 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <flux:icon name="document-text" size="lg" class="text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">License Agreement</flux:heading>
                        <flux:text size="sm" class="text-slate-600 dark:text-slate-400">for "{{ $project->name }}"</flux:text>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="space-y-6 mb-6">
                    <!-- License Template Info -->
                    <flux:card>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-emerald-100 dark:bg-emerald-900 rounded-lg">
                                <flux:icon name="document-check" size="sm" class="text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <flux:heading size="sm" class="text-slate-800 dark:text-slate-200">License Details</flux:heading>
                        </div>

                        <div class="mb-4">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <flux:heading size="xs" class="text-slate-900 dark:text-slate-100 mb-1">{{ $project->licenseTemplate->name }}</flux:heading>
                                    @if($project->licenseTemplate->description)
                                        <flux:text size="sm" class="text-slate-700 dark:text-slate-300">{{ $project->licenseTemplate->description }}</flux:text>
                                    @endif
                                </div>
                                @if($project->licenseTemplate->category)
                                    <flux:badge color="zinc" size="sm">
                                        {{ ucwords(str_replace(['-', '_'], ' ', $project->licenseTemplate->category)) }}
                                    </flux:badge>
                                @endif
                            </div>

                            @if($project->licenseTemplate->terms)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-4">
                                    @if($project->licenseTemplate->terms['commercial_use'] ?? false)
                                        <div class="flex items-center gap-2">
                                            <flux:icon name="check-circle" size="sm" class="text-emerald-600 dark:text-emerald-400" />
                                            <flux:text size="sm" class="text-slate-700 dark:text-slate-300">Commercial Use Allowed</flux:text>
                                        </div>
                                    @endif
                                    @if($project->licenseTemplate->terms['attribution_required'] ?? false)
                                        <div class="flex items-center gap-2">
                                            <flux:icon name="user" size="sm" class="text-emerald-600 dark:text-emerald-400" />
                                            <flux:text size="sm" class="text-slate-700 dark:text-slate-300">Attribution Required</flux:text>
                                        </div>
                                    @endif
                                    @if($project->licenseTemplate->terms['modification_allowed'] ?? false)
                                        <div class="flex items-center gap-2">
                                            <flux:icon name="pencil" size="sm" class="text-emerald-600 dark:text-emerald-400" />
                                            <flux:text size="sm" class="text-slate-700 dark:text-slate-300">Modification Allowed</flux:text>
                                        </div>
                                    @endif
                                    @if($project->licenseTemplate->terms['exclusive_rights'] ?? false)
                                        <div class="flex items-center gap-2">
                                            <flux:icon name="sparkles" size="sm" class="text-emerald-600 dark:text-emerald-400" />
                                            <flux:text size="sm" class="text-slate-700 dark:text-slate-300">Exclusive Rights</flux:text>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <flux:separator class="my-4" />

                        <!-- Full License Content -->
                        <div class="mb-4">
                            <flux:subheading class="mb-2">Full License Terms</flux:subheading>
                            <flux:card class="bg-gray-50 dark:bg-gray-900 max-h-96 overflow-y-auto">
                                <flux:text size="sm" class="whitespace-pre-line text-slate-700 dark:text-slate-300">
                                    {!! nl2br(e($project->licenseTemplate->generateLicenseContent($project))) !!}
                                </flux:text>
                            </flux:card>
                        </div>
                    </flux:card>

                    @if($project->license_notes)
                        <!-- Additional License Notes -->
                        <flux:callout color="blue" icon="information-circle">
                            <flux:callout.heading>Additional Notes</flux:callout.heading>
                            <flux:callout.text class="whitespace-pre-wrap">
                                {{ $project->license_notes }}
                            </flux:callout.text>
                        </flux:callout>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
                    <flux:button variant="ghost" @click="$flux.modal('view-license-terms').close()">
                        Close
                    </flux:button>
                    @php
                        $user = auth()->check() ? auth()->user() : null;
                        $clientEmail = $user ? null : $project->client_email;
                        $hasSigned = \App\Models\LicenseSignature::hasClientSigned($project, $user, $clientEmail);
                    @endphp

                    @if(!$hasSigned)
                        <flux:button
                            variant="primary"
                            @click="window.dispatchEvent(new CustomEvent('open-license-modal')); $flux.modal('view-license-terms').close()">
                            <flux:icon name="pencil-square" class="mr-1" />
                            Sign This Agreement
                        </flux:button>
                    @endif
                </div>
            </div>
        </flux:modal>
    @endif
</div>
