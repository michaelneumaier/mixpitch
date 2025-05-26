            </div>
            {{-- End Direct Hire Fields --}}

            {{-- Client Management Fields --}}
            <div x-show="workflowType === '{{ \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT }}'" class="mt-4 space-y-4">
                <div>
                    <x-label for="client_email" value="{{ __('Client Email') }}" />
                    <x-input id="client_email" type="email" class="mt-1 block w-full" wire:model="client_email" />
                    <x-input-error for="client_email" class="mt-2" />
                </div>
                <div>
                    <x-label for="client_name" value="{{ __('Client Name (Optional)') }}" />
                    <x-input id="client_name" type="text" class="mt-1 block w-full" wire:model="client_name" />
                    <x-input-error for="client_name" class="mt-2" />
                </div>

                {{-- Added: Payment Amount Input --}}
                <div>
                    <x-label for="payment_amount" value="{{ __('Client Payment Amount (USD)') }}" />
                    <x-input id="payment_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" wire:model="payment_amount" />
                    <x-input-error for="payment_amount" class="mt-2" />
                    <p class="mt-1 text-sm text-gray-500">{{ __('The amount the client will pay upon approving the final delivery. Enter 0 if no payment is required through the platform.') }}</p>
                </div>
            </div>
            {{-- End Client Management Fields --}}

        </div>
        {{-- End Conditional Fields --}}

        {{-- Existing fields like Title, Description etc. follow --}}
        <div class="mt-4">
            <x-label for="title" value="{{ __('Project Title') }}" /> 