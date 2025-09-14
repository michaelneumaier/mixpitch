<div class="bg-gradient-to-br from-yellow-50 to-amber-50 border-2 border-amber-200 rounded-xl p-2 md:p-6 shadow-lg">
    <div class="flex flex-col md:flex-row items-center justify-between mb-6">
        <h3 class="text-2xl font-bold text-amber-800 flex items-center">
            <i class="fas fa-trophy text-amber-600 mr-3 text-3xl"></i>
            Contest Prize Configuration
        </h3>
        <div class="text-sm text-amber-700 bg-amber-100 px-3 py-1 rounded-full">
            @if($project)
                Edit prizes for this contest
            @else
                Configure prizes (will be saved when project is created)
            @endif
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Prize Configuration for Each Placement --}}
    <div class="space-y-2 md:space-y-6">
        @foreach(['1st', '2nd', '3rd', 'runner_up'] as $placement)
            <div class="bg-white border-2 border-gray-200 rounded-lg p-2 md:p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
                {{-- Placement Header --}}
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-xl font-semibold text-gray-800 flex items-center">
                        <span class="text-2xl mr-3">{{ $this->getPlacementEmoji($placement) }}</span>
                        {{ $this->getPlacementDisplayName($placement) }}
                    </h4>
                    <select wire:model.live="prizes.{{ $placement }}.type" 
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 px-4 py-2 min-w-[140px]">
                        <option value="none">No Prize</option>
                        <option value="cash">💰 Cash Prize</option>
                        <option value="other">🎁 Other Prize</option>
                    </select>
                </div>

                {{-- Prize Configuration Based on Type --}}
                @if($prizes[$placement]['type'] === 'cash')
                    <div class="bg-green-50 border border-green-200 rounded-lg p-2 md:p-4">
                        <h5 class="font-medium text-green-800 mb-3 flex items-center">
                            <i class="fas fa-dollar-sign mr-2"></i>
                            Cash Prize Configuration
                        </h5>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 md:gap-4">
                            <div>
                                <label class="block text-sm font-medium text-green-700 mb-2">Currency</label>
                                <select wire:model="prizes.{{ $placement }}.currency" 
                                        class="w-full bg-white border border-green-300 text-green-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 px-3 py-2">
                                    @foreach($availableCurrencies as $code => $label)
                                        <option value="{{ $code }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-green-700 mb-2">Amount</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-green-600 font-medium">
                                        {{ collect($availableCurrencies)->keys()->contains($prizes[$placement]['currency']) ? 
                                           (match($prizes[$placement]['currency']) {
                                               'EUR' => '€',
                                               'GBP' => '£', 
                                               'CAD' => 'C$',
                                               'AUD' => 'A$',
                                               default => '$'
                                           }) : '$' }}
                                    </span>
                                    <input type="number" 
                                           wire:model="prizes.{{ $placement }}.cash_amount"
                                           placeholder="0.00"
                                           step="0.01"
                                           min="0.01"
                                           max="999999.99"
                                           class="w-full pl-10 pr-3 py-2 bg-white border border-green-300 text-green-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500">
                                </div>
                                @error("prizes.{$placement}.cash_amount")
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                @elseif($prizes[$placement]['type'] === 'other')
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2 md:p-4">
                        <h5 class="font-medium text-blue-800 mb-3 flex items-center">
                            <i class="fas fa-gift mr-2"></i>
                            Other Prize Configuration
                        </h5>
                        
                        <div class="space-y-2 md:space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-blue-700 mb-2">Prize Title <span class="text-red-500">*</span></label>
                                <input type="text" 
                                       wire:model="prizes.{{ $placement }}.title"
                                       placeholder="e.g., Software License, T-shirt, Studio Time"
                                       maxlength="255"
                                       class="w-full px-3 py-2 bg-white border border-blue-300 text-blue-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                @error("prizes.{$placement}.title")
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-blue-700 mb-2">Description</label>
                                <textarea wire:model="prizes.{{ $placement }}.description"
                                          placeholder="Describe the prize details, how it will be delivered, etc."
                                          rows="3"
                                          maxlength="1000"
                                          class="w-full px-3 py-2 bg-white border border-blue-300 text-blue-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                                <p class="text-xs text-blue-600 mt-1">{{ strlen($prizes[$placement]['description'] ?? '') }}/1000 characters</p>
                                @error("prizes.{$placement}.description")
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-blue-700 mb-2">Estimated Value (Optional)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-blue-600 font-medium">$</span>
                                    <input type="number" 
                                           wire:model="prizes.{{ $placement }}.value_estimate"
                                           placeholder="0.00"
                                           step="0.01"
                                           min="0"
                                           max="999999.99"
                                           class="w-full pl-10 pr-3 py-2 bg-white border border-blue-300 text-blue-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <p class="text-xs text-blue-600 mt-1">Optional: For reference and analytics purposes</p>
                                @error("prizes.{$placement}.value_estimate")
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                @else
                    <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-2 md:p-8 text-center">
                        <i class="fas fa-trophy text-gray-400 text-4xl mb-3"></i>
                        <p class="text-gray-500 font-medium">No prize configured for this placement</p>
                        <p class="text-gray-400 text-sm">Select a prize type above to get started</p>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Prize Summary Section --}}
    <div class="mt-8 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-2 md:p-6">
        <h4 class="font-bold text-lg text-blue-800 mb-4 flex items-center">
            <i class="fas fa-chart-bar mr-2"></i>
            Prize Summary
        </h4>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 md:gap-4 mb-4">
            <div class="bg-white rounded-lg p-4 text-center border border-blue-200">
                <div class="text-2xl font-bold text-green-600">${{ number_format($this->getTotalCashPrizes(), 2) }}</div>
                <div class="text-sm text-green-700">Total Cash Prizes</div>
            </div>
            
            <div class="bg-white rounded-lg p-4 text-center border border-blue-200">
                <div class="text-2xl font-bold text-blue-600">${{ number_format($this->getTotalEstimatedValue(), 2) }}</div>
                <div class="text-sm text-blue-700">Total Estimated Value</div>
            </div>
            
            <div class="bg-white rounded-lg p-4 text-center border border-blue-200">
                @php $counts = $this->getPrizeCounts(); @endphp
                <div class="text-2xl font-bold text-purple-600">{{ $counts['total'] }}</div>
                <div class="text-sm text-purple-700">Total Prizes ({{ $counts['cash'] }} cash, {{ $counts['other'] }} other)</div>
            </div>
        </div>

        {{-- Preview of Configured Prizes --}}
        @if($counts['total'] > 0)
            <div class="bg-white rounded-lg p-2 md:p-4 border border-blue-200">
                <h5 class="font-medium text-blue-800 mb-3">Prize Breakdown:</h5>
                <div class="space-y-2">
                    @foreach(['1st', '2nd', '3rd', 'runner_up'] as $placement)
                        @if($prizes[$placement]['type'] !== 'none')
                            <div class="flex items-center justify-between py-2 px-3 bg-blue-50 rounded">
                                <span class="font-medium text-blue-800">
                                    {{ $this->getPlacementEmoji($placement) }} {{ $this->getPlacementDisplayName($placement) }}
                                </span>
                                <span class="text-blue-700">
                                    @if($prizes[$placement]['type'] === 'cash')
                                        {{ $prizes[$placement]['currency'] }} {{ number_format($prizes[$placement]['cash_amount'] ?? 0, 2) }}
                                    @else
                                        {{ $prizes[$placement]['title'] ?: 'Other Prize' }}
                                    @endif
                                </span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Action Buttons --}}
    <div class="flex justify-end space-x-2 md:space-x-4 mt-8">
        <button type="button" 
                wire:click="resetPrizes"
                class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors duration-200 flex items-center">
            <i class="fas fa-undo mr-2"></i>
            Reset
        </button>
        
        <button type="button" 
                wire:click="savePrizes"
                class="px-4 md:px-8 py-3 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg transition-colors duration-200 flex items-center shadow-lg">
            <i class="fas fa-save mr-2"></i>
            @if($project)
                Save Prize Configuration
            @else
                Configure Prizes
            @endif
        </button>
    </div>

    {{-- Help Text --}}
    <div class="mt-6 bg-amber-100 border border-amber-300 rounded-lg p-4">
        <h5 class="font-medium text-amber-800 mb-2 flex items-center">
            <i class="fas fa-info-circle mr-2"></i>
            Prize Configuration Tips
        </h5>
        <ul class="text-sm text-amber-700 space-y-1">
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
    </div>
</div>
