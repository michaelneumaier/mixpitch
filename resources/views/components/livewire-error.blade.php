@props(['messages'])

@if ($messages)
    <p {{ $attributes->merge(['class' => 'text-sm text-red-600 dark:text-red-400']) }}>{{ is_array($messages) ? implode(', ', $messages) : $messages }}</p>
@endif 