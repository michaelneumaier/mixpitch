<div class="relative">
    <flux:card>
        <!-- Section Header -->
        <div class="mb-4">
            <div class="flex items-center gap-3 mb-2">
                <div class="hidden p-2 bg-gradient-to-r from-emerald-500 to-green-600 rounded-lg shadow-md">
                    <flux:icon name="banknotes" class="text-white" size="lg" />
                </div>
                <flux:heading size="xl" class="bg-gradient-to-r from-gray-900 via-emerald-800 to-green-800 dark:from-gray-100 dark:via-emerald-300 dark:to-green-300 bg-clip-text text-transparent">
                    Billing & Payments
                </flux:heading>
            </div>
            <flux:subheading>Manage your billing, transactions, and earnings</flux:subheading>
        </div>

        <!-- Universal Stats (All Users) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <!-- Recent Transactions -->
            <div class="p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon name="arrow-path" class="text-blue-600 dark:text-blue-400" size="sm" />
                    <span class="text-sm font-medium text-blue-900 dark:text-blue-200">Recent Transactions</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                        ${{ number_format($this->transactionSummary['total_amount'], 2) }}
                    </span>
                    <span class="text-sm text-blue-600 dark:text-blue-400">
                        ({{ $this->transactionSummary['count'] }} {{ Str::plural('transaction', $this->transactionSummary['count']) }})
                    </span>
                </div>
                <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">Last 30 days</p>
            </div>

            <!-- Payment Method -->
            <div class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg border border-purple-100 dark:border-purple-800">
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon name="credit-card" class="text-purple-600 dark:text-purple-400" size="sm" />
                    <span class="text-sm font-medium text-purple-900 dark:text-purple-200">Payment Method</span>
                </div>
                @if($this->paymentMethod['exists'])
                    <div class="flex items-center gap-2">
                        <flux:badge color="purple" size="sm">
                            {{ ucfirst($this->paymentMethod['brand'] ?? 'Card') }}
                        </flux:badge>
                        <span class="text-sm font-mono text-purple-900 dark:text-purple-100">
                            ●●●● {{ $this->paymentMethod['last_four'] }}
                        </span>
                    </div>
                    <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">Default card</p>
                @else
                    <div class="text-sm text-purple-600 dark:text-purple-400">
                        No payment method on file
                    </div>
                    <flux:button href="{{ route('billing') }}" wire:navigate variant="ghost" size="xs" class="mt-2 text-purple-600">
                        Add Payment Method
                    </flux:button>
                @endif
            </div>

            <!-- Next Billing / Subscription Status -->
            <div class="p-4 bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 rounded-lg border border-amber-100 dark:border-amber-800">
                <div class="flex items-center gap-2 mb-2">
                    <flux:icon name="calendar" class="text-amber-600 dark:text-amber-400" size="sm" />
                    <span class="text-sm font-medium text-amber-900 dark:text-amber-200">Next Billing</span>
                </div>
                @if($this->nextBilling)
                    <div class="text-lg font-bold text-amber-900 dark:text-amber-100">
                        ${{ number_format($this->nextBilling['amount'] ?? 0, 2) }}
                    </div>
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                        {{ $this->nextBilling['date'] ? toUserTimezone($this->nextBilling['date'])->format('M j, Y') : 'N/A' }}
                    </p>
                @else
                    <div class="text-sm text-amber-600 dark:text-amber-400">
                        No upcoming billing
                    </div>
                    <flux:badge color="zinc" size="sm" class="mt-1">
                        Free Plan
                    </flux:badge>
                @endif
            </div>
        </div>

        <!-- Producer Section (Conditional) -->
        @if($this->isProducer)
            <flux:separator class="my-6" />

            <!-- Producer Earnings Stats -->
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <flux:icon name="currency-dollar" class="text-emerald-600 dark:text-emerald-400" size="sm" />
                    <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Producer Earnings</flux:heading>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <!-- Total Earnings -->
                    <div class="p-4 bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-lg border border-emerald-200 dark:border-emerald-700">
                        <div class="text-sm text-emerald-700 dark:text-emerald-300 mb-1">Total Earnings</div>
                        <div class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">
                            ${{ number_format($this->earningsSummary['total_earnings'], 2) }}
                        </div>
                        <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">
                            {{ $this->earningsSummary['completed_count'] }} completed {{ Str::plural('payout', $this->earningsSummary['completed_count']) }}
                        </div>
                    </div>

                    <!-- Pending Payouts -->
                    <div class="p-4 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                        <div class="text-sm text-blue-700 dark:text-blue-300 mb-1">Pending Payouts</div>
                        <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                            ${{ number_format($this->earningsSummary['pending_payouts'], 2) }}
                        </div>
                        <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                            {{ $this->earningsSummary['pending_count'] }} {{ Str::plural('payout', $this->earningsSummary['pending_count']) }} in queue
                        </div>
                    </div>

                    <!-- This Month -->
                    <div class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg border border-purple-200 dark:border-purple-700">
                        <div class="text-sm text-purple-700 dark:text-purple-300 mb-1">This Month</div>
                        <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                            ${{ number_format($this->earningsSummary['this_month_earnings'], 2) }}
                        </div>
                        <div class="text-xs text-purple-600 dark:text-purple-400 mt-1">
                            {{ now()->format('F Y') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payout Account Status -->
            <div class="mb-6">
                <div class="p-4 rounded-lg border-2
                    @if($this->payoutAccountStatus['is_active'])
                        bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300 dark:border-emerald-700
                    @elseif($this->payoutAccountStatus['is_restricted'])
                        bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700
                    @else
                        bg-amber-50 dark:bg-amber-900/20 border-amber-300 dark:border-amber-700
                    @endif
                ">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg
                                @if($this->payoutAccountStatus['is_active'])
                                    bg-emerald-500
                                @elseif($this->payoutAccountStatus['is_restricted'])
                                    bg-red-500
                                @else
                                    bg-amber-500
                                @endif
                            ">
                                <flux:icon
                                    :name="$this->payoutAccountStatus['is_active'] ? 'check-circle' : ($this->payoutAccountStatus['is_restricted'] ? 'exclamation-triangle' : 'clock')"
                                    class="text-white"
                                    size="sm"
                                />
                            </div>
                            <div>
                                <div class="font-semibold text-slate-900 dark:text-slate-100">
                                    Payout Account Status
                                </div>
                                <div class="text-sm text-slate-600 dark:text-slate-400">
                                    @if($this->payoutAccountStatus['provider'])
                                        {{ $this->payoutAccountStatus['provider'] }} •
                                    @endif
                                    <flux:badge
                                        :color="$this->payoutAccountStatus['status_color']"
                                        size="sm"
                                    >
                                        {{ $this->payoutAccountStatus['status_text'] }}
                                    </flux:badge>
                                </div>
                            </div>
                        </div>

                        @if($this->payoutAccountStatus['needs_setup'] || $this->payoutAccountStatus['is_restricted'])
                            <flux:button
                                href="{{ route('payouts.setup.index') }}"
                                wire:navigate
                                variant="filled"
                                size="sm"
                                class="flex-shrink-0"
                            >
                                @if($this->payoutAccountStatus['is_restricted'])
                                    Resolve Issues
                                @else
                                    Complete Setup
                                @endif
                            </flux:button>
                        @endif
                    </div>

                    @if($this->payoutAccountStatus['needs_setup'])
                        <div class="mt-3 pt-3 border-t border-amber-200 dark:border-amber-700 text-sm text-amber-800 dark:text-amber-200">
                            <flux:icon name="information-circle" class="inline" size="xs" />
                            Complete your payout account setup to receive earnings from completed projects.
                        </div>
                    @elseif($this->payoutAccountStatus['is_restricted'])
                        <div class="mt-3 pt-3 border-t border-red-200 dark:border-red-700 text-sm text-red-800 dark:text-red-200">
                            <flux:icon name="exclamation-triangle" class="inline" size="xs" />
                            Your payout account requires attention. Please resolve any outstanding issues.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Payouts List -->
            @if($this->recentPayouts->isNotEmpty())
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <flux:icon name="clock" class="text-slate-600 dark:text-slate-400" size="sm" />
                            <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Recent Payouts</flux:heading>
                        </div>
                        <flux:button href="{{ route('payouts.index') }}" wire:navigate icon="arrow-top-right-on-square" variant="ghost" size="sm" class="text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200" />
                    </div>

                    <div class="space-y-3">
                        @foreach($this->recentPayouts as $payout)
                        <div class="flex items-center justify-between p-4 bg-white/70 dark:bg-white/10 rounded-lg border border-slate-100 dark:border-slate-700 hover:bg-white dark:hover:bg-white/20 hover:shadow-md transition-all duration-200">
                            <div class="flex items-center gap-4">
                                <div class="flex-shrink-0">
                                    @if($payout->status === 'completed')
                                        <div class="p-2 bg-gradient-to-r from-emerald-500 to-green-600 rounded-full shadow-sm">
                                            <flux:icon name="check" class="text-white" size="sm" />
                                        </div>
                                    @elseif($payout->status === 'processing')
                                        <div class="p-2 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full shadow-sm">
                                            <flux:icon name="clock" class="text-white" size="sm" />
                                        </div>
                                    @else
                                        <div class="p-2 bg-gradient-to-r from-amber-500 to-yellow-600 rounded-full shadow-sm">
                                            <flux:icon name="clock" class="text-white" size="sm" />
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-slate-900 dark:text-slate-100 truncate">{{ $payout->project->name ?? 'Unknown Project' }}</div>
                                    <div class="flex items-center gap-1 text-sm text-slate-600 dark:text-slate-400">
                                        <flux:icon name="calendar" size="xs" />
                                        <span>{{ toUserTimezone($payout->created_at)->format('M j, Y') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="font-bold text-slate-900 dark:text-slate-100">${{ number_format($payout->net_amount, 2) }}</div>
                                <flux:badge
                                    :color="$payout->status === 'completed' ? 'emerald' : ($payout->status === 'processing' ? 'blue' : 'amber')"
                                    size="sm"
                                    class="mt-1"
                                >
                                    {{ ucfirst(str_replace('_', ' ', $payout->status)) }}
                                </flux:badge>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif

        <!-- Action Buttons -->
        <flux:separator class="my-6" />

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <flux:button
                href="{{ route('billing') }}"
                wire:navigate
                icon="credit-card"
                variant="outline"
                size="sm"
                class="w-full sm:w-auto border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800"
            >
                Manage Billing
            </flux:button>

            @if($this->isProducer)
                <flux:button
                    href="{{ route('payouts.index') }}"
                    wire:navigate
                    icon="banknotes"
                    variant="outline"
                    size="sm"
                    class="w-full sm:w-auto border-emerald-300 dark:border-emerald-600 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/20"
                >
                    View All Payouts
                </flux:button>

                @if($this->payoutAccountStatus['needs_setup'])
                    <flux:button
                        href="{{ route('payouts.setup.index') }}"
                        wire:navigate
                        icon="arrow-right"
                        variant="filled"
                        size="sm"
                        class="w-full sm:w-auto !bg-gradient-to-r !from-emerald-600 !to-green-600 !hover:from-emerald-700 !hover:to-green-700 !text-white"
                    >
                        Complete Payout Setup
                    </flux:button>
                @endif
            @endif
        </div>
    </flux:card>
</div>
