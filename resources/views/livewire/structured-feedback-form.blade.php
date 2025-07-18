<div class="structured-feedback-form">
    @if($showTemplateSelector)
        {{-- Template Selection --}}
        <div class="template-selector">
            <h3 class="text-lg font-semibold mb-4">Choose a Feedback Template</h3>
            
            @if(empty($availableTemplates))
                <div class="text-center py-8 text-gray-500">
                    <p>No feedback templates are available at this time.</p>
                </div>
            @else
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($availableTemplates as $templateOption)
                        <div class="template-card border rounded-lg p-4 hover:border-blue-500 cursor-pointer transition-colors"
                             wire:click="selectTemplate({{ $templateOption['id'] }})">
                            <h4 class="font-semibold text-gray-900">{{ $templateOption['name'] }}</h4>
                            
                            @if($templateOption['description'])
                                <p class="text-sm text-gray-600 mt-1">{{ $templateOption['description'] }}</p>
                            @endif
                            
                            <div class="flex items-center justify-between mt-3 text-xs text-gray-500">
                                <span class="bg-gray-100 px-2 py-1 rounded">{{ $templateOption['category'] }}</span>
                                <span>{{ $templateOption['question_count'] }} questions</span>
                            </div>
                            
                            @if(!$isClientUser && isset($templateOption['is_default']) && $templateOption['is_default'])
                                <div class="mt-2">
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">System Template</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        {{-- Feedback Form --}}
        <div class="feedback-form">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold">{{ $template->name }}</h3>
                    @if($template->description)
                        <p class="text-sm text-gray-600">{{ $template->description }}</p>
                    @endif
                </div>
                <button type="button" 
                        class="text-blue-600 hover:text-blue-800 text-sm"
                        wire:click="backToTemplateSelector">
                    ← Change Template
                </button>
            </div>

            {{-- General Error --}}
            @if(isset($validationErrors['general']))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    {{ $validationErrors['general'] }}
                </div>
            @endif

            {{-- Questions --}}
            <form wire:submit.prevent="submitFeedback" class="space-y-6">
                @foreach($template->questions ?? [] as $question)
                    <div class="question-container">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ $question['label'] }}
                            @if($question['required'] ?? false)
                                <span class="text-red-500">*</span>
                            @endif
                        </label>

                        @if($question['help_text'] ?? '')
                            <p class="text-xs text-gray-500 mb-2">{{ $question['help_text'] }}</p>
                        @endif

                        {{-- Question Input Based on Type --}}
                        @if($question['type'] === App\Models\FeedbackTemplate::TYPE_TEXT)
                            <input type="text" 
                                   wire:model="responses.{{ $question['id'] }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('responses.' . $question['id']) border-red-500 @enderror">

                        @elseif($question['type'] === App\Models\FeedbackTemplate::TYPE_TEXTAREA)
                            <textarea wire:model="responses.{{ $question['id'] }}"
                                      rows="{{ $question['rows'] ?? 4 }}"
                                      class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('responses.' . $question['id']) border-red-500 @enderror"></textarea>

                        @elseif($question['type'] === App\Models\FeedbackTemplate::TYPE_SELECT)
                            <select wire:model="responses.{{ $question['id'] }}"
                                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('responses.' . $question['id']) border-red-500 @enderror">
                                <option value="">Choose an option...</option>
                                @foreach($question['options'] ?? [] as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>

                        @elseif($question['type'] === App\Models\FeedbackTemplate::TYPE_RADIO)
                            <div class="space-y-2">
                                @foreach($question['options'] ?? [] as $option)
                                    <label class="flex items-center">
                                        <input type="radio" 
                                               wire:model="responses.{{ $question['id'] }}"
                                               value="{{ $option }}"
                                               class="mr-2">
                                        <span class="text-sm">{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>

                        @elseif($question['type'] === App\Models\FeedbackTemplate::TYPE_CHECKBOX)
                            <div class="space-y-2">
                                @foreach($question['options'] ?? [] as $option)
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               wire:model="responses.{{ $question['id'] }}"
                                               value="{{ $option }}"
                                               class="mr-2">
                                        <span class="text-sm">{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>

                        @elseif($question['type'] === App\Models\FeedbackTemplate::TYPE_RATING)
                            <div class="star-rating flex items-center space-x-1">
                                @for($i = 1; $i <= ($question['max_rating'] ?? 5); $i++)
                                    <button type="button"
                                            wire:click="$set('responses.{{ $question['id'] }}', {{ $i }})"
                                            class="text-2xl focus:outline-none transition-colors {{ (($responses[$question['id']] ?? 0) >= $i) ? 'text-yellow-400' : 'text-gray-300' }}">
                                        ★
                                    </button>
                                @endfor
                                @if(($responses[$question['id']] ?? 0) > 0)
                                    <span class="ml-2 text-sm text-gray-600">
                                        {{ $responses[$question['id']] ?? 0 }}/{{ $question['max_rating'] ?? 5 }}
                                    </span>
                                @endif
                            </div>

                        @elseif($question['type'] === App\Models\FeedbackTemplate::TYPE_RANGE)
                            <div class="range-input">
                                <input type="range"
                                       wire:model="responses.{{ $question['id'] }}"
                                       min="{{ $question['min'] ?? 0 }}"
                                       max="{{ $question['max'] ?? 100 }}"
                                       step="{{ $question['step'] ?? 1 }}"
                                       class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>{{ $question['min'] ?? 0 }}</span>
                                    <span class="font-semibold">{{ $responses[$question['id']] ?? ($question['min'] ?? 0) }}</span>
                                    <span>{{ $question['max'] ?? 100 }}</span>
                                </div>
                            </div>
                        @endif

                        {{-- Question-specific Error --}}
                        @if(isset($validationErrors[$question['id']]))
                            <p class="text-red-500 text-xs mt-1">{{ $validationErrors[$question['id']] }}</p>
                        @endif
                    </div>
                @endforeach

                {{-- Submit Button --}}
                <div class="flex justify-end pt-4 border-t">
                    <button type="submit" 
                            @disabled($isSubmitting)
                            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        @if($isSubmitting)
                            <span class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Submitting...
                            </span>
                        @else
                            Submit Feedback
                        @endif
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('feedbackSubmitted', (event) => {
            // Show success notification
            if (window.showNotification) {
                window.showNotification(event.message, 'success');
            } else {
                alert(event.message);
            }
        });
    });
</script>