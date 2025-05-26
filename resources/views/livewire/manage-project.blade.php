                </div>
            @endif

            {{-- Resend Client Invite Button --}}
            @if($project->isClientManagement() && $project->client_email)
                <div class="mt-4">
                    <x-secondary-button wire:click="resendClientInvite" wire:loading.attr="disabled">
                        {{ __('Resend Client Invite') }}
                    </x-secondary-button>
                    <span wire:loading wire:target="resendClientInvite" class="ml-2 text-sm text-gray-500 dark:text-gray-400">Sending...</span>
                </div>
            @endif

            {{-- Tab Navigation --}}
            <div class="mt-6">
                <div class="border-b border-gray-200 dark:border-gray-700"> 