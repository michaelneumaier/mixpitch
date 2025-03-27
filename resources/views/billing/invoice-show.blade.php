<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Invoice Details') }}
            </h2>
            <a href="{{ route('billing.invoices') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium rounded-md transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Invoices
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Use the shared invoice component -->
            <x-invoice-details :invoice="$invoice" />
            
            <!-- If this is a pitch payment, add a link to the project -->
            @if(isset($invoice->metadata) && isset($invoice->metadata['pitch_id']))
                <div class="mt-6 text-center">
                    <a href="{{ route('projects.manage', $invoice->metadata['project_id']) }}" class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors">
                        <i class="fas fa-project-diagram mr-2"></i> View Related Project
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
