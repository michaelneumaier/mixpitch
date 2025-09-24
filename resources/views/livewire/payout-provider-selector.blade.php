<div>
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <flux:callout icon="check-circle" color="green" class="mb-4">
            {{ session('success') }}
        </flux:callout>
    @endif

    {{-- Error Messages --}}
    @error('provider')
        <flux:callout icon="exclamation-circle" color="red" class="mb-4">
            {{ $message }}
        </flux:callout>
    @enderror

    @error('setup')
        <flux:callout icon="exclamation-circle" color="red" class="mb-4">
            {{ $message }}
        </flux:callout>
    @enderror

    @error('refresh')
        <flux:callout icon="exclamation-circle" color="red" class="mb-4">
            {{ $message }}
        </flux:callout>
    @enderror

    @error('remove')
        <flux:callout icon="exclamation-circle" color="red" class="mb-4">
            {{ $message }}
        </flux:callout>
    @enderror

    {{-- Provider Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($providers as $provider)
            <flux:card class="relative {{ $provider['is_preferred'] ? 'ring-2 ring-blue-500' : '' }}">
                {{-- Preferred Badge --}}
                @if($provider['is_preferred'])
                    <div class="absolute top-2 right-2">
                        <flux:badge color="blue" size="sm">
                            <flux:icon name="star" size="xs" class="mr-1" />
                            Preferred
                        </flux:badge>
                    </div>
                @endif

                <div class="p-4">
                    {{-- Provider Header --}}
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-3">
                            {{-- Provider Icon --}}
                            <div class="w-8 h-8 flex items-center justify-center rounded bg-gray-100">
                                @if($provider['name'] === 'stripe')
                                    <span class="text-sm font-bold text-purple-600">S</span>
                                @elseif($provider['name'] === 'paypal')
                                    <span class="text-sm font-bold text-blue-600">P</span>
                                @elseif($provider['name'] === 'wise')
                                    <span class="text-sm font-bold text-green-600">W</span>
                                @else
                                    <span class="text-sm font-bold text-gray-600">{{ substr($provider['display_name'], 0, 1) }}</span>
                                @endif
                            </div>
                            
                            <div>
                                <flux:heading size="sm">{{ $provider['display_name'] }}</flux:heading>
                                @if($provider['supports_instant'])
                                    <flux:badge color="green" size="xs">Instant</flux:badge>
                                @endif
                            </div>
                        </div>

                        {{-- Status Badge --}}
                        @if($provider['is_configured'])
                            @if($provider['is_ready'])
                                <flux:badge color="green" size="sm">Ready</flux:badge>
                            @else
                                <flux:badge color="amber" size="sm">Setup Required</flux:badge>
                            @endif
                        @else
                            <flux:badge color="gray" size="sm">Not Configured</flux:badge>
                        @endif
                    </div>

                    {{-- Pricing Info --}}
                    <div class="mb-3 text-sm text-gray-600">
                        @if(isset($provider['pricing']['description']))
                            {{ $provider['pricing']['description'] }}
                        @else
                            {{ $provider['pricing']['transaction_fee_percentage'] ?? 0 }}% + ${{ $provider['pricing']['transaction_fee_fixed'] ?? 0 }}
                        @endif
                    </div>

                    {{-- Account Status Details --}}
                    @if($provider['is_configured'] && isset($provider['status']))
                        <div class="mb-3 p-2 bg-gray-50 rounded text-xs">
                            <div class="font-medium">{{ $provider['status']['status_display'] }}</div>
                            <div class="text-gray-600">{{ $provider['status']['status_description'] }}</div>
                        </div>
                    @endif

                    {{-- Action Buttons --}}
                    <div class="space-y-2">
                        @if(!$provider['is_configured'])
                            {{-- Setup Button --}}
                            <flux:button 
                                wire:click="setupProvider('{{ $provider['name'] }}')"
                                variant="primary" 
                                size="sm" 
                                icon="plus"
                                class="w-full"
                                :disabled="$loading"
                            >
                                Set Up {{ $provider['display_name'] }}
                            </flux:button>
                        @elseif(!$provider['is_ready'])
                            {{-- Complete Setup or Get Onboarding Link --}}
                            <flux:button 
                                wire:click="getOnboardingLink('{{ $provider['name'] }}')"
                                variant="primary" 
                                size="sm" 
                                icon="arrow-top-right-on-square"
                                class="w-full"
                                :disabled="$loading"
                            >
                                Complete Setup
                            </flux:button>
                            
                            <flux:button 
                                wire:click="refreshProviderStatus('{{ $provider['name'] }}')"
                                variant="outline" 
                                size="sm" 
                                icon="arrow-path"
                                class="w-full"
                                :disabled="$loading"
                            >
                                Refresh Status
                            </flux:button>
                        @else
                            {{-- Select/Switch Provider --}}
                            @if(!$provider['is_preferred'])
                                <flux:button 
                                    wire:click="selectProvider('{{ $provider['name'] }}')"
                                    variant="primary" 
                                    size="sm" 
                                    icon="check"
                                    class="w-full"
                                    :disabled="$loading"
                                >
                                    Use for Payouts
                                </flux:button>
                            @else
                                <flux:button 
                                    variant="primary" 
                                    size="sm" 
                                    icon="star"
                                    class="w-full"
                                    disabled
                                >
                                    Current Provider
                                </flux:button>
                            @endif

                            <div class="flex space-x-2">
                                <flux:button 
                                    wire:click="refreshProviderStatus('{{ $provider['name'] }}')"
                                    variant="outline" 
                                    size="sm" 
                                    icon="arrow-path"
                                    class="flex-1"
                                    :disabled="$loading"
                                >
                                </flux:button>
                                
                                <flux:button 
                                    wire:click="removeProvider('{{ $provider['name'] }}')"
                                    variant="outline" 
                                    size="sm" 
                                    icon="trash"
                                    class="flex-1 text-red-600 hover:text-red-700"
                                    :disabled="$loading"
                                    onclick="return confirm('Are you sure you want to remove this payout method?')"
                                >
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </div>
            </flux:card>
        @endforeach
    </div>

    {{-- Setup Modal --}}
    <flux:modal wire:model="showSetupModal" class="max-w-md">
        @if($setupProvider)
            @php
                $providerData = collect($providers)->firstWhere('name', $setupProvider);
            @endphp
            
            <div class="space-y-6">
                {{-- Header --}}
                <div class="flex items-center gap-3">
                    <flux:icon name="credit-card" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    <flux:heading size="lg">Set Up {{ $providerData['display_name'] ?? $setupProvider }}</flux:heading>
                </div>

                {{-- Body --}}
                <div>
                    @if($setupProvider === 'paypal')
                        {{-- PayPal Setup Form --}}
                        <div class="space-y-4">
                            <flux:field>
                                <flux:label>PayPal Email Address</flux:label>
                                <flux:input 
                                    wire:model="setupData.paypal_email" 
                                    type="email" 
                                    placeholder="your-paypal@email.com"
                                    required
                                />
                                <flux:error name="setupData.paypal_email" />
                                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                    Enter the email address associated with your PayPal account. This is where you'll receive payouts.
                                </flux:text>
                            </flux:field>
                        </div>
                    @elseif($setupProvider === 'stripe')
                        {{-- Stripe setup handled via external onboarding --}}
                        <div class="text-center py-4">
                            <flux:text class="text-gray-600 dark:text-gray-400">
                                Stripe Connect requires external setup. Click "Continue to Stripe" to complete your account setup.
                            </flux:text>
                        </div>
                    @else
                        {{-- Generic setup form --}}
                        <div class="space-y-4">
                            <flux:field>
                                <flux:label>Account Email</flux:label>
                                <flux:input 
                                    wire:model="setupData.email" 
                                    type="email" 
                                    placeholder="your@email.com"
                                    required
                                />
                                <flux:error name="setupData.email" />
                            </flux:field>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 pt-4">
                    <flux:modal.close>
                        <flux:button wire:click="cancelSetup" variant="outline">
                            Cancel
                        </flux:button>
                    </flux:modal.close>
                    
                    @if($setupProvider === 'stripe')
                        <flux:button wire:click="getOnboardingLink('stripe')" variant="primary">
                            Continue to Stripe
                        </flux:button>
                    @else
                        <flux:button wire:click="completeSetup" variant="primary" :disabled="$loading">
                            @if($loading)
                                <flux:icon name="arrow-path" class="animate-spin mr-2" size="xs" />
                            @endif
                            Complete Setup
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Loading Overlay --}}
    <div wire:loading class="fixed inset-0 bg-black bg-opacity-25 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <flux:icon name="arrow-path" class="animate-spin" />
            <span>Processing...</span>
        </div>
    </div>
</div>