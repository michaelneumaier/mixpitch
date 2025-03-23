<x-filament-widgets::widget>
    <div class="space-y-4">
        @if($hasPaymentMethod)
            {{ $this->form }}
            
            <div class="flex justify-end">
                <x-filament::button wire:click="processPayment" color="success">
                    {{ __('Make Payment') }}
                </x-filament::button>
            </div>
        @else
            <div class="bg-yellow-50 dark:bg-yellow-900 rounded-lg p-4 border border-yellow-200 dark:border-yellow-700 text-yellow-800 dark:text-yellow-200">
                <p>{{ __('You need to add a payment method before making a payment.') }}</p>
            </div>
        @endif
    </div>
</x-filament-widgets::widget> 