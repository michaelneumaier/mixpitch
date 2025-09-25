@php
    $isReadyForReview = $pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW;
    $canSubmit = in_array($pitch->status, [
        \App\Models\Pitch::STATUS_IN_PROGRESS,
        \App\Models\Pitch::STATUS_REVISIONS_REQUESTED,
        \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
        \App\Models\Pitch::STATUS_DENIED,
    ]);
@endphp

<div>
    @if($isReadyForReview)
        <!-- Recall Submission Section -->
        <flux:card class="mb-2">
            <div class="mb-6 flex items-center gap-3">
                <flux:icon.arrow-uturn-left variant="solid"
                    class="{{ $workflowColors['icon'] }} h-8 w-8" />
                <div>
                    <flux:heading size="lg"
                        class="{{ $workflowColors['text_primary'] }}">
                        Submission Under Review
                    </flux:heading>
                    <flux:subheading class="{{ $workflowColors['text_muted'] }}">
                        Your work has been submitted to the client for review
                    </flux:subheading>
                </div>
            </div>

            <!-- Submission Status Info -->
            <div class="mb-6 rounded-xl border border-purple-200/50 bg-gradient-to-r from-purple-50/80 to-indigo-50/80 p-4 backdrop-blur-sm">
                <div class="flex items-center">
                    <div class="mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-purple-500 to-indigo-600">
                        <i class="fas fa-eye text-sm text-white"></i>
                    </div>
                    <div>
                        <h5 class="font-semibold text-purple-800">Awaiting Client Review</h5>
                        <p class="text-sm text-purple-700">
                            Submitted {{ $pitch->updated_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- File Count Display -->
            <div class="mb-6 flex items-center justify-between rounded-lg bg-slate-50 dark:bg-slate-800 p-4">
                <div class="flex items-center gap-2">
                    <flux:icon.document-text class="h-5 w-5 text-slate-600 dark:text-slate-400" />
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ $this->producerFiles->count() }} {{ Str::plural('file', $this->producerFiles->count()) }} submitted
                    </span>
                </div>
                @if($this->audioFiles->count() > 0 && $pitch->watermarking_enabled)
                    <flux:badge variant="outline" size="sm">
                        <flux:icon.shield-check class="mr-1" />
                        Audio Protection Active
                    </flux:badge>
                @endif
            </div>

            <!-- Recall Button Section -->
            <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                <div class="mb-4 rounded-lg border border-amber-200/50 bg-amber-50/50 p-4">
                    <div class="flex items-start gap-2">
                        <flux:icon.information-circle class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600" />
                        <div class="text-sm text-amber-700">
                            <p class="font-medium mb-1">Need to make changes?</p>
                            <p>You can recall your submission to make updates. This will change the status back to "In Progress" and notify the client.</p>
                        </div>
                    </div>
                </div>

                <button wire:click="recallSubmission" 
                        wire:confirm="Are you sure you want to recall this submission? The client will be notified that you're making changes."
                        wire:loading.attr="disabled"
                        class="group relative inline-flex w-full items-center justify-center overflow-hidden rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 px-6 py-4 text-lg font-bold text-white duration-200 hover:from-amber-700 hover:to-orange-700 hover:shadow-xl disabled:opacity-50">
                    <div class="absolute inset-0 -translate-x-full -skew-x-12 transform bg-white/20 transition-transform duration-700 group-hover:translate-x-full"></div>
                    <span wire:loading wire:target="recallSubmission"
                        class="mr-3 inline-block h-5 w-5 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                    <flux:icon.arrow-uturn-left wire:loading.remove wire:target="recallSubmission" class="relative z-10 mr-3 h-5 w-5" />
                    <span class="relative z-10">Recall Submission</span>
                </button>
            </div>
        </flux:card>
    @elseif($canSubmit)
        <!-- Submit for Review Section -->
        <flux:card class="mb-2">
            <div class="mb-6 flex items-center gap-3">
                <flux:icon.paper-airplane variant="solid"
                    class="{{ $workflowColors['icon'] }} h-8 w-8" />
                <div>
                    <flux:heading size="lg"
                        class="{{ $workflowColors['text_primary'] }}">Ready to Submit for
                        Review?</flux:heading>
                    <flux:subheading class="{{ $workflowColors['text_muted'] }}">Submit
                        your work to your client for review and approval</flux:subheading>
                </div>
            </div>

            @if ($this->producerFiles->count() === 0)
                <div class="mb-4 rounded-xl border border-amber-200/50 bg-gradient-to-r from-amber-50/80 to-orange-50/80 p-4 backdrop-blur-sm">
                    <div class="flex items-center">
                        <div class="mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-orange-600">
                            <i class="fas fa-exclamation-triangle text-sm text-white"></i>
                        </div>
                        <div>
                            <h5 class="font-semibold text-amber-800">No deliverables uploaded</h5>
                            <p class="text-sm text-amber-700">You need to upload at least one file before submitting for review.</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="mb-4 rounded-xl border border-green-200/50 bg-gradient-to-r from-green-50/80 to-emerald-50/80 p-4 backdrop-blur-sm">
                    <div class="flex items-center">
                        <div class="mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-emerald-600">
                            <i class="fas fa-check-circle text-sm text-white"></i>
                        </div>
                        <div>
                            <h5 class="font-semibold text-green-800">
                                {{ $this->producerFiles->count() }}
                                {{ Str::plural('file', $this->producerFiles->count()) }}
                                ready</h5>
                            <p class="text-sm text-green-700">Your deliverables are ready to be submitted to the client.</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Watermarking Toggle Section -->
            @if ($this->producerFiles->count() > 0)
                <div class="mb-4 rounded-xl border border-purple-200/30 bg-white/60 p-4 backdrop-blur-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center">
                            <h5 class="mr-2 font-semibold text-purple-900">Audio Protection</h5>
                            <button wire:click="$toggle('showWatermarkingInfo')"
                                class="text-purple-600 transition-colors hover:text-purple-800">
                                <i class="fas fa-info-circle text-sm"></i>
                            </button>
                        </div>

                        <!-- Toggle Switch -->
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" wire:model.live="watermarkingEnabled" class="peer sr-only">
                            <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-purple-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300"></div>
                            <span class="ml-3 text-sm font-medium text-purple-900">
                                {{ $watermarkingEnabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </label>
                    </div>

                    <!-- Information Panel -->
                    @if ($showWatermarkingInfo)
                        <div class="mb-3 rounded-lg border border-purple-200/50 bg-purple-50/50 p-3 text-sm text-purple-800">
                            <p class="mb-2"><strong>Audio Protection adds a subtle watermark to your files during client review.</strong></p>
                            <ul class="list-inside list-disc space-y-1 text-xs">
                                <li>Protects your intellectual property during the review phase</li>
                                <li>Client receives clean, unwatermarked files after approval and payment</li>
                                <li>Processing takes 30-60 seconds per audio file</li>
                                <li>Does not affect non-audio files (PDFs, images, etc.)</li>
                            </ul>
                        </div>
                    @endif

                    <!-- Audio Files Preview -->
                    @if ($this->audioFiles->count() > 0)
                        <div class="mt-3 rounded-lg bg-purple-50/30 p-3">
                            <p class="mb-2 text-xs font-medium text-purple-700">
                                {{ $this->audioFiles->count() }} audio file(s) will be
                                {{ $watermarkingEnabled ? 'processed with watermarking' : 'submitted without processing' }}:
                            </p>
                            <ul class="space-y-1 text-xs text-purple-600">
                                @foreach ($this->audioFiles->take(3) as $file)
                                    <li class="flex items-center">
                                        <i class="fas fa-music mr-2"></i>
                                        {{ $file->file_name }}
                                        @if ($watermarkingEnabled && $file->audio_processed)
                                            <span class="ml-2 text-green-600">(Already processed)</span>
                                        @endif
                                    </li>
                                @endforeach
                                @if ($this->audioFiles->count() > 3)
                                    <li class="italic text-purple-500">... and {{ $this->audioFiles->count() - 3 }} more</li>
                                @endif
                            </ul>
                        </div>
                    @else
                        <div class="mt-3 rounded-lg bg-gray-50/30 p-3">
                            <p class="text-xs text-gray-600">
                                <i class="fas fa-info-circle mr-2"></i>
                                No audio files detected. Watermarking only affects audio files (MP3, WAV, etc.).
                            </p>
                        </div>
                    @endif
                </div>
            @endif

            @if (in_array($pitch->status, [
                    \App\Models\Pitch::STATUS_REVISIONS_REQUESTED,
                    \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
                ]) && $responseToFeedback)
                <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <h5 class="mb-2 font-medium text-blue-800">Your Response to Feedback:</h5>
                    <p class="text-sm italic text-blue-700">{{ $responseToFeedback }}</p>
                </div>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row">
                @if ($this->producerFiles->count() > 0)
                    <button wire:click="submitForReview" wire:loading.attr="disabled"
                        class="group relative inline-flex flex-1 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 text-lg font-bold text-white duration-200 hover:from-purple-700 hover:to-indigo-700 hover:shadow-xl disabled:opacity-50">
                        <div class="absolute inset-0 -translate-x-full -skew-x-12 transform bg-white/20 transition-transform duration-700 group-hover:translate-x-full"></div>
                        <span wire:loading wire:target="submitForReview"
                            class="mr-3 inline-block h-5 w-5 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                        <i wire:loading.remove wire:target="submitForReview" class="fas fa-paper-plane relative z-10 mr-3"></i>
                        <span class="relative z-10">
                            @if (in_array($pitch->status, [
                                    \App\Models\Pitch::STATUS_REVISIONS_REQUESTED,
                                    \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
                                ]))
                                Submit Revisions
                            @else
                                Submit for Review
                            @endif
                        </span>
                    </button>
                @else
                    <button disabled
                        class="inline-flex flex-1 cursor-not-allowed items-center justify-center rounded-xl bg-gray-400 px-6 py-4 text-lg font-bold text-white opacity-50">
                        <i class="fas fa-paper-plane mr-3"></i>
                        Submit for Review
                    </button>
                @endif

                <button
                    onclick="window.scrollTo({top: document.querySelector('[data-section=producer-deliverables]').offsetTop - 100, behavior: 'smooth'})"
                    class="inline-flex flex-1 items-center justify-center rounded-xl border border-purple-300 bg-gradient-to-r from-purple-100 to-indigo-100 px-6 py-4 font-medium text-purple-800 duration-200 hover:from-purple-200 hover:to-indigo-200 hover:shadow-md">
                    <i class="fas fa-upload mr-3"></i>Upload More Files
                </button>
            </div>

            <div class="mt-4 text-center">
                <p class="text-sm text-purple-600">
                    <i class="fas fa-info-circle mr-1"></i>
                    Once submitted, your client will receive an email notification and can review your work through their secure portal.
                </p>
            </div>
        </flux:card>
    @endif
</div>
