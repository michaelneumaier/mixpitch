@if (($isPreview ?? false) === true)
    <flux:callout variant="warning" class="rounded-none border-x-0 border-t-0">
        <flux:icon.eye class="mr-2" />
        <span class="font-semibold">Preview Mode</span>
        <span class="mx-2">•</span>
        <span>This is how your client sees their portal</span>
    </flux:callout>
@endif

