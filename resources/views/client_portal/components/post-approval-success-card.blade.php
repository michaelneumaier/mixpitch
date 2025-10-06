@props(['project', 'pitch', 'milestones'])

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
                        variant="success" size="sm">
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
                            href="{{ URL::temporarySignedRoute('client.portal.deliverables', now()->addDays(7), ['project' => $project->id]) }}"
                            variant="success" class="justify-center py-3">
                            <flux:icon.arrow-down-tray class="mr-2" />
                            Download Files
                        </flux:button>
                        @if ($pitch->payment_amount > 0 && ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID || $allMilestonesPaid))
                            <flux:button
                                href="{{ URL::temporarySignedRoute('client.portal.invoice', now()->addDays(7), ['project' => $project->id]) }}"
                                variant="primary" class="justify-center py-3">
                                <flux:icon.receipt-percent class="mr-2" />
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
@endif
