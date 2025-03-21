<div class="p-6 bg-white rounded-lg shadow-md">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Email Test Tool</h2>
    
    @if ($status === 'success')
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p>{{ $message }}</p>
        </div>
    @elseif ($status === 'error')
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p>{{ $message }}</p>
        </div>
    @endif
    
    <form wire:submit.prevent="sendTest">
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700">Recipient Email</label>
            <input type="email" id="email" wire:model.defer="email" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="email@example.com" required>
            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        
        <div class="mb-4">
            <label for="subject" class="block text-sm font-medium text-gray-700">Subject</label>
            <input type="text" id="subject" wire:model.defer="subject" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Test email subject" required>
            @error('subject') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        
        <div class="mb-4">
            <label for="template" class="block text-sm font-medium text-gray-700">Email Template</label>
            <select id="template" wire:model.defer="template" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                <option value="emails.test">Standard Test Template</option>
                <!-- Add more templates if available -->
            </select>
            @error('template') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Template Variables</label>
            
            <div class="bg-gray-50 p-3 rounded-md mb-2">
                @forelse ($variables as $key => $value)
                    <div class="flex items-center justify-between py-1">
                        <div class="flex-1">
                            <span class="font-medium">{{ $key }}:</span> {{ $value }}
                        </div>
                        <button type="button" wire:click="removeVariable('{{ $key }}')" class="text-red-500 hover:text-red-700 ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm italic">No variables added yet</p>
                @endforelse
            </div>
            
            <div class="flex space-x-2">
                <div class="flex-1">
                    <input type="text" wire:model.defer="variableKey" class="block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Variable name">
                </div>
                <div class="flex-1">
                    <input type="text" wire:model.defer="variableValue" class="block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Variable value">
                </div>
                <div>
                    <button type="button" wire:click="addVariable" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Add
                    </button>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Send Test Email
            </button>
        </div>
    </form>
</div>
