@props(['currentStep' => 1, 'totalSteps' => 4, 'steps' => []])

@php
    $progressPercentage = ($currentStep / $totalSteps) * 100;
@endphp

<div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200 p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-plus-circle text-blue-600 mr-3"></i>
                Create New Project
            </h3>
            <div class="text-right">
                <div class="text-2xl font-bold text-blue-600">{{ round($progressPercentage) }}%</div>
                <div class="text-xs text-gray-500">Complete</div>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2 rounded-full transition-all duration-500"
                 style="width: {{ $progressPercentage }}%"></div>
        </div>
        
        <!-- Step Indicators -->
        <div class="flex justify-between text-xs">
            @foreach($steps as $index => $step)
            @php
                $stepNumber = $index + 1;
                $isActive = $stepNumber === $currentStep;
                $isCompleted = $stepNumber < $currentStep;
                $isAccessible = $stepNumber <= $currentStep;
            @endphp
            <div class="flex flex-col items-center {{ $isActive ? 'text-blue-600' : ($isCompleted ? 'text-green-600' : 'text-gray-400') }}">
                <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center mb-1 transition-all duration-300
                    {{ $isCompleted ? 'bg-green-600 border-green-600 text-white' : 
                       ($isActive ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 bg-white') }}">
                    @if($isCompleted)
                        <i class="fas fa-check text-xs"></i>
                    @else
                        {{ $stepNumber }}
                    @endif
                </div>
                <span class="text-center leading-tight max-w-16 break-words">{{ $step['label'] }}</span>
                @if(isset($step['description']))
                <span class="text-center leading-tight text-gray-500 mt-1 max-w-20 break-words">{{ $step['description'] }}</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div> 