<x-filament-panels::page>
    <x-filament::section>
        <form wire:submit="submit" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end mt-6">
                <x-filament::button type="submit" class="bg-primary-600 hover:bg-primary-700">
                    Save Settings
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page> 