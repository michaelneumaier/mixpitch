@if ($errors->any())
<div {{ $attributes }}>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4 rounded-md">
        <div class="flex items-center">
            <svg class="h-5 w-5 text-red-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="font-medium text-red-700">{{ __('Whoops! Something went wrong.') }}</div>
        </div>

        <ul class="mt-3 list-disc list-inside text-sm text-red-700 ml-5">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif