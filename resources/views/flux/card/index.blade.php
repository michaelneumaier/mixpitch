@props([
    'size' => null,
])

@php
$classes = Flux::classes()
    ->add('bg-white/90 dark:bg-slate-800/90')
    ->add('backdrop-blur-sm')
    ->add('border border-slate-200/50 dark:border-slate-700/50')
    ->add('shadow-sm')
    ->add('transition-all duration-300')
    ->add(match ($size) {
        default => '[:where(&)]:p-6 [:where(&)]:rounded-2xl',
        'sm' => '[:where(&)]:p-4 [:where(&)]:rounded-xl',
    })
    ;
@endphp

<div {{ $attributes->class($classes) }} data-flux-card>
    {{ $slot }}
</div>
