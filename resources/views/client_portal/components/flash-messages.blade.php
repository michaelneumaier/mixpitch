@if (request()->query('checkout_status') === 'success')
    <flux:callout variant="success" class="mb-6">
        <flux:icon.check-circle class="mr-2" />
        Payment successful! The project has been approved and the producer has been notified.
    </flux:callout>
@elseif(request()->query('checkout_status') === 'cancel')
    <flux:callout variant="warning" class="mb-6">
        <flux:icon.exclamation-triangle class="mr-2" />
        Payment was cancelled. You can try approving again when ready.
    </flux:callout>
@endif

@if (session('success'))
    <flux:callout variant="success" class="mb-6">
        <flux:icon.check-circle class="mr-2" />
        {{ session('success') }}
    </flux:callout>
@endif

@if ($errors->any())
    <flux:callout variant="danger" class="mb-6">
        <flux:icon.exclamation-circle class="mr-2" />
        <div>
            <strong>Please fix the following errors:</strong>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </flux:callout>
@endif

