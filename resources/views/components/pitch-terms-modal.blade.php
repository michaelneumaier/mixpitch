@props(['project'])

@php
    $licenseTemplate = $project->license_template_id ? $project->licenseTemplate : null;
    $requiresAgreement = $project->requires_license_agreement ?? false;
    $hasLicenseNotes = !empty($project->license_notes);
@endphp

<div x-data="{ 
        open: false,
        agreeTerms: false,
        agreeLicense: false,
        submitting: false,
        errors: {},
        requiresLicense: @js($requiresAgreement),
        
        submitForm() {
            // Clear previous errors
            this.errors = {};
            
            // Validate terms
            if (!this.agreeTerms) {
                this.errors.terms = 'Please agree to the Terms and Conditions to continue.';
            }
            
            // Validate license (if required)
            if (this.requiresLicense && !this.agreeLicense) {
                this.errors.license = 'Please agree to the project license terms to continue.';
            }
            
            // If there are errors, don't submit
            if (Object.keys(this.errors).length > 0) {
                return;
            }
            
            // Submit the form
            this.submitting = true;
            this.$refs.pitchForm.submit();
        }
     }" 
     @open-pitch-modal.window="open = true">
    <!-- Flux Modal -->
    <flux:modal name="pitch-terms" x-model="open" class="max-w-4xl">
        <div class="p-6">
            <!-- Modal Header -->
            <div class="flex items-center gap-3 mb-6">
                <flux:icon name="paper-airplane" size="lg" class="text-blue-600 dark:text-blue-400" />
                <div>
                    <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Start Your Pitch</flux:heading>
                    <flux:text size="sm" class="text-slate-600 dark:text-slate-400">for "{{ $project->name }}"</flux:text>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="space-y-6 mb-6">
            <!-- Welcome Section -->
            <flux:card class="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800">
                <div class="flex items-start gap-3">
                    <div class="p-2.5 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <flux:icon name="rocket-launch" size="sm" class="text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="flex-1">
                        <flux:heading size="sm" class="text-blue-800 dark:text-blue-200 mb-1">Welcome to your creative journey!</flux:heading>
                        <flux:text size="sm" class="text-blue-700 dark:text-blue-300">
                            You're about to start a pitch for this project. Before you begin, please review our terms to ensure a smooth collaboration experience.
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <!-- Terms Section -->
            <flux:card>
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <flux:icon name="shield-check" size="sm" class="text-green-600 dark:text-green-400" />
                    </div>
                    <flux:heading size="sm" class="text-slate-800 dark:text-slate-200">Terms of Service Highlights</flux:heading>
                </div>
                
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="shield-check" size="sm" class="text-blue-600 dark:text-blue-400 mt-0.5" />
                        <flux:text size="sm" class="text-slate-700 dark:text-slate-300">You retain ownership of your original work.</flux:text>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <flux:icon name="document-check" size="sm" class="text-green-600 dark:text-green-400 mt-0.5" />
                        <flux:text size="sm" class="text-slate-700 dark:text-slate-300">If your pitch is selected, you grant the project owner a license as specified in the project details.</flux:text>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <flux:icon name="scale" size="sm" class="text-purple-600 dark:text-purple-400 mt-0.5" />
                        <flux:text size="sm" class="text-slate-700 dark:text-slate-300">Respect copyright and intellectual property rights in your submissions.</flux:text>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <flux:icon name="heart" size="sm" class="text-amber-600 dark:text-amber-400 mt-0.5" />
                        <flux:text size="sm" class="text-slate-700 dark:text-slate-300">Be respectful and professional in all communications.</flux:text>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <flux:icon name="exclamation-triangle" size="sm" class="text-red-600 dark:text-red-400 mt-0.5" />
                        <flux:text size="sm" class="text-slate-700 dark:text-slate-300">We may remove content that violates our community standards.</flux:text>
                    </div>
                </div>
                
                <flux:callout color="blue" class="mt-4">
                    <flux:callout.text>
                        For a complete understanding, please review our full
                        <a href="/terms" target="_blank" class="font-medium underline hover:no-underline">
                            Terms and Conditions
                            <flux:icon name="arrow-top-right-on-square" size="xs" class="inline ml-1" />
                        </a>
                    </flux:callout.text>
                </flux:callout>
            </flux:card>

            @if($licenseTemplate || $requiresAgreement || $hasLicenseNotes)
                <!-- Project License Section -->
                <flux:card class="bg-emerald-50 dark:bg-emerald-950 border border-emerald-200 dark:border-emerald-800">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-emerald-100 dark:bg-emerald-900 rounded-lg">
                            <flux:icon name="document-text" size="sm" class="text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <flux:heading size="sm" class="text-emerald-800 dark:text-emerald-200">Project License Terms</flux:heading>
                    </div>

                    @if($licenseTemplate)
                        <div class="mb-4">
                            <flux:card class="bg-white/60 dark:bg-gray-800/60">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <flux:heading size="xs" class="text-emerald-900 dark:text-emerald-100 mb-1">{{ $licenseTemplate->name }}</flux:heading>
                                        @if($licenseTemplate->description)
                                            <flux:text size="sm" class="text-emerald-800 dark:text-emerald-300">{{ $licenseTemplate->description }}</flux:text>
                                        @endif
                                    </div>
                                    @if($licenseTemplate->category)
                                        <flux:badge color="emerald" size="sm">
                                            {{ ucwords(str_replace('_', ' ', $licenseTemplate->category)) }}
                                        </flux:badge>
                                    @endif
                                </div>
                                
                                @if($licenseTemplate->terms)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        @if($licenseTemplate->terms['commercial_use'] ?? false)
                                            <div class="flex items-center gap-2">
                                                <flux:icon name="check-circle" size="sm" class="text-emerald-600 dark:text-emerald-400" />
                                                <flux:text size="sm" class="text-emerald-700 dark:text-emerald-300">Commercial Use Allowed</flux:text>
                                            </div>
                                        @endif
                                        @if($licenseTemplate->terms['attribution_required'] ?? false)
                                            <div class="flex items-center gap-2">
                                                <flux:icon name="user" size="sm" class="text-emerald-600 dark:text-emerald-400" />
                                                <flux:text size="sm" class="text-emerald-700 dark:text-emerald-300">Attribution Required</flux:text>
                                            </div>
                                        @endif
                                        @if($licenseTemplate->terms['modification_allowed'] ?? false)
                                            <div class="flex items-center gap-2">
                                                <flux:icon name="pencil" size="sm" class="text-emerald-600 dark:text-emerald-400" />
                                                <flux:text size="sm" class="text-emerald-700 dark:text-emerald-300">Modification Allowed</flux:text>
                                            </div>
                                        @endif
                                        @if($licenseTemplate->terms['exclusive_rights'] ?? false)
                                            <div class="flex items-center gap-2">
                                                <flux:icon name="sparkles" size="sm" class="text-emerald-600 dark:text-emerald-400" />
                                                <flux:text size="sm" class="text-emerald-700 dark:text-emerald-300">Exclusive Rights</flux:text>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                
                                <flux:separator class="my-3" />
                                <flux:button variant="ghost" size="sm" icon="eye" @click="$flux.modal('project-license').show()">
                                    View Full License Terms
                                </flux:button>
                            </flux:card>
                        </div>
                    @endif

                    @if($requiresAgreement)
                        <flux:callout color="purple" icon="document-text" class="mb-4">
                            <flux:callout.heading>Project License Agreement</flux:callout.heading>
                            <flux:callout.text>
                                This project requires agreement to specific license terms. You must agree to these terms to submit your pitch.
                            </flux:callout.text>
                            <flux:button variant="ghost" size="sm" icon="eye" @click="$flux.modal('project-license').show()" class="mt-3">
                                Review License Terms
                            </flux:button>
                        </flux:callout>
                    @endif

                    @if($hasLicenseNotes)
                        <flux:callout color="blue" icon="document-text">
                            <flux:callout.heading>Additional License Notes</flux:callout.heading>
                            <flux:callout.text class="whitespace-pre-wrap">
                                {{ $project->license_notes }}
                            </flux:callout.text>
                        </flux:callout>
                    @endif
                </flux:card>
            @endif

            <!-- Agreement Form -->
            <form id="pitch-create-form" action="{{ route('projects.pitches.store', ['project' => $project->slug]) }}" method="POST" x-ref="pitchForm">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->id }}">

                <!-- Platform Terms Agreement -->
                <flux:card class="bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 mb-4"
                           x-bind:class="{ 'ring-2 ring-red-500': errors.terms }">
                    <flux:field>
                        <flux:checkbox 
                            id="agree_terms" 
                            name="agree_terms" 
                            x-model="agreeTerms"
                            label="I agree to the Terms and Conditions"
                            description="By checking this box, you confirm that you have read and agree to our terms of service."
                        />
                        <template x-if="errors.terms">
                            <flux:error x-text="errors.terms" class="mt-2" />
                        </template>
                    </flux:field>
                </flux:card>

                @if($requiresAgreement)
                    <!-- Project License Agreement -->
                    <flux:card class="bg-purple-50 dark:bg-purple-950 border border-purple-200 dark:border-purple-800"
                               x-bind:class="{ 'ring-2 ring-red-500': errors.license }">
                        <flux:field>
                            <flux:checkbox 
                                id="agree_license" 
                                name="agree_license" 
                                x-model="agreeLicense"
                                label="I agree to the project license terms"
                                description="By checking this box, you agree to the specific license terms for this project.{{ $licenseTemplate ? ' License: ' . $licenseTemplate->name : '' }}"
                            />
                            <template x-if="errors.license">
                                <flux:error x-text="errors.license" class="mt-2" />
                            </template>
                        </flux:field>
                    </flux:card>
                @endif
            </form>
            
            <!-- Modal Footer -->
            <div class="flex flex-col sm:flex-row gap-4 sm:justify-end">
                <flux:button variant="ghost" @click="open = false">
                    Cancel
                </flux:button>
                <flux:button variant="primary" 
                             @click="submitForm()"
                             x-bind:disabled="submitting">
                    <span x-show="!submitting" class="flex items-center gap-2">
                        <flux:icon name="paper-airplane" size="sm" />
                        Start My Pitch
                    </span>
                    <span x-show="submitting" class="flex items-center gap-2">
                        <flux:icon name="arrow-path" size="sm" class="animate-spin" />
                        Starting Pitch...
                    </span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    @if($project->license_template_id && $project->licenseTemplate)
        <!-- Project License Preview Modal -->
        <flux:modal name="project-license" class="max-w-2xl">
            <div class="p-6">
                <flux:heading size="md" class="text-slate-800 dark:text-slate-200 mb-4">{{ $project->licenseTemplate->name }}</flux:heading>
                
                <div class="mb-4 flex gap-2">
                    <flux:badge color="zinc" size="sm">{{ $project->licenseTemplate->category_name ?? 'License' }}</flux:badge>
                    @if($project->licenseTemplate->use_case)
                        <flux:badge color="blue" size="sm">{{ $project->licenseTemplate->use_case_name }}</flux:badge>
                    @endif
                </div>
                
                <flux:card class="bg-gray-50 dark:bg-gray-900 max-h-96 overflow-y-auto mb-6">
                    <flux:text size="sm" class="whitespace-pre-line text-slate-700 dark:text-slate-300">
                        {!! nl2br(e($project->licenseTemplate->generateLicenseContent())) !!}
                    </flux:text>
                </flux:card>
                
                <div class="flex justify-end">
                    <flux:button variant="ghost" @click="$flux.modal('project-license').close()">Close</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

</div>

<script>
    // Global function to open the modal (called from project header)
    window.openPitchTermsModal = function() {
        window.dispatchEvent(new CustomEvent('open-pitch-modal'));
    };
</script>