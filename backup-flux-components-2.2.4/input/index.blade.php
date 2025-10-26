@php $iconTrailing = $iconTrailing ??= $attributes->pluck('icon:trailing'); @endphp
@php $iconLeading = $iconLeading ??= $attributes->pluck('icon:leading'); @endphp
@php $iconVariant = $iconVariant ??= $attributes->pluck('icon:variant'); @endphp

@props([
    'name' => $attributes->whereStartsWith('wire:model')->first(),
    'iconVariant' => 'mini',
    'variant' => 'outline',
    'iconTrailing' => null,
    'iconLeading' => null,
    'expandable' => null,
    'clearable' => null,
    'copyable' => null,
    'viewable' => null,
    'invalid' => null,
    'loading' => null,
    'type' => 'text',
    'mask' => null,
    'size' => null,
    'icon' => null,
    'kbd' => null,
    'as' => null,
])

@php

// There are a few loading scenarios that this covers:
// If `:loading="false"` then never show loading.
// If `:loading="true"` then always show loading.
// If `:loading="foo"` then show loading when `foo` request is happening.
// If `wire:model` then never show loading.
// If `wire:model.live` then show loading when the `wire:model` value request is happening.
$wireModel = $attributes->wire('model');
$wireTarget = null;

if ($loading !== false) {
    if ($loading === true) {
        $loading = true;
    } elseif ($wireModel?->directive) {
        $loading = $wireModel->hasModifier('live');
        $wireTarget = $loading ? $wireModel->value() : null;
    } else {
        $wireTarget = $loading;
        $loading = (bool) $loading;
    }
}

$invalid ??= ($name && $errors->has($name));

$iconLeading ??= $icon;

$hasLeadingIcon = (bool) ($iconLeading);
$countOfTrailingIcons = collect([
    (bool) $iconTrailing,
    (bool) $kbd,
    (bool) $clearable,
    (bool) $copyable,
    (bool) $viewable,
    (bool) $expandable,
])->filter()->count();

$iconClasses = Flux::classes()
    // When using the outline icon variant, we need to size it down to match the default icon sizes...
    ->add($iconVariant === 'outline' ? 'size-5' : '')
    ;

$inputLoadingClasses = Flux::classes()
    // When loading, we need to add some extra padding to the input to account for the loading icon...
    ->add(match ($countOfTrailingIcons) {
        0 => 'pe-10',
        1 => 'pe-16',
        2 => 'pe-23',
        3 => 'pe-30',
        4 => 'pe-37',
        5 => 'pe-44',
        6 => 'pe-51',
    })
    ;

$classes = Flux::classes()
    ->add('w-full border rounded-xl block disabled:shadow-none dark:shadow-none')
    ->add('appearance-none') // Without this, input[type="date"] on mobile doesn't respect w-full...
    ->add('transition-all duration-200 ease-in-out')
    ->add('focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500')
    ->add(match ($size) {
        default => 'text-base sm:text-sm py-3 h-12 leading-[1.375rem]', // Increased height for better touch targets
        'sm' => 'text-sm py-2.5 h-10 leading-[1.125rem]',
        'xs' => 'text-xs py-2 h-8 leading-[1.125rem]',
    })
    ->add(match ($hasLeadingIcon) {
        true => 'ps-11',
        false => 'ps-4',
    })
    ->add(match ($countOfTrailingIcons) {
        // Make sure there's enough padding on the right side of the input to account for all the icons...
        0 => 'pe-4',
        1 => 'pe-11',
        2 => 'pe-18',
        3 => 'pe-25',
        4 => 'pe-32',
        5 => 'pe-39',
        6 => 'pe-46',
    })
    ->add(match ($variant) { // Background...
        'outline' => 'bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm dark:disabled:bg-slate-800/50',
        'filled'  => 'bg-slate-100/80 dark:bg-slate-700/80 backdrop-blur-sm dark:disabled:bg-slate-700/50',
    })
    ->add(match ($variant) { // Text color
        'outline' => 'text-slate-900 disabled:text-slate-500 placeholder-slate-500 disabled:placeholder-slate-400 dark:text-slate-100 dark:disabled:text-slate-400 dark:placeholder-slate-400 dark:disabled:placeholder-slate-500',
        'filled'  => 'text-slate-900 placeholder-slate-600 disabled:placeholder-slate-500 dark:text-slate-100 dark:placeholder-slate-300 dark:disabled:placeholder-slate-400',
    })
    ->add(match ($variant) { // Border...
        'outline' => $invalid ? 'border-red-500 focus:ring-red-500/20' : 'border-slate-300/60 dark:border-slate-600/60',
        'filled'  => $invalid ? 'border-red-500 focus:ring-red-500/20' : 'border-0',
    })
    ->add($attributes->pluck('class:input'))
    ;
@endphp

<?php if ($type === 'file'): ?>
    <flux:with-field :$attributes :$name>
        <flux:input.file :$attributes :$name :$size />
    </flux:with-field>
<?php elseif ($as !== 'button'): ?>
    <flux:with-field :$attributes :$name>
        <div {{ $attributes->only('class')->class('w-full relative block group/input') }} data-flux-input>
            <?php if (is_string($iconLeading)): ?>
                <div class="pointer-events-none absolute top-0 bottom-0 flex items-center justify-center text-xs text-zinc-400/75 ps-3 start-0">
                    <flux:icon :icon="$iconLeading" :variant="$iconVariant" :class="$iconClasses" />
                </div>
            <?php elseif ($iconLeading): ?>
                <div {{ $iconLeading->attributes->class('absolute top-0 bottom-0 flex items-center justify-center text-xs text-zinc-400/75 ps-3 start-0') }}>
                    {{ $iconLeading }}
                </div>
            <?php endif; ?>

            <input
                type="{{ $type }}"
                {{-- Leave file inputs unstyled... --}}
                {{ $attributes->except('class')->class($type === 'file' ? '' : $classes) }}
                @isset ($name) name="{{ $name }}" @endisset
                @if ($mask) x-mask="{{ $mask }}" @endif
                @if ($invalid) aria-invalid="true" data-invalid @endif
                @if (is_numeric($size)) size="{{ $size }}" @endif
                data-flux-control
                data-flux-group-target
                @if ($loading) wire:loading.class="{{ $inputLoadingClasses }}" @endif
                @if ($loading && $wireTarget) wire:target="{{ $wireTarget }}" @endif
            >

            <div class="absolute top-0 bottom-0 flex items-center gap-x-1.5 pe-3 end-0 text-xs text-zinc-400">
                {{-- Icon should be text-zinc-400/75 --}}
                <?php if ($loading): ?>
                    <flux:icon name="loading" :variant="$iconVariant" :class="$iconClasses" wire:loading :wire:target="$wireTarget" />
                <?php endif; ?>

                <?php if ($clearable): ?>
                    <flux:input.clearable inset="left right" :$size />
                <?php endif; ?>

                <?php if ($kbd): ?>
                    <span class="pointer-events-none">{{ $kbd }}</span>
                <?php endif; ?>

                <?php if ($expandable): ?>
                    <flux:input.expandable inset="left right" :$size />
                <?php endif; ?>

                <?php if ($copyable): ?>
                    <flux:input.copyable inset="left right" :$size />
                <?php endif; ?>

                <?php if ($viewable): ?>
                    <flux:input.viewable inset="left right" :$size />
                <?php endif; ?>

                <?php if (is_string($iconTrailing)): ?>
                    <?php
                        $trailingIconClasses = clone $iconClasses;
                        $trailingIconClasses->add('pointer-events-none text-zinc-400/75');
                    ?>
                    <flux:icon :icon="$iconTrailing" :variant="$iconVariant" :class="$trailingIconClasses" />
                <?php elseif ($iconTrailing): ?>
                    {{ $iconTrailing }}
                <?php endif; ?>
            </div>
        </div>
    </flux:with-field>
<?php else: ?>
    <button {{ $attributes->merge(['type' => 'button'])->class([$classes, 'w-full relative flex']) }}>
        <?php if (is_string($iconLeading)): ?>
            <div class="absolute top-0 bottom-0 flex items-center justify-center text-xs text-zinc-400/75 ps-3 start-0">
                <flux:icon :icon="$iconLeading" :variant="$iconVariant" :class="$iconClasses" />
            </div>
        <?php elseif ($iconLeading): ?>
            <div {{ $iconLeading->attributes->class('absolute top-0 bottom-0 flex items-center justify-center text-xs text-zinc-400/75 ps-3 start-0') }}>
                {{ $iconLeading }}
            </div>
        <?php endif; ?>

        <?php if ($attributes->has('placeholder')): ?>
            <div class="block self-center text-start flex-1 font-medium text-zinc-400 dark:text-white/40">
                {{ $attributes->get('placeholder') }}
            </div>
        <?php else: ?>
            <div class="text-start self-center flex-1 font-medium text-zinc-800 dark:text-white">
                {{ $slot }}
            </div>
        <?php endif; ?>

        <?php if ($kbd): ?>
            <div class="absolute top-0 bottom-0 flex items-center justify-center text-xs text-zinc-400/75 pe-4 end-0">
                {{ $kbd }}
            </div>
        <?php endif; ?>

        <?php if (is_string($iconTrailing)): ?>
            <div class="absolute top-0 bottom-0 flex items-center justify-center text-xs text-zinc-400/75 pe-3 end-0">
                <flux:icon :icon="$iconTrailing" :variant="$iconVariant" :class="$iconClasses" />
            </div>
        <?php elseif  ($iconTrailing): ?>
            <div {{ $iconTrailing->attributes->class('absolute top-0 bottom-0 flex items-center justify-center text-xs text-zinc-400/75 pe-2 end-0') }}>
                {{ $iconTrailing }}
            </div>
        <?php endif; ?>
    </button>
<?php endif; ?>
