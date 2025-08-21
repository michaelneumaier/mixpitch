<x-layouts.app-sidebar>
    {{-- Load the ManagePitch Livewire component, passing the Pitch object --}}
    {{-- The component's mount method will handle loading related data --}}
    <livewire:pitch.component.manage-pitch :pitch="$pitch" />
</x-layouts.app-sidebar> 