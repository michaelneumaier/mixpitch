@pure

@php $iconTrailing = $iconTrailing ??= $attributes->pluck('icon:trailing'); @endphp
@php $iconVariant = $iconVariant ??= $attributes->pluck('icon:variant'); @endphp

@props([
    'iconVariant' => 'micro',
    'iconTrailing' => null,
    'variant' => null,
    'color' => null,
    'inset' => null,
    'size' => null,
    'icon' => null,
])

@php
$insetClasses = Flux::applyInset($inset, top: '-mt-1', right: '-me-2', bottom: '-mb-1', left: '-ms-2');

// When using the outline icon variant, we need to size it down to match the default icon sizes...
$iconClasses = Flux::classes()->add($iconVariant === 'outline' ? 'size-4' : '');

$classes = Flux::classes()
    ->add('inline-flex items-center font-semibold whitespace-nowrap')
    ->add($insetClasses)
    ->add('[print-color-adjust:exact]')
    ->add('shadow-sm')
    ->add(match ($size) {
        'lg' => 'text-sm py-1.5 **:data-flux-badge-icon:me-2',
        default => 'text-sm py-1 **:data-flux-badge-icon:me-1.5',
        'sm' => 'text-xs py-1 **:data-flux-badge-icon:size-3 **:data-flux-badge-icon:me-1',
    })
    ->add(match ($variant) {
        'pill' => 'rounded-full px-3',
        'success' => 'rounded-full px-3',
        'warning' => 'rounded-full px-3',
        'info' => 'rounded-full px-3',
        'neutral' => 'rounded-full px-3',
        'danger' => 'rounded-full px-3',
        'primary' => 'rounded-full px-3',
        'outline' => 'rounded-full px-3',
        default => 'rounded-full px-3',
    })
    /**
     * We can't compile classes for each color because of variants color to color and Tailwind's JIT compiler.
     * We instead need to write out each one by hand. Sorry...
     */
    ->add(match ($variant) {
        // Semantic variants with predefined colors
        'success' => 'text-green-800 dark:text-green-200 bg-green-400/20 dark:bg-green-400/40',
        'warning' => 'text-amber-800 dark:text-amber-200 bg-amber-400/25 dark:bg-amber-400/40',
        'info' => 'text-blue-800 dark:text-blue-200 bg-blue-400/20 dark:bg-blue-400/40',
        'neutral' => 'text-zinc-700 dark:text-zinc-200 bg-zinc-400/15 dark:bg-zinc-400/40',
        'danger' => 'text-red-700 dark:text-red-200 bg-red-400/20 dark:bg-red-400/40',
        'primary' => 'text-indigo-700 dark:text-indigo-200 bg-indigo-400/20 dark:bg-indigo-400/40',
        'outline' => 'text-zinc-700 dark:text-zinc-200 bg-transparent border border-zinc-300 dark:border-zinc-600',
        // Color-based variants
        'solid' => match ($color) {
            default => 'text-white dark:text-white bg-slate-600 dark:bg-slate-600',
            'red' => 'text-white dark:text-white bg-gradient-to-r from-red-500 to-pink-600',
            'orange' => 'text-white dark:text-white bg-gradient-to-r from-orange-500 to-red-500',
            'amber' => 'text-white dark:text-slate-950 bg-gradient-to-r from-amber-400 to-yellow-500',
            'yellow' => 'text-slate-950 dark:text-slate-950 bg-gradient-to-r from-yellow-400 to-amber-500',
            'lime' => 'text-slate-950 dark:text-slate-950 bg-gradient-to-r from-lime-400 to-green-500',
            'green' => 'text-white dark:text-white bg-gradient-to-r from-green-500 to-emerald-600',
            'emerald' => 'text-white dark:text-white bg-gradient-to-r from-emerald-500 to-teal-600',
            'teal' => 'text-white dark:text-white bg-gradient-to-r from-teal-500 to-cyan-600',
            'cyan' => 'text-white dark:text-white bg-gradient-to-r from-cyan-500 to-blue-600',
            'sky' => 'text-white dark:text-white bg-gradient-to-r from-sky-500 to-blue-600',
            'blue' => 'text-white dark:text-white bg-gradient-to-r from-blue-500 to-indigo-600',
            'indigo' => 'text-white dark:text-white bg-gradient-to-r from-indigo-500 to-purple-600',
            'violet' => 'text-white dark:text-white bg-gradient-to-r from-violet-500 to-purple-600',
            'purple' => 'text-white dark:text-white bg-gradient-to-r from-purple-500 to-indigo-600',
            'fuchsia' => 'text-white dark:text-white bg-gradient-to-r from-fuchsia-500 to-pink-600',
            'pink' => 'text-white dark:text-white bg-gradient-to-r from-pink-500 to-rose-600',
            'rose' => 'text-white dark:text-white bg-gradient-to-r from-rose-500 to-pink-600',
        },
        // Default case for when neither semantic nor solid variants are used
        default => match ($color) {
        default => 'text-zinc-700 [&_button]:text-zinc-700! dark:text-zinc-200 dark:[&_button]:text-zinc-200! bg-zinc-400/15 dark:bg-zinc-400/40 [&:is(button)]:hover:bg-zinc-400/25 dark:[button]:hover:bg-zinc-400/50',
        'red' => 'text-red-700 [&_button]:text-red-700! dark:text-red-200 dark:[&_button]:text-red-200! bg-red-400/20 dark:bg-red-400/40 [&:is(button)]:hover:bg-red-400/30 dark:[button]:hover:bg-red-400/50',
        'orange' => 'text-orange-700 [&_button]:text-orange-700! dark:text-orange-200 dark:[&_button]:text-orange-200! bg-orange-400/20 dark:bg-orange-400/40 [&:is(button)]:hover:bg-orange-400/30 dark:[button]:hover:bg-orange-400/50',
        'amber' => 'text-amber-700 [&_button]:text-amber-700! dark:text-amber-200 dark:[&_button]:text-amber-200! bg-amber-400/25 dark:bg-amber-400/40 [&:is(button)]:hover:bg-amber-400/40 dark:[button]:hover:bg-amber-400/50',
        'yellow' => 'text-yellow-800 [&_button]:text-yellow-800! dark:text-yellow-200 dark:[&_button]:text-yellow-200! bg-yellow-400/25 dark:bg-yellow-400/40 [&:is(button)]:hover:bg-yellow-400/40 dark:[button]:hover:bg-yellow-400/50',
        'lime' => 'text-lime-800 [&_button]:text-lime-800! dark:text-lime-200 dark:[&_button]:text-lime-200! bg-lime-400/25 dark:bg-lime-400/40 [&:is(button)]:hover:bg-lime-400/35 dark:[button]:hover:bg-lime-400/50',
        'green' => 'text-green-800 [&_button]:text-green-800! dark:text-green-200 dark:[&_button]:text-green-200! bg-green-400/20 dark:bg-green-400/40 [&:is(button)]:hover:bg-green-400/30 dark:[button]:hover:bg-green-400/50',
        'emerald' => 'text-emerald-800 [&_button]:text-emerald-800! dark:text-emerald-200 dark:[&_button]:text-emerald-200! bg-emerald-400/20 dark:bg-emerald-400/40 [&:is(button)]:hover:bg-emerald-400/30 dark:[button]:hover:bg-emerald-400/50',
        'teal' => 'text-teal-800 [&_button]:text-teal-800! dark:text-teal-200 dark:[&_button]:text-teal-200! bg-teal-400/20 dark:bg-teal-400/40 [&:is(button)]:hover:bg-teal-400/30 dark:[button]:hover:bg-teal-400/50',
        'cyan' => 'text-cyan-800 [&_button]:text-cyan-800! dark:text-cyan-200 dark:[&_button]:text-cyan-200! bg-cyan-400/20 dark:bg-cyan-400/40 [&:is(button)]:hover:bg-cyan-400/30 dark:[button]:hover:bg-cyan-400/50',
        'sky' => 'text-sky-800 [&_button]:text-sky-800! dark:text-sky-200 dark:[&_button]:text-sky-200! bg-sky-400/20 dark:bg-sky-400/40 [&:is(button)]:hover:bg-sky-400/30 dark:[button]:hover:bg-sky-400/50',
        'blue' => 'text-blue-800 [&_button]:text-blue-800! dark:text-blue-200 dark:[&_button]:text-blue-200! bg-blue-400/20 dark:bg-blue-400/40 [&:is(button)]:hover:bg-blue-400/30 dark:[button]:hover:bg-blue-400/50',
        'indigo' => 'text-indigo-700 [&_button]:text-indigo-700! dark:text-indigo-200 dark:[&_button]:text-indigo-200! bg-indigo-400/20 dark:bg-indigo-400/40 [&:is(button)]:hover:bg-indigo-400/30 dark:[button]:hover:bg-indigo-400/50',
        'violet' => 'text-violet-700 [&_button]:text-violet-700! dark:text-violet-200 dark:[&_button]:text-violet-200! bg-violet-400/20 dark:bg-violet-400/40 [&:is(button)]:hover:bg-violet-400/30 dark:[button]:hover:bg-violet-400/50',
        'purple' => 'text-purple-700 [&_button]:text-purple-700! dark:text-purple-200 dark:[&_button]:text-purple-200! bg-purple-400/20 dark:bg-purple-400/40 [&:is(button)]:hover:bg-purple-400/30 dark:[button]:hover:bg-purple-400/50',
        'fuchsia' => 'text-fuchsia-700 [&_button]:text-fuchsia-700! dark:text-fuchsia-200 dark:[&_button]:text-fuchsia-200! bg-fuchsia-400/20 dark:bg-fuchsia-400/40 [&:is(button)]:hover:bg-fuchsia-400/30 dark:[button]:hover:bg-fuchsia-400/50',
        'pink' => 'text-pink-700 [&_button]:text-pink-700! dark:text-pink-200 dark:[&_button]:text-pink-200! bg-pink-400/20 dark:bg-pink-400/40 [&:is(button)]:hover:bg-pink-400/30 dark:[button]:hover:bg-pink-400/50',
        'rose' => 'text-rose-700 [&_button]:text-rose-700! dark:text-rose-200 dark:[&_button]:text-rose-200! bg-rose-400/20 dark:bg-rose-400/40 [&:is(button)]:hover:bg-rose-400/30 dark:[button]:hover:bg-rose-400/50',
        },
    });
@endphp

<flux:button-or-div :attributes="$attributes->class($classes)" data-flux-badge>
    <?php if (is_string($icon) && $icon !== ''): ?>
        <flux:icon :$icon :variant="$iconVariant" :class="$iconClasses" data-flux-badge-icon />
    <?php else: ?>
        {{ $icon }}
    <?php endif; ?>

    {{ $slot }}

    <?php if ($iconTrailing): ?>
        <div class="ps-1 flex items-center" data-flux-badge-icon:trailing>
            <?php if (is_string($iconTrailing)): ?>
                <flux:icon :icon="$iconTrailing" :variant="$iconVariant" :class="$iconClasses" />
            <?php else: ?>
                {{ $iconTrailing }}
            <?php endif; ?>
        </div>
    <?php endif; ?>
</flux:button-or-div>
