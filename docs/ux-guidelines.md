# MixPitch UX Design Guidelines

**"Professional simplicity with creative warmth."**

## Tech Stack Integration

This document provides UX guidelines specifically tailored for our tech stack:
- **Tailwind CSS v4** for utility-first styling
- **Flux UI Pro** for pre-built components
- **Livewire v3** for reactive server-driven UI
- **Alpine.js v3** for lightweight client-side interactions
- **Filament v3** for admin interfaces

### Important: Tailwind v4 Changes
We're using Tailwind CSS v4. Do NOT use these deprecated utilities:
- ❌ `bg-opacity-*` → ✅ `bg-black/50`
- ❌ `text-opacity-*` → ✅ `text-white/80`
- ❌ `flex-grow-*` → ✅ `grow-*`
- ❌ `flex-shrink-*` → ✅ `shrink-*`

---

## 1. Core UX Principles (The Non-Negotiables)

| Principle | Do ✅ | Don't ❌ |
|-----------|--------|----------|
| **Clarity First** | Prioritize a single primary action per screen (e.g., Start Project, Pitch Now). | Don't clutter screens with multiple competing CTAs or technical jargon. |
| **Minimal Cognitive Load** | Use familiar patterns, clean layouts, and progressive disclosure for advanced features. | Don't force users to configure everything upfront — defer complexity until needed. |
| **Trust Through Work** | Design experiences where audio itself builds credibility — A/B players, waveform previews, winning showcases. | Don't overwhelm users with bios, reviews, and stats before they hear the work. |
| **Creative-Friendly Warmth** | Use soft edges, open whitespace, playful microinteractions, and expressive accents. | Don't default to sterile "enterprise" vibes that feel cold or overly rigid. |
| **Device-Aware Flows** | Mobile flows prioritize speed and defer file-heavy steps; desktop flows embrace drag-drop, multitasking, and batch actions. | Don't mirror the same UI across devices without considering context. |
| **Consistency Everywhere** | Use one visual language for colors, typography, icons, spacing, and motion. | Don't introduce inconsistent button styles, multiple shades of the same accent, or mixed icon packs. |
| **Feedback Everywhere** | Provide immediate, clear feedback on actions (uploads, shortlists, messages). | Don't leave users guessing about system state or progress. |

---

## 2. Layout & Structure Guidelines

| Aspect | Do ✅ | Don't ❌ |
|--------|--------|----------|
| **Spacing & Breathing Room** | Use Tailwind's spacing scale: `p-2` (8px), `p-4` (16px), `p-6` (24px). Stack sections with `space-y-4` or `space-y-6`. | Don't use arbitrary spacing values. Stick to Tailwind's 4px/rem scale. |
| **Content Hierarchy** | Use Tailwind's type scale: `text-3xl font-bold` → `text-xl font-semibold` → `text-base`. Apply `text-slate-600` for secondary text. | Don't make every font size similar; maintain clear visual hierarchy. |
| **Progressive Disclosure** | Use Alpine.js: `x-show="expanded"` with `x-collapse` plugin. Flux UI accordions for settings. | Don't show all options at once. Use `<flux:accordion>` components. |
| **Sticky Actions** | Apply `sticky bottom-0` or `fixed bottom-4 right-4` for mobile CTAs. Use `lg:static` for desktop layouts. | Don't bury primary actions. Keep them visible with `z-10` layering. |
| **Single-Column Default** | Use `max-w-2xl mx-auto` for content. Apply `lg:grid lg:grid-cols-3` only on larger screens. | Don't force multi-column on mobile. Start with `flex flex-col`. |
| **Container Widths** | Use `container mx-auto px-4 sm:px-6 lg:px-8` for consistent edge spacing across breakpoints. | Don't use full-width layouts without padding on mobile. |

---

## 3. Visual Design Language

| Aspect | Do ✅ | Don't ❌ |
|--------|--------|----------|
| **Colors** | Use Tailwind's color palette: `slate` for neutrals, `indigo-600` primary, `pink-500`/`orange-500` accents. Configure in `tailwind.config.js`. | Don't use arbitrary color values outside the design system. |
| **Typography** | Use Inter (already configured). Apply Tailwind classes: `text-sm`, `text-base`, `text-xl` for consistency. | Don't use custom font sizes. Stick to Tailwind's type scale. |
| **Components** | Use Flux UI components: `<flux:button>`, `<flux:card>`, `<flux:modal>` for consistency. | Don't recreate components that Flux UI already provides. |
| **Iconography** | Use Flux UI's `<flux:icon>` component with consistent sizing (`size="sm"`, `size="base"`). | Don't mix icon libraries or use raw SVGs without the icon component. |
| **Rounded Surfaces** | Use Tailwind's rounded utilities: `rounded-lg` for cards, `rounded-md` for inputs, `rounded-full` for avatars. | Don't use arbitrary radius values. Stick to Tailwind's scale. |
| **Dark Mode** | Use Tailwind's `dark:` variant. Background: `bg-slate-50 dark:bg-slate-900`. Text: `text-slate-900 dark:text-slate-100`. | Don't hardcode colors. Always provide both light and dark variants. |
| **Shadows** | Use Tailwind's shadow utilities: `shadow-sm` for subtle, `shadow-lg` for emphasis, `shadow-indigo-500/10` for colored shadows. | Don't use CSS box-shadow directly. |

---

## 4. Interaction & Motion Guidelines

| Element | Do ✅ | Don't ❌ |
|---------|--------|----------|
| **Uploads** | Use Livewire file uploads with `wire:model`. Show progress with `wire:loading` and Uppy.js for large files. | Don't block the UI. Use `wire:loading.class="opacity-50"` for visual feedback. |
| **Players** | Create custom Livewire audio player components. Use Alpine.js for client-side playback controls. | Don't reload the entire page for audio controls. |
| **Microinteractions** | Use Alpine transitions: `x-transition:enter-duration.200ms`. Combine with Tailwind's `transition-all duration-200`. | Don't use jQuery or vanilla JS animations when Alpine provides the same. |
| **Loading States** | Use `wire:loading` with Flux UI skeletons. Apply `wire:target` for specific actions. | Don't use generic spinners. Show contextual loading states. |
| **Real-time Updates** | Use Livewire's `wire:poll` for live data. Implement `$refresh` for user-triggered updates. | Don't use AJAX polling. Let Livewire handle server communication. |
| **Empty States** | Use Flux UI's empty state patterns. Include primary action with `<flux:button variant="primary">`. | Don't leave empty divs. Always provide next steps. |

---

## 5. Content & Microcopy Guidelines

| Context | Do ✅ | Don't ❌ |
|---------|--------|----------|
| **Tone** | Professional but human — warm, approachable, clear. | Don't sound overly technical or "corporate." |
| **Labels** | Short, action-driven labels ("Start Project", "Pitch Now", "Join Contest"). | Don't use vague verbs like "Submit" or "Go." |
| **Errors** | Be empathetic and actionable ("Upload failed — retry or send via Drive?"). | Don't use cryptic, technical error codes. |
| **Guidance** | Prefer tooltips, short inline helpers, or modals when necessary. | Don't explain everything upfront or clutter the interface with instructions. |

---

## 6. Accessibility Guidelines

- **Color contrast**: Always AA-compliant.
- **Keyboard-first**: Navigable forms, modal focus traps, player shortcuts.
- **Tap targets**: Minimum 44px for mobile controls.
- **Audio preview player**: Enable volume-independent waveform visualization.
- **Reduced motion**: Honor OS-level preferences to disable transitions.

---

## 7. Cross-Platform Behavior

- **Desktop-first power tools**: Drag-drop uploading, waveform comparisons, batch pitch management, multi-column layouts.
- **Mobile-first speed**: Simplified Fast Brief flow, sticky mini-player, swipeable pitches, floating "Start Project" button.
- **Sync everywhere**: Sessions persist across devices — start on mobile, finish on desktop.

---

## 8. Component Implementation Patterns

### Form Components
```blade
{{-- Use Flux UI form components with Livewire bindings --}}
<flux:field>
    <flux:label>Project Title</flux:label>
    <flux:input wire:model.live="title" placeholder="Enter your project name" />
    <flux:error name="title" />
</flux:field>
```

### Loading States
```blade
{{-- Contextual loading with wire:loading --}}
<flux:button wire:click="save" wire:loading.attr="disabled">
    <span wire:loading.remove>Save Project</span>
    <span wire:loading>Saving...</span>
</flux:button>
```

### Audio Player Pattern
```blade
{{-- Combine Livewire + Alpine for audio --}}
<div x-data="audioPlayer(@entangle('currentTrack'))">
    <flux:button @click="togglePlay" size="sm">
        <flux:icon name="play" x-show="!playing" />
        <flux:icon name="pause" x-show="playing" />
    </flux:button>
</div>
```

### Responsive Utilities
```blade
{{-- Mobile-first responsive design --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <flux:card class="p-4 md:p-6">
        <!-- Card content -->
    </flux:card>
</div>
```

---

## 9. Filament Admin Guidelines

When building admin interfaces with Filament:
- Follow Filament's design system for consistency
- Use Filament's built-in components and actions
- Maintain the same color scheme in custom admin pages
- Leverage Filament's table, form, and widget patterns
- Use resource classes for CRUD operations

---

## 10. Performance Considerations

### Livewire Optimization
- Use `wire:model.defer` by default, `wire:model.live` only when needed
- Implement `wire:loading` states for all server actions
- Use `wire:poll.keep-alive` for background updates
- Batch multiple updates with `$this->skipRender()`

### Asset Loading
- Lazy load images with `loading="lazy"`
- Use Cloudflare R2 for audio file CDN delivery
- Implement progressive audio loading for waveforms
- Defer non-critical JavaScript with Alpine's `x-init`

---

## 11. Global Do's & Don'ts Cheat Sheet

### Do:
- Optimize for creators and engineers equally.
- Make audio visible (waveforms, meters, heatmaps).
- Defer complexity until users ask for it.
- Celebrate creativity with showcases, contests, and pitch galleries.
- Keep the interface consistent everywhere.

### Don't:
- Overload onboarding forms or force file uploads early.
- Hide critical information behind unexplained icons.
- Use flashy gradients everywhere — accents, not chaos.
- Treat mobile as an afterthought.
- Let silence = confusion — always provide feedback.