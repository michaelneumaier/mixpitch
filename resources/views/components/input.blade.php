@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border border-gray-300 focus:border-primary
focus:ring focus:ring-primary/20 rounded-lg px-4 py-3 shadow-sm w-full transition-all']) !!}>