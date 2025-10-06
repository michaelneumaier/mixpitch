<div>
    <flux:modal name="client-selection" class="max-w-lg">
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <flux:icon.folder-plus class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                <flux:heading size="lg">Create Client Management Project</flux:heading>
            </div>

            <flux:subheading class="text-gray-600 dark:text-gray-400">
                Choose an existing client or start fresh for a new client.
            </flux:subheading>

            <div class="space-y-4">
                <!-- Start Fresh Option -->
                <flux:button
                    wire:click="createProjectWithoutClient"
                    variant="primary"
                    class="w-full justify-start"
                    icon="plus">
                    <div class="flex items-center gap-3">
                        <div class="flex-1 text-left">
                            <div class="font-semibold">Start Fresh</div>
                            <div class="text-xs text-white/80">Create a project for a new client</div>
                        </div>
                    </div>
                </flux:button>

                <flux:separator />

                <!-- Existing Clients Section -->
                @if(count($clients) > 0)
                    <div>
                        <flux:field>
                            <flux:label>Or choose an existing client</flux:label>
                            <flux:select
                                variant="listbox"
                                wire:model.live="selectedClientId"
                                placeholder="Select an existing client"
                                searchable>
                                @foreach($clients as $client)
                                    <flux:select.option value="{{ $client['id'] }}">{{ $client['label'] }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    </div>
                @else
                    <div class="text-center py-6">
                        <flux:icon.users class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-3" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">No existing clients</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Click "Start Fresh" to create your first project</p>
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
