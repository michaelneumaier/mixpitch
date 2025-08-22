<div>
    @if($producerData && (Auth::user()->hasRole('producer') || Auth::user()->hasRole('admin')))
    <flux:navlist.group heading="Finances" expandable :expanded="false" icon="currency-dollar">
        <!-- Earnings -->
        <flux:navlist.item icon="banknotes" href="{{ route('payouts.index') }}">
            <div class="flex items-center justify-between w-full">
                <span>Earnings</span>
                <flux:badge size="sm" color="emerald">${{ number_format($producerData['earnings']['total'] ?? 0, 0) }}</flux:badge>
            </div>
        </flux:navlist.item>

        <!-- Payout Status -->
        <flux:navlist.item icon="credit-card" href="{{ route('stripe.connect.setup') }}">
            <div class="flex items-center justify-between w-full">
                <span>Payouts</span>
                @if(($producerData['stripe_connect']['can_receive_payouts'] ?? false))
                    <flux:badge color="emerald" size="sm" icon="check">Ready</flux:badge>
                @elseif(($producerData['stripe_connect']['account_exists'] ?? false))
                    <flux:badge color="amber" size="sm" icon="clock">Setup</flux:badge>
                @else
                    <flux:badge color="red" size="sm" icon="exclamation-triangle">Setup</flux:badge>
                @endif
            </div>
        </flux:navlist.item>

        <!-- Client Projects -->
        <flux:navlist.item icon="users" href="{{ route('producer.client-management') }}">
            <div class="flex items-center justify-between w-full">
                <span>Clients</span>
                @if(($producerData['client_management']['total_projects'] ?? 0) > 0)
                    <flux:badge size="sm" color="blue">{{ $producerData['client_management']['total_projects'] }}</flux:badge>
                @else
                    <span class="text-sm text-gray-500 dark:text-gray-400">0</span>
                @endif
            </div>
        </flux:navlist.item>
    </flux:navlist.group>
    @endif
</div>