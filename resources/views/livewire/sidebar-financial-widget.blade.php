<div class="px-2" x-data="{ expanded: false }">
@if(Auth::check())
    <!-- Financial Widget Header -->
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Finances</h3>
            @if(!($producerData['stripe_connect']['can_receive_payouts'] ?? false))
                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
            @endif
        </div>
        <button @click="expanded = !expanded" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
            <flux:icon name="chevron-down" size="xs" x-show="!expanded" />
            <flux:icon name="chevron-up" size="xs" x-show="expanded" />
        </button>
    </div>

    <!-- Financial Items -->
    <div x-show="expanded" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 transform scale-95" 
         x-transition:enter-end="opacity-100 transform scale-100" 
         x-transition:leave="transition ease-in duration-150" 
         x-transition:leave-start="opacity-100 transform scale-100" 
         x-transition:leave-end="opacity-0 transform scale-95" 
         class="space-y-1 mb-4">
         
        <!-- Earnings -->
        <flux:navlist.item icon="currency-dollar" href="{{ route('payouts.index') }}" class="text-sm">
            <div class="flex items-center justify-between w-full">
                <span>Earnings</span>
                <span class="text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 px-2 py-0.5 rounded-full">${{ number_format($producerData['earnings']['total'] ?? 0, 0) }}</span>
            </div>
        </flux:navlist.item>

        <!-- Payout Status -->
        <flux:navlist.item icon="credit-card" href="{{ route('stripe.connect.setup') }}" class="text-sm">
            <div class="flex items-center justify-between w-full">
                <span>Payouts</span>
                @if(($producerData['stripe_connect']['can_receive_payouts'] ?? false))
                    <flux:badge color="emerald" size="xs" icon="check">Ready</flux:badge>
                @elseif(($producerData['stripe_connect']['account_exists'] ?? false))
                    <flux:badge color="amber" size="xs" icon="clock">Setup</flux:badge>
                @else
                    <flux:badge color="red" size="xs" icon="exclamation-triangle">Setup</flux:badge>
                @endif
            </div>
        </flux:navlist.item>

        <!-- Client Projects -->
        <flux:navlist.item icon="users" href="{{ route('producer.client-management') }}" class="text-sm">
            <div class="flex items-center justify-between w-full">
                <span>Clients</span>
                @if(($producerData['client_management']['total_projects'] ?? 0) > 0)
                    <span class="text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 px-2 py-0.5 rounded-full">{{ $producerData['client_management']['total_projects'] }}</span>
                @else
                    <span class="text-xs text-gray-500 dark:text-gray-400">0</span>
                @endif
            </div>
        </flux:navlist.item>
    </div>
@endif
</div>