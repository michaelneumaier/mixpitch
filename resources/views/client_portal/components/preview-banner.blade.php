@if (($isPreview ?? false) === true)
    <flux:callout variant="warning" class="rounded-none border-x-0 border-t-0" icon="eye">
        <flux:callout.heading>
            Preview Mode
        </flux:callout.heading>
        <flux:callout.text>
            This is how your client sees their portal
        </flux:callout.text>
    </flux:callout>
@endif

