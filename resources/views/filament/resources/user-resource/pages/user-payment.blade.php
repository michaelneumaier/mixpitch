<x-filament::page>
    <x-filament::section>
        <div class="space-y-6">
            <div class="prose dark:prose-invert max-w-none">
                <h2>{{ __('Process Payment for') }} {{ $record->name }}</h2>
                <p>{{ __('Create and process an invoice payment for this customer.') }}</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium mb-4">{{ __('Customer Information') }}</h3>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Name') }}</span>
                            <span class="font-medium">{{ $record->name }}</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Email') }}</span>
                            <span class="font-medium">{{ $record->email }}</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Stripe Customer') }}</span>
                            <span class="font-medium">
                                @if($record->stripe_id)
                                    <span class="text-green-600 dark:text-green-400">{{ $record->stripe_id }}</span>
                                @else
                                    <span class="text-red-600 dark:text-red-400">{{ __('Not created') }}</span>
                                @endif
                            </span>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <h4 class="font-medium mb-3">{{ __('Payment Methods') }}</h4>
                        
                        @if(count($paymentMethods) > 0)
                            <div class="space-y-3">
                                @foreach($paymentMethods as $method)
                                    <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg {{ $method['default'] ? 'ring-2 ring-primary-500' : '' }}">
                                        <div class="flex-shrink-0">
                                            @switch($method['brand'])
                                                @case('visa')
                                                    <svg class="h-8 w-8 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M22.3,2H1.7C0.8,2,0,2.8,0,3.7v16.5c0,1,0.8,1.7,1.7,1.7h20.5c1,0,1.7-0.8,1.7-1.7V3.7C24,2.8,23.2,2,22.3,2z M21.2,5.9h-3.5c-1.1,0-1.8,0.8-1.8,1.8c0,1.4,0.8,2,1.8,2.1h1.5c0.5,0,0.8,0.3,0.7,0.7c0,0.4-0.2,0.7-0.7,0.7h-1.7v0.9h1.7c0.9,0,1.5-0.3,1.7-1v-0.1c0.1-0.1,0.1-0.2,0.1-0.3V5.9z M15.9,12.1l1.5-4.5h1.1l-1.5,4.5H15.9z M13.1,7.6l-0.3,1.7l-0.5-3h-2.1L8.4,12.1h1l1.5-4.5h0.8l0.5,2.4c0.1,0.4,0.1,0.7,0.1,0.9c0-0.3,0.1-0.6,0.2-0.9l0.8-2.4h1.1l-1.5,4.5h-1l0.4-1.7c0.1-0.3,0.1-0.6,0.2-0.9C13.3,7,13.2,7.3,13.1,7.6z M6.5,12.1h-1l0.9-4.5h1L6.5,12.1z" />
                                                    </svg>
                                                    @break
                                                @case('mastercard')
                                                    <svg class="h-8 w-8 text-red-600" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M15.7,2.8c-1.5-0.8-3.2-1.2-5-1.2C4.8,1.6,0,6.4,0,12.3C0,18.2,4.8,23,10.7,23c1.8,0,3.5-0.4,5-1.2c1.5,0.8,3.2,1.2,5,1.2c5.9,0,10.7-4.8,10.7-10.7c0-5.9-4.8-10.7-10.7-10.7C18.9,1.6,17.2,2,15.7,2.8z M23.5,12.3c0,4.1-3.3,7.5-7.5,7.5c-1.2,0-2.3-0.3-3.3-0.8c1.3-1.3,2.2-3.1,2.2-5c0-2-0.8-3.7-2.2-5c1-0.5,2.1-0.8,3.3-0.8C20.2,4.8,23.5,8.1,23.5,12.3z M8.2,12.3c0-1.9,0.8-3.7,2.2-5c-1-0.5-2.1-0.8-3.3-0.8c-4.1,0-7.5,3.3-7.5,7.5c0,4.1,3.3,7.5,7.5,7.5c1.2,0,2.3-0.3,3.3-0.8C9,16,8.2,14.2,8.2,12.3z" />
                                                    </svg>
                                                    @break
                                                @case('amex')
                                                    <svg class="h-8 w-8 text-blue-800" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M22.3,2H1.7C0.8,2,0,2.8,0,3.7v16.5c0,1,0.8,1.7,1.7,1.7h20.5c1,0,1.7-0.8,1.7-1.7V3.7C24,2.8,23.2,2,22.3,2z M13.3,9.3h-2.1v1.4h2v1.3h-2v1.4h2.1v1.2l1.7-2.7l-1.7-2.7V9.3z M6.9,9.3v4.2h-1V9.8L4.7,13.5H3.9L2.7,9.8v3.7h-1V9.3h1.6l1,3.1l1-3.1H6.9z M16.5,13.5h-0.8l-1-1.1h-0.7v1.1h-0.9V9.3h2c1,0,1.7,0.7,1.7,1.5c0,0.6-0.3,1.1-0.9,1.3L16.5,13.5z M14.1,10.2h-1v1.2h1c0.3,0,0.6-0.2,0.6-0.6C14.7,10.5,14.4,10.2,14.1,10.2z M20.6,13.5h-2.9V9.3h2.9v0.9h-2v0.9h1.9v0.9h-1.9v0.9h2V13.5z" />
                                                    </svg>
                                                    @break
                                                @case('discover')
                                                    <svg class="h-8 w-8 text-orange-600" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M22.3,2H1.7C0.8,2,0,2.8,0,3.7v16.5c0,1,0.8,1.7,1.7,1.7h20.5c1,0,1.7-0.8,1.7-1.7V3.7C24,2.8,23.2,2,22.3,2z M12,15.5c-2.5,0-4.5-2-4.5-4.5s2-4.5,4.5-4.5c2.5,0,4.5,2,4.5,4.5S14.5,15.5,12,15.5z M18.2,11c0,0.1,0,0.2,0,0.3c0,1.3-0.9,2.4-2.1,2.7v-1c0.6-0.2,1.1-0.8,1.1-1.5c0-0.1,0-0.3,0-0.4H18.2z" />
                                                    </svg>
                                                    @break
                                                @default
                                                    <svg class="h-8 w-8 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M22.3,2H1.7C0.8,2,0,2.8,0,3.7v16.5c0,1,0.8,1.7,1.7,1.7h20.5c1,0,1.7-0.8,1.7-1.7V3.7C24,2.8,23.2,2,22.3,2z M22,20.2c0,0.1-0.1,0.2-0.2,0.2H2.2c-0.1,0-0.2-0.1-0.2-0.2V3.8c0-0.1,0.1-0.2,0.2-0.2h19.6c0.1,0,0.2,0.1,0.2,0.2V20.2z" />
                                                    </svg>
                                            @endswitch
                                        </div>
                                        <div class="ml-3">
                                            <p class="font-medium">{{ ucfirst($method['brand']) }} •••• {{ $method['last4'] }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Expires') }} {{ $method['exp'] }}</p>
                                        </div>
                                        
                                        @if($method['default'])
                                            <span class="ml-auto px-2 py-1 text-xs bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100 rounded-full">
                                                {{ __('Default') }}
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400">
                                <p>{{ __('No payment methods available for this customer.') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium mb-4">{{ __('Payment Details') }}</h3>
                    
                    @if($record->stripe_id && count($paymentMethods) > 0)
                        <form wire:submit="processPayment">
                            {{ $this->form }}
                            
                            <div class="mt-6">
                                <x-filament::button type="submit" color="primary">
                                    {{ __('Process Payment') }}
                                </x-filament::button>
                            </div>
                        </form>
                    @elseif(!$record->stripe_id)
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 mb-4">
                            <p>{{ __('This user is not registered as a Stripe customer.') }}</p>
                        </div>
                        
                        <form action="{{ route('filament.admin.resources.users.create-stripe-customer', ['record' => $record]) }}" method="POST">
                            @csrf
                            <x-filament::button type="submit" color="primary">
                                {{ __('Create Stripe Customer') }}
                            </x-filament::button>
                        </form>
                    @elseif(count($paymentMethods) === 0)
                        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800 text-yellow-600 dark:text-yellow-400">
                            <p>{{ __('This customer needs to add a payment method before you can process payments.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="mt-4">
                <x-filament::button
                    tag="a"
                    href="{{ route('filament.admin.resources.users.invoices', ['record' => $record]) }}"
                    color="gray"
                    icon="heroicon-m-document-text"
                >
                    {{ __('View Invoices') }}
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament::page> 