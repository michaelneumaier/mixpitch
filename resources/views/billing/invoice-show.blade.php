<x-layouts.app-sidebar>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <flux:heading size="xl" class="text-gray-900 dark:text-gray-100">
                {{ __('Invoice Details') }}
            </flux:heading>
            <flux:button href="{{ route('billing.invoices') }}" wire:navigate icon="arrow-left" variant="outline" size="sm">
                Back to Invoices
            </flux:button>
        </div>
    </div>

    <!-- Use the shared invoice component -->
    <x-invoice-details :invoice="$invoice" />

    <!-- If this is a pitch payment, add a link to the project -->
    @if(isset($invoice->metadata) && isset($invoice->metadata['pitch_id']))
        <div class="mt-6 text-center">
            <flux:button href="{{ route('projects.manage', $invoice->metadata['project_id']) }}" wire:navigate icon="folder" variant="filled">
                View Related Project
            </flux:button>
        </div>
    @endif
</div>

</x-layouts.app-sidebar>
