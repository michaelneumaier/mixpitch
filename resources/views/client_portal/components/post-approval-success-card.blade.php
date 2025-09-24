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
                class="mb-6 rounded-xl border border-green-200/50 bg-gradient-to-r from-green-50/80 to-emerald-50/80 p-6 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div
                            class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-emerald-600">
                            <flux:icon.receipt-percent class="text-white" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-green-800">Payment Confirmed</h4>
                            <p class="text-sm text-green-700">Amount: ${{ number_format($pitch->payment_amount, 2) }} • Processed securely via Stripe</p>
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

        @if (isset($milestones) && $milestones->count() > 0)
            <div class="mb-6 rounded-xl bg-purple-50 p-6 dark:bg-purple-900/20">
                <div class="mb-4 flex items-center gap-2">
                    <flux:icon.flag class="text-purple-500" />
                    <flux:heading size="lg">Milestones</flux:heading>
                </div>
                <div class="space-y-3">
                    @foreach ($milestones as $m)
                        <div class="flex items-center justify-between rounded-xl border bg-white p-4 dark:bg-gray-700">
                            <div class="min-w-0 flex-1">
                                <flux:heading size="sm" class="truncate">{{ $m->name }}</flux:heading>
                                @if ($m->description)
                                    <flux:subheading class="truncate">{{ $m->description }}</flux:subheading>
                                @endif
                                <flux:text size="xs" class="mt-1">
                                    Status: {{ ucfirst($m->status) }}
                                    @if ($m->payment_status)
                                        • Payment: {{ str_replace('_', ' ', $m->payment_status) }}
                                    @endif
                                </flux:text>
                            </div>
                            <div class="ml-4 flex items-center gap-3">
                                <flux:heading size="sm">${{ number_format($m->amount, 2) }}</flux:heading>
                                @if ($m->status !== 'approved' || ($m->amount > 0 && $m->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_PAID))
                                    <form method="POST"
                                        action="{{ route('client.portal.milestones.approve', ['project' => $project->id, 'milestone' => $m->id]) }}">
                                        @csrf
                                        <flux:button type="submit" variant="primary" size="sm">
                                            @if ($m->amount > 0)
                                                <flux:icon.credit-card class="mr-2" />Approve &amp; Pay
                                            @else
                                                <flux:icon.check class="mr-2" />Approve
                                            @endif
                                        </flux:button>
                                    </form>
                                @else
                                    <flux:badge variant="success">
                                        <flux:icon.check-circle class="mr-1" /> Completed
                                    </flux:badge>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
            <div
                class="mb-6 rounded-xl border border-emerald-200/50 bg-gradient-to-r from-emerald-50/80 to-green-50/80 p-6 backdrop-blur-sm">
                <h4 class="mb-4 flex items-center font-semibold text-emerald-800">
                    <flux:icon.gift class="mr-2" />
                    Your Project Deliverables
                </h4>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:button
                        href="{{ URL::temporarySignedRoute('client.portal.deliverables', now()->addDays(7), ['project' => $project->id]) }}"
                        variant="success" size="lg" class="justify-center">
                        <flux:icon.arrow-down-tray class="mr-2" />
                        Download Files
                    </flux:button>
                    @if ($pitch->payment_amount > 0 && $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                        <flux:button
                            href="{{ URL::temporarySignedRoute('client.portal.invoice', now()->addDays(7), ['project' => $project->id]) }}"
                            variant="primary" size="lg" class="justify-center">
                            <flux:icon.receipt-percent class="mr-2" />
                            View Invoice
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif

        <div class="text-center">
            <p class="mb-4 text-sm text-gray-600">
                Need to get in touch? Use the communication section below to send a message to your producer.
            </p>

            @if ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                <div
                    class="inline-flex items-center rounded-lg border border-blue-200 bg-gradient-to-r from-blue-100 to-purple-100 px-4 py-2 text-blue-800">
                    <flux:icon.star class="mr-2" />
                    <span class="font-medium">We'd love your feedback on this project!</span>
                </div>
            @endif
        </div>
    </flux:callout>
@endif
