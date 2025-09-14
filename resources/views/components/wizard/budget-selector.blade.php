@props(['budgetType' => 'free', 'budget' => 0, 'workflowType' => 'standard'])

<div class="space-y-6">
    <!-- Budget Type Selection -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-4">
            Project Budget <span class="text-red-500">*</span>
        </label>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Free Project Card -->
            <div wire:click="$set('form.budgetType', 'free')"
                 class="relative cursor-pointer rounded-lg border-2 p-4 transition-all duration-200 hover:shadow-md
                 {{ $budgetType === 'free' ? 'border-blue-500 bg-blue-50 shadow-md' : 'border-gray-200 hover:border-blue-300' }}">
                
                <div class="flex items-start space-x-3">
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-heart text-green-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-base font-semibold text-gray-900 mb-2">Free Collaboration</h4>
                        <p class="text-sm text-gray-600 mb-3 leading-relaxed">
                            Perfect for building relationships, gaining experience, or passion projects. Great for new artists and producers looking to collaborate.
                        </p>
                        
                        <div class="space-y-1">
                            <p class="text-xs font-medium text-green-700 mb-1">Benefits:</p>
                            <ul class="text-xs text-gray-600 space-y-1">
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-2 text-xs"></i>
                                    No upfront costs
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-2 text-xs"></i>
                                    Build your network
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-2 text-xs"></i>
                                    Creative freedom
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Selection Indicator -->
                @if($budgetType === 'free')
                <div class="mt-3 pt-3 border-t border-blue-200">
                    <div class="flex items-center text-blue-600">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span class="text-sm font-medium">Selected</span>
                    </div>
                </div>
                @endif
            </div>

            <!-- Paid Project Card -->
            <div wire:click="$set('form.budgetType', 'paid')"
                 class="relative cursor-pointer rounded-lg border-2 p-4 transition-all duration-200 hover:shadow-md
                 {{ $budgetType === 'paid' ? 'border-blue-500 bg-blue-50 shadow-md' : 'border-gray-200 hover:border-blue-300' }}">
                
                <div class="flex items-start space-x-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-dollar-sign text-blue-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-base font-semibold text-gray-900 mb-2">Paid Project</h4>
                        <p class="text-sm text-gray-600 mb-3 leading-relaxed">
                            Invest in professional quality work. Set a budget to attract experienced producers and ensure dedicated attention to your project.
                        </p>
                        
                        <div class="space-y-1">
                            <p class="text-xs font-medium text-blue-700 mb-1">Benefits:</p>
                            <ul class="text-xs text-gray-600 space-y-1">
                                <li class="flex items-center">
                                    <i class="fas fa-check text-blue-500 mr-2 text-xs"></i>
                                    Priority attention
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-blue-500 mr-2 text-xs"></i>
                                    Professional quality
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-blue-500 mr-2 text-xs"></i>
                                    Faster turnaround
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Selection Indicator -->
                @if($budgetType === 'paid')
                <div class="mt-3 pt-3 border-t border-blue-200">
                    <div class="flex items-center text-blue-600">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span class="text-sm font-medium">Selected</span>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Budget Amount Input (shown when paid is selected) -->
    @if($budgetType === 'paid')
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <label for="budget_amount" class="block text-sm font-medium text-blue-800 mb-3">
            <i class="fas fa-dollar-sign text-blue-600 mr-2"></i>
            Set Your Budget Amount <span class="text-red-500">*</span>
        </label>
        
        <div class="space-y-4">
            <!-- Budget Input -->
            <div class="relative">
                <span class="absolute left-3 top-3 text-gray-500 text-lg">$</span>
                <input type="number" id="budget_amount" wire:model.blur="form.budget" 
                       class="w-full pl-10 pr-3 py-3 text-lg border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                       placeholder="0.00" min="0">
            </div>
            
            <!-- Budget Guidelines -->
            <div class="bg-white rounded-lg p-3 border border-blue-200">
                <h5 class="text-sm font-medium text-blue-800 mb-2">💡 Budget Guidelines</h5>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div class="text-center p-2 bg-blue-50 rounded">
                        <div class="font-semibold text-blue-700">$50 - $200</div>
                        <div class="text-blue-600">Basic mixing/mastering</div>
                    </div>
                    <div class="text-center p-2 bg-blue-50 rounded">
                        <div class="font-semibold text-blue-700">$200 - $500</div>
                        <div class="text-blue-600">Professional production</div>
                    </div>
                    <div class="text-center p-2 bg-blue-50 rounded">
                        <div class="font-semibold text-blue-700">$500+</div>
                        <div class="text-blue-600">Premium/complex projects</div>
                    </div>
                </div>
            </div>
            
            @error('form.budget')
            <p class="mt-1 text-sm text-red-600 flex items-center">
                <i class="fas fa-exclamation-circle mr-1"></i>
                {{ $message }}
            </p>
            @enderror
        </div>
    </div>
    @endif
</div> 