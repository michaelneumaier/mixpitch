<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-6 py-3
    bg-accent hover:bg-accent-focus text-black font-medium rounded-lg transition-all transform hover:scale-105
    border-b-2 border-accent hover:border-accent-focus shadow-sm']) }}>
    {{ $slot }}
</button>