@props(['project', 'pitch', 'currentSnapshot'])

@if ($pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
    <flux:card class="mb-6">
        <div class="mb-6 flex items-center gap-3">
            <flux:icon.clipboard-document-check class="animate-pulse text-green-500" />
            <div>
                <flux:heading size="lg">Review &amp; Approval</flux:heading>
                <flux:subheading>The project is ready for your review. Please approve or request revisions.</flux:subheading>
            </div>
        </div>

        {{-- Payment Information Banner --}}
        @if ($pitch->payment_amount > 0)
            <flux:callout variant="info" class="mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <flux:icon.credit-card class="text-blue-500" />
                        <div>
                            <flux:heading size="sm">Payment Required: ${{ number_format($pitch->payment_amount, 2) }}</flux:heading>
                            <flux:subheading>Secure payment processing via Stripe</flux:subheading>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 text-green-600">
                        <flux:icon.shield-check class="h-4 w-4" />
                        <flux:text size="sm">Secure</flux:text>
                    </div>
                </div>
            </flux:callout>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Approve Form --}}
            <div class="rounded-xl bg-green-50 p-6 dark:bg-green-900/20">
                <div class="mb-4 flex items-center gap-2">
                    <flux:icon.check-circle class="text-green-500" />
                    <flux:heading size="sm">Approve Project</flux:heading>
                </div>
                <flux:text size="sm" class="mb-4">
                    @if ($pitch->payment_amount > 0)
                        Clicking approve will redirect you to secure payment processing. You'll be charged ${{ number_format($pitch->payment_amount, 2) }} and the producer will be notified of completion.
                    @else
                        Clicking approve will notify the producer that the project is complete and satisfactory.
                    @endif
                </flux:text>

                <form action="{{ URL::temporarySignedRoute('client.portal.approve', now()->addHours(24), ['project' => $project->id]) }}" method="POST">
                    @csrf
                    <flux:button type="submit" variant="primary" size="lg" class="w-full">
                        @if ($pitch->payment_amount > 0)
                            <flux:icon.credit-card class="mr-2" />
                            Approve &amp; Pay ${{ number_format($pitch->payment_amount, 2) }}
                        @else
                            <flux:icon.check-circle class="mr-2" />
                            Approve Project
                        @endif
                    </flux:button>
                </form>

                @if ($pitch->payment_amount > 0)
                    <div class="mt-3 flex items-center justify-center gap-1">
                        <flux:icon.lock-closed class="h-3 w-3 text-green-600" />
                        <flux:text size="xs" class="text-green-600">Powered by Stripe • SSL Encrypted</flux:text>
                    </div>
                @endif
            </div>

            {{-- Request Revisions Form --}}
            <div class="rounded-xl bg-amber-50 p-6 dark:bg-amber-900/20">
                <div class="mb-4 flex items-center gap-2">
                    <flux:icon.pencil class="text-amber-500" />
                    <flux:heading size="sm">Request Revisions</flux:heading>
                </div>
                <flux:text size="sm" class="mb-4">
                    Use our structured feedback system to provide specific, organized feedback about what needs to be changed.
                </flux:text>

                {{-- Structured Feedback Form --}}
                <div class="mb-4 rounded-lg bg-white p-4 dark:bg-gray-700">
                    @livewire('structured-feedback-form', [
                        'pitch' => $pitch,
                        'pitchFile' => ($currentSnapshot->files ?? collect())->first(),
                        'clientEmail' => $project->client_email,
                    ])
                </div>

                {{-- Traditional Text Feedback --}}
                <div class="border-t border-amber-200 pt-4 dark:border-amber-800">
                    <flux:heading size="sm" class="mb-3">Or send traditional feedback:</flux:heading>
                    <form action="{{ URL::temporarySignedRoute('client.portal.revisions', now()->addHours(24), ['project' => $project->id]) }}" method="POST">
                        @csrf
                        <flux:textarea name="feedback" rows="3"
                            placeholder="Additional feedback or specific requests..."
                            class="mb-3">{{ old('feedback') }}</flux:textarea>
                        @error('feedback')
                            <flux:text size="sm" class="mb-2 text-red-600">{{ $message }}</flux:text>
                        @enderror
                        <flux:button type="submit" variant="warning" size="sm" class="w-full">
                            <flux:icon.paper-airplane class="mr-2" />
                            Send Traditional Feedback
                        </flux:button>
                    </form>
                </div>
            </div>
        </div>
    </flux:card>
@endif
