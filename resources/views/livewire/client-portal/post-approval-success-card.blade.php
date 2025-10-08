<div class="mb-2">
    @if (in_array($pitch->status, [\App\Models\Pitch::STATUS_APPROVED, \App\Models\Pitch::STATUS_COMPLETED]))
        <flux:callout variant="success">
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-500">
                    <flux:icon.check class="text-2xl text-white" />
                </div>
                <flux:heading size="xl" class="mb-2">
                    @if ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                        🎉 Project Completed!
                    @else
                        ✅ Project Approved!
                    @endif
                </flux:heading>
                <flux:text>
                    @if ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                        Your project has been successfully completed. All deliverables are ready for download.
                    @else
                        Thank you for approving the project!
                        @if ($pitch->payment_amount > 0)
                            Payment has been processed successfully.
                        @endif
                    @endif
                </flux:text>
            </div>

            @if ($pitch->payment_amount > 0 && $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                <div
                    class="mb-6 rounded-xl border border-green-200/50 bg-gradient-to-r from-green-50/80 to-emerald-50/80 p-6 backdrop-blur-sm dark:border-green-800/50 dark:from-green-900/30 dark:to-emerald-900/30">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div
                                class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-emerald-600">
                                <flux:icon.receipt-percent class="text-white" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-green-800 dark:text-green-300">Payment Confirmed</h4>
                                <p class="text-sm text-green-700 dark:text-green-400">Amount: ${{ number_format($pitch->payment_amount, 2) }} • Processed securely via Stripe</p>
                            </div>
                        </div>
                        <flux:button
                            href="{{ URL::temporarySignedRoute('client.portal.invoice', now()->addDays(7), ['project' => $project->id]) }}"
                            variant="primary" size="sm">
                            <flux:icon.arrow-down-tray class="mr-2" />
                            View Invoice
                        </flux:button>
                    </div>
                </div>
            @endif

            @php
                $hasMilestones = isset($milestones) && $milestones->count() > 0;
                $allMilestonesPaid = $hasMilestones &&
                    $milestones->where('payment_status', \App\Models\Pitch::PAYMENT_STATUS_PAID)->count() === $milestones->count();
            @endphp

            @if ($hasMilestones && !$allMilestonesPaid)
                {{-- Show incomplete milestones warning --}}
                <div class="mb-6 rounded-xl border-2 border-amber-300 bg-amber-50 p-6 dark:border-amber-700 dark:bg-amber-900/20">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500">
                            <flux:icon.exclamation-triangle class="text-white" />
                        </div>
                        <div>
                            <flux:heading size="lg">Complete Milestone Payments</flux:heading>
                            <flux:subheading>Pay all milestones to download your deliverables</flux:subheading>
                        </div>
                    </div>

                    @php
                        $paidCount = $milestones->where('payment_status', \App\Models\Pitch::PAYMENT_STATUS_PAID)->count();
                        $totalCount = $milestones->count();
                    @endphp

                    <div class="rounded-lg bg-white p-4 dark:bg-gray-800">
                        <div class="mb-2 flex items-center justify-between">
                            <flux:text size="sm" class="font-medium">Milestone Progress</flux:text>
                            <flux:text size="sm" class="font-semibold text-amber-600">{{ $paidCount }} of {{ $totalCount }} paid</flux:text>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                            <div class="h-full bg-gradient-to-r from-amber-500 to-orange-600 transition-all"
                                 style="width: {{ ($paidCount / $totalCount * 100) }}%"></div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-lg bg-blue-50 p-3 dark:bg-blue-900/30">
                        <flux:text size="sm" class="text-blue-800 dark:text-blue-300">
                            <flux:icon.information-circle class="mr-1 inline h-4 w-4" />
                            Once all milestones are paid, your deliverables will be available for download below.
                        </flux:text>
                    </div>
                </div>
            @elseif ($hasMilestones && $allMilestonesPaid)
                {{-- All milestones paid - show success --}}
                <div class="mb-6 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 p-6 dark:from-green-900/20 dark:to-emerald-900/20">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-500">
                            <flux:icon.check-circle class="text-white" />
                        </div>
                        <div>
                            <flux:heading size="lg">All Milestones Complete!</flux:heading>
                            <flux:subheading>All milestone payments have been processed successfully</flux:subheading>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Revision Request Section - Allow clients to change their mind --}}
            <div class="mb-6 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-4 flex items-center gap-2">
                    <flux:icon.arrow-path class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                    <flux:heading size="lg">Changed Your Mind?</flux:heading>
                </div>

                {{-- Revision Status Information --}}
                @php
                    $revisionsUsed = $pitch->revisions_used ?? 0;
                    $includedRevisions = $pitch->included_revisions ?? 2;
                    $additionalRevisionPrice = $pitch->additional_revision_price ?? 0;
                    $revisionsRemaining = max(0, $includedRevisions - $revisionsUsed);
                    $nextRevisionIsFree = $revisionsUsed < $includedRevisions;
                @endphp

                <div class="mb-4 rounded-lg border-2 {{ $nextRevisionIsFree ? 'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-900/20' : 'border-amber-300 bg-amber-100 dark:border-amber-700 dark:bg-amber-900/30' }} p-4">
                    <div class="flex items-start gap-3">
                        @if ($nextRevisionIsFree)
                            <flux:icon.check-circle class="mt-0.5 h-5 w-5 text-green-600 dark:text-green-400" />
                        @else
                            <flux:icon.exclamation-triangle class="mt-0.5 h-5 w-5 text-amber-600 dark:text-amber-400" />
                        @endif
                        <div class="flex-1">
                            <flux:text size="sm" class="font-medium {{ $nextRevisionIsFree ? 'text-green-800 dark:text-green-200' : 'text-amber-800 dark:text-amber-200' }}">
                                @if ($nextRevisionIsFree)
                                    <strong>{{ $revisionsRemaining }}</strong> {{ Str::plural('revision', $revisionsRemaining) }} remaining (included)
                                @else
                                    Additional revision available - <strong>${{ number_format($additionalRevisionPrice, 2) }}</strong>
                                @endif
                            </flux:text>
                            <flux:text size="xs" class="{{ $nextRevisionIsFree ? 'text-green-700 dark:text-green-300' : 'text-amber-700 dark:text-amber-300' }} mt-1">
                                @if ($nextRevisionIsFree)
                                    You can still request {{ $revisionsRemaining }} free {{ Str::plural('revision', $revisionsRemaining) }}.
                                    @if ($additionalRevisionPrice > 0)
                                        <br>Additional revisions beyond {{ $includedRevisions }}: <strong>${{ number_format($additionalRevisionPrice, 2) }}</strong> each
                                    @endif
                                @else
                                    You've used all {{ $includedRevisions }} included revisions. Additional revisions require payment.
                                @endif
                            </flux:text>
                            @if ($pitch->revision_scope_guidelines)
                                <flux:text size="xs" class="mt-2 italic {{ $nextRevisionIsFree ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    Note: {{ $pitch->revision_scope_guidelines }}
                                </flux:text>
                            @endif
                        </div>
                    </div>
                </div>

                <flux:text size="sm" class="mb-4 text-gray-600 dark:text-gray-400">
                    If you'd like to request changes or revisions to the approved work, you can submit your feedback below. The producer will review and submit an updated version.
                </flux:text>

                <form action="{{ URL::temporarySignedRoute('client.portal.revisions', now()->addHours(24), ['project' => $project->id]) }}" method="POST">
                    @csrf
                    <flux:textarea name="feedback" rows="3"
                        placeholder="Describe the changes you'd like to see..."
                        class="mb-3">{{ old('feedback') }}</flux:textarea>
                    @error('feedback')
                        <flux:text size="sm" class="mb-2 text-red-600">{{ $message }}</flux:text>
                    @enderror
                    <flux:button type="submit" variant="primary" size="sm" icon="paper-airplane" class="w-full">
                        Request Revisions
                    </flux:button>
                </form>
            </div>

            @if ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                @php
                    // Determine if deliverables are accessible
                    $deliverablesAccessible = true;
                    if ($hasMilestones && !$allMilestonesPaid) {
                        $deliverablesAccessible = false;
                    }
                @endphp

                <div class="mb-6 rounded-xl border border-emerald-200/50 bg-gradient-to-r from-emerald-50/80 to-green-50/80 p-6 backdrop-blur-sm dark:border-emerald-800/50 dark:from-emerald-900/30 dark:to-green-900/30">
                    <h4 class="mb-4 flex items-center font-semibold text-emerald-800 dark:text-emerald-300">
                        <flux:icon.gift class="mr-2" />
                        Your Project Deliverables
                    </h4>

                    @if (!$deliverablesAccessible)
                        {{-- Locked deliverables --}}
                        <div class="mb-4 rounded-lg border-2 border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/30">
                            <div class="flex items-center gap-3">
                                <flux:icon.lock-closed class="h-6 w-6 text-amber-600" />
                                <div>
                                    <flux:text size="sm" class="font-medium text-amber-800 dark:text-amber-300">
                                        Deliverables Locked
                                    </flux:text>
                                    <flux:text size="xs" class="text-amber-700 dark:text-amber-400">
                                        Complete all milestone payments to unlock downloads
                                    </flux:text>
                                </div>
                            </div>
                        </div>
                        <flux:button variant="ghost" class="w-full justify-center py-3" disabled>
                            <flux:icon.lock-closed class="mr-2" />
                            Complete Milestone Payments to Download
                        </flux:button>
                    @else
                        {{-- Accessible deliverables --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:button
                                wire:click="openDownloadModal"
                                variant="primary" icon="arrow-down-tray" class="justify-center py-3">
                                Download Files
                            </flux:button>
                            @if ($pitch->payment_amount > 0 && ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID || $allMilestonesPaid))
                                <flux:button
                                    href="{{ URL::temporarySignedRoute('client.portal.invoice', now()->addDays(7), ['project' => $project->id]) }}"
                                    variant="primary" icon="receipt-percent" class="justify-center py-3">
                                    View Invoice
                                </flux:button>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            <div class="text-center">
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    Need to get in touch? Use the communication section below to send a message to your producer.
                </p>

                @if ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                    <div
                        class="inline-flex items-center rounded-lg border border-blue-200 bg-gradient-to-r from-blue-100 to-purple-100 px-4 py-2 text-blue-800 dark:border-blue-800 dark:from-blue-900/40 dark:to-purple-900/40 dark:text-blue-300">
                        <flux:icon.star class="mr-2" />
                        <span class="font-medium">We'd love your feedback on this project!</span>
                    </div>
                @endif
            </div>
        </flux:callout>

        {{-- Download Files Modal --}}
        <flux:modal name="download-files" :open="$showDownloadModal" wire:model="showDownloadModal" class="w-full max-w-md sm:max-w-lg">
            <div class="p-4 sm:p-6">
                <flux:heading size="lg" class="mb-4">Download Your Files</flux:heading>

                @if ($this->deliverableFiles->count() > 0)
                    <div class="space-y-2">
                        @foreach ($this->deliverableFiles as $file)
                            <div class="flex items-center gap-2 sm:gap-3 rounded-lg border border-gray-200 p-3 sm:p-4 dark:border-gray-700 max-w-full">
                                <div class="hidden sm:flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900">
                                    <flux:icon :name="$this->getFileIcon($file->mime_type)" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <div class="flex-1 min-w-0 overflow-hidden">
                                    <p class="font-medium text-gray-900 dark:text-gray-100 truncate text-sm sm:text-base">{{ $file->file_name }}</p>
                                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 truncate">
                                        {{ $this->formatFileSize($file->size) }}
                                        @if ($file->created_at)
                                            <span class="hidden sm:inline">• {{ toUserTimezone($file->created_at)->format('M d, Y') }}</span>
                                        @endif
                                    </p>
                                </div>
                                <flux:button
                                    wire:click="downloadFile({{ $file->id }})"
                                    variant="primary"
                                    size="sm"
                                    icon="arrow-down-tray"
                                    class="flex-shrink-0 min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0 md:[&>span]:inline [&>span]:hidden"
                                    aria-label="Download {{ $file->file_name }}">
                                    <span>Download</span>
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center">
                        <flux:icon.folder-open class="mx-auto h-12 w-12 text-gray-400" />
                        <p class="mt-2 text-gray-600 dark:text-gray-400">No files available for download</p>
                    </div>
                @endif

                <div class="mt-6 flex justify-end">
                    <flux:button wire:click="closeDownloadModal" variant="ghost">
                        Close
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- JavaScript to handle file downloads --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('download-file', (event) => {
                window.location.href = event.url;
            });
        });
    </script>
</div>
