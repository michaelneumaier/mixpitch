<flux:card class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-950 dark:to-amber-950 border border-orange-200 dark:border-orange-800">
    <div class="flex flex-col md:flex-row items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <flux:icon name="trophy" variant="solid" class="text-orange-600 dark:text-orange-400 h-8 w-8" />
            <flux:heading size="xl" class="text-orange-900 dark:text-orange-100">Contest Prize Configuration</flux:heading>
        </div>
        <flux:badge color="amber" size="sm">
            @if($project)
                Edit prizes for this contest
            @else
                Configure prizes (will be saved when project is created)
            @endif
        </flux:badge>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <flux:callout color="green" icon="check-circle" class="mb-6">
            {{ session('success') }}
        </flux:callout>
    @endif

    @if (session()->has('error'))
        <flux:callout color="red" icon="exclamation-circle" class="mb-6">
            {{ session('error') }}
        </flux:callout>
    @endif

    {{-- Prize Configuration for Each Placement --}}
    <div class="space-y-2 md:space-y-6">
        @foreach(['1st', '2nd', '3rd', 'runner_up'] as $placement)
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-2 md:p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
                {{-- Placement Header --}}
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">{{ $this->getPlacementEmoji($placement) }}</span>
                        <flux:heading size="lg" class="text-gray-800 dark:text-gray-200">{{ $this->getPlacementDisplayName($placement) }}</flux:heading>
                    </div>
                    <flux:select wire:model.live="prizes.{{ $placement }}.type" class="min-w-[140px]">
                        <option value="none">No Prize</option>
                        <option value="cash">💰 Cash Prize</option>
                        <option value="other">🎁 Other Prize</option>
                    </flux:select>
                </div>

                {{-- Prize Configuration Based on Type --}}
                @if($prizes[$placement]['type'] === 'cash')
                    <div class="bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 rounded-lg p-2 md:p-4">
                        <div class="flex items-center gap-3 mb-3">
                            <flux:icon name="banknotes" class="text-green-600 dark:text-green-400 h-5 w-5" />
                            <flux:heading size="base" class="text-green-800 dark:text-green-200">Cash Prize Configuration</flux:heading>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 md:gap-4">
                            <flux:field>
                                <flux:label class="text-green-700 dark:text-green-300">Currency</flux:label>
                                <flux:select wire:model="prizes.{{ $placement }}.currency">
                                    @foreach($availableCurrencies as $code => $label)
                                        <option value="{{ $code }}">{{ $label }}</option>
                                    @endforeach
                                </flux:select>
                            </flux:field>
                            
                            <flux:field>
                                <flux:label class="text-green-700 dark:text-green-300">Amount</flux:label>
                                <flux:input 
                                    type="number" 
                                    wire:model="prizes.{{ $placement }}.cash_amount"
                                    placeholder="0.00"
                                    step="0.01"
                                    min="0.01"
                                    max="999999.99"
                                    prefix="{{ collect($availableCurrencies)->keys()->contains($prizes[$placement]['currency']) ? 
                                               (match($prizes[$placement]['currency']) {
                                                   'EUR' => '€',
                                                   'GBP' => '£', 
                                                   'CAD' => 'C$',
                                                   'AUD' => 'A$',
                                                   default => '$'
                                               }) : '$' }}"
                                />
                                <flux:error name="prizes.{{ $placement }}.cash_amount" />
                            </flux:field>
                        </div>
                    </div>

                @elseif($prizes[$placement]['type'] === 'other')
                    <div class="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-lg p-2 md:p-4">
                        <div class="flex items-center gap-3 mb-3">
                            <flux:icon name="gift" class="text-blue-600 dark:text-blue-400 h-5 w-5" />
                            <flux:heading size="base" class="text-blue-800 dark:text-blue-200">Other Prize Configuration</flux:heading>
                        </div>
                        
                        <div class="space-y-2 md:space-y-4">
                            <flux:field>
                                <flux:label class="text-blue-700 dark:text-blue-300">Prize Title <span class="text-red-500">*</span></flux:label>
                                <flux:input 
                                    type="text" 
                                    wire:model="prizes.{{ $placement }}.title"
                                    placeholder="e.g., Software License, T-shirt, Studio Time"
                                    maxlength="255"
                                />
                                <flux:error name="prizes.{{ $placement }}.title" />
                            </flux:field>
                            
                            <flux:field>
                                <flux:label class="text-blue-700 dark:text-blue-300">Description</flux:label>
                                <flux:textarea 
                                    wire:model="prizes.{{ $placement }}.description"
                                    placeholder="Describe the prize details, how it will be delivered, etc."
                                    rows="3"
                                    maxlength="1000"
                                />
                                <flux:text size="xs" class="text-blue-600 dark:text-blue-400">{{ strlen($prizes[$placement]['description'] ?? '') }}/1000 characters</flux:text>
                                <flux:error name="prizes.{{ $placement }}.description" />
                            </flux:field>
                            
                            <flux:field>
                                <flux:label class="text-blue-700 dark:text-blue-300">Estimated Value (Optional)</flux:label>
                                <flux:input 
                                    type="number" 
                                    wire:model="prizes.{{ $placement }}.value_estimate"
                                    placeholder="0.00"
                                    step="0.01"
                                    min="0"
                                    max="999999.99"
                                    prefix="$"
                                />
                                <flux:text size="xs" class="text-blue-600 dark:text-blue-400">Optional: For reference and analytics purposes</flux:text>
                                <flux:error name="prizes.{{ $placement }}.value_estimate" />
                            </flux:field>
                        </div>
                    </div>

                @else
                    <div class="bg-gray-50 dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-2 md:p-8 text-center">
                        <flux:icon name="trophy" class="text-gray-400 dark:text-gray-500 h-12 w-12 mx-auto mb-3" />
                        <flux:text class="text-gray-500 dark:text-gray-400 font-medium">No prize configured for this placement</flux:text>
                        <flux:text size="sm" class="text-gray-400 dark:text-gray-500">Select a prize type above to get started</flux:text>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Prize Summary Section --}}
    <div class="mt-8 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950 dark:to-indigo-950 border border-blue-200 dark:border-blue-800 rounded-lg p-2 md:p-6">
        <div class="flex items-center gap-3 mb-4">
            <flux:icon name="chart-bar" class="text-blue-600 dark:text-blue-400 h-6 w-6" />
            <flux:heading size="lg" class="text-blue-800 dark:text-blue-200">Prize Summary</flux:heading>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4 mb-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center border border-blue-200 dark:border-blue-700">
                <flux:heading size="xl" class="text-green-600 dark:text-green-400">${{ number_format($this->getTotalCashPrizes(), 2) }}</flux:heading>
                <flux:text size="sm" class="text-green-700 dark:text-green-300">Total Cash Prizes</flux:text>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center border border-blue-200 dark:border-blue-700">
                <flux:heading size="xl" class="text-blue-600 dark:text-blue-400">${{ number_format($this->getTotalEstimatedValue(), 2) }}</flux:heading>
                <flux:text size="sm" class="text-blue-700 dark:text-blue-300">Total Estimated Value</flux:text>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center border border-blue-200 dark:border-blue-700">
                @php $counts = $this->getPrizeCounts(); @endphp
                <flux:heading size="xl" class="text-purple-600 dark:text-purple-400">{{ $counts['total'] }}</flux:heading>
                <flux:text size="sm" class="text-purple-700 dark:text-purple-300">Total Prizes ({{ $counts['cash'] }} cash, {{ $counts['other'] }} other)</flux:text>
            </div>
        </div>

        {{-- Preview of Configured Prizes --}}
        @if($counts['total'] > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-2 md:p-4 border border-blue-200 dark:border-blue-700">
                <flux:heading size="base" class="text-blue-800 dark:text-blue-200 mb-3">Prize Breakdown:</flux:heading>
                <div class="space-y-2">
                    @foreach(['1st', '2nd', '3rd', 'runner_up'] as $placement)
                        @if($prizes[$placement]['type'] !== 'none')
                            <div class="flex items-center justify-between py-2 px-3 bg-blue-50 dark:bg-blue-950 rounded">
                                <flux:text class="font-medium text-blue-800 dark:text-blue-200">
                                    {{ $this->getPlacementEmoji($placement) }} {{ $this->getPlacementDisplayName($placement) }}
                                </flux:text>
                                <flux:text class="text-blue-700 dark:text-blue-300">
                                    @if($prizes[$placement]['type'] === 'cash')
                                        {{ $prizes[$placement]['currency'] }} {{ number_format($prizes[$placement]['cash_amount'] ?? 0, 2) }}
                                    @else
                                        {{ $prizes[$placement]['title'] ?: 'Other Prize' }}
                                    @endif
                                </flux:text>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Action Buttons --}}
    <div class="flex justify-end space-x-2 md:space-x-4 mt-8">
        <flux:button variant="ghost" wire:click="resetPrizes" icon="arrow-path">
            Reset
        </flux:button>
        
        <flux:button variant="primary" wire:click="savePrizes" icon="check">
            @if($project)
                Save Prize Configuration
            @else
                Configure Prizes
            @endif
        </flux:button>
    </div>

    {{-- Help Text --}}
    <flux:callout color="amber" icon="information-circle" class="mt-6">
        <flux:callout.heading>Prize Configuration Tips</flux:callout.heading>
        <flux:callout.text>
            <ul class="space-y-1">
                <li>• <strong>Cash prizes</strong> will be automatically distributed via invoice when contest finalization occurs</li>
                <li>• <strong>Other prizes</strong> require manual coordination between you and the winners</li>
                @if($project)
                    <li>• The project budget will be automatically updated to match your total cash prizes</li>
                    <li>• All prize configurations can be changed before the contest submission deadline</li>
                @else
                    <li>• Prize configuration will be saved when you complete the project creation</li>
                    <li>• The project budget will be automatically set to match your total cash prizes</li>
                @endif
                <li>• You can leave placements without prizes if desired (e.g., recognition only)</li>
            </ul>
        </flux:callout.text>
    </flux:callout>
</flux:card>
