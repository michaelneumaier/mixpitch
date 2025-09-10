@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Title -->
    <div>
        <x-label for="title" value="{{ __('Package Title') }}" />
        <x-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title', $package->title ?? '')" required autofocus />
        <x-input-error for="title" class="mt-2" />
    </div>

    <!-- Price -->
    <div>
        <x-label for="price" value="{{ __('Price') }}" />
        <div class="flex items-center">
            <x-input id="price" class="block mt-1 w-full rounded-r-none" type="number" name="price" :value="old('price', $package->price ?? '')" required step="0.01" min="0" />
            <select id="currency" name="currency" class="block mt-1 rounded-l-none border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                {{-- TODO: Populate with available currencies --}}
                <option value="USD" {{ old('currency', $package->currency ?? 'USD') == 'USD' ? 'selected' : '' }}>USD</option>
                <option value="EUR" {{ old('currency', $package->currency ?? 'USD') == 'EUR' ? 'selected' : '' }}>EUR</option>
                <option value="GBP" {{ old('currency', $package->currency ?? 'USD') == 'GBP' ? 'selected' : '' }}>GBP</option>
            </select>
        </div>
        <x-input-error for="price" class="mt-2" />
        <x-input-error for="currency" class="mt-2" />
    </div>

    <!-- Description -->
    <div class="md:col-span-2">
        <x-label for="description" value="{{ __('Description') }}" />
        <x-textarea id="description" name="description" class="block mt-1 w-full">{{ old('description', $package->description ?? '') }}</x-textarea>
        <x-input-error for="description" class="mt-2" />
    </div>

    <!-- Deliverables -->
    <div class="md:col-span-2">
        <x-label for="deliverables" value="{{ __('Deliverables (What the client gets)') }}" />
        <x-textarea id="deliverables" name="deliverables" class="block mt-1 w-full">{{ old('deliverables', $package->deliverables ?? '') }}</x-textarea>
        <x-input-error for="deliverables" class="mt-2" />
    </div>

    <!-- Revisions Included -->
    <div>
        <x-label for="revisions_included" value="{{ __('Revisions Included') }}" />
        <x-input id="revisions_included" class="block mt-1 w-full" type="number" name="revisions_included" :value="old('revisions_included', $package->revisions_included ?? 0)" required min="0" />
        <x-input-error for="revisions_included" class="mt-2" />
    </div>

    <!-- Estimated Delivery Days -->
    <div>
        <x-label for="estimated_delivery_days" value="{{ __('Estimated Delivery (Days)') }}" />
        <x-input id="estimated_delivery_days" class="block mt-1 w-full" type="number" name="estimated_delivery_days" :value="old('estimated_delivery_days', $package->estimated_delivery_days ?? '')" min="1" />
        <x-input-error for="estimated_delivery_days" class="mt-2" />
    </div>

    <!-- Requirements Prompt -->
    <div class="md:col-span-2">
        <x-label for="requirements_prompt" value="{{ __('Requirements Prompt (Instructions for client after ordering)') }}" />
        <x-textarea id="requirements_prompt" name="requirements_prompt" class="block mt-1 w-full">{{ old('requirements_prompt', $package->requirements_prompt ?? '') }}</x-textarea>
        <x-input-error for="requirements_prompt" class="mt-2" />
    </div>

    <!-- Is Published -->
    <div class="md:col-span-2">
        <label for="is_published" class="flex items-center">
            <x-checkbox id="is_published" name="is_published" value="1" :checked="old('is_published', $package->is_published ?? false)" />
            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Publish this service package?') }}</span>
        </label>
         <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Published packages will be visible to potential clients.</p>
        <x-input-error for="is_published" class="mt-2" />
    </div>

</div>

<div class="flex items-center justify-end mt-8">
    <a href="{{ route('producer.services.packages.index') }}" wire:navigate class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800 mr-4">
        {{ __('Cancel') }}
    </a>

    <x-button>
        {{ isset($package) ? __('Update Package') : __('Create Package') }}
    </x-button>
</div> 