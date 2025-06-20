does # MixPitch Design System & Style Guide

## Overview

This document outlines the comprehensive design system implemented across the MixPitch platform, establishing consistent visual patterns, component structures, and user experience guidelines.

## Table of Contents

1. [Color Palette & Gradients](#color-palette--gradients)
2. [Typography](#typography)
3. [Layout Patterns](#layout-patterns)
4. [Component Library](#component-library)
5. [Interactive Elements](#interactive-elements)
6. [Background Effects](#background-effects)
7. [Animation & Transitions](#animation--transitions)
8. [Responsive Design](#responsive-design)
9. [Page-Specific Patterns](#page-specific-patterns)
10. [Status & Badge Components](#status--badge-components)
11. [Dropdown & Modal Patterns](#dropdown--modal-patterns)
12. [Pitch Creation & Management](#pitch-creation--management)
13. [Modal Components](#modal-components)
14. [Notification Components](#notification-components)
15. [Billing & Payment Components](#billing--payment-components)
16. [Pitch List Components](#pitch-list-components)

---

## Color Palette & Gradients

### Primary Color Scheme
- **Blue**: `#2563eb` (blue-600) to `#1d4ed8` (blue-700)
- **Purple**: `#7c3aed` (purple-600) to `#6d28d9` (purple-700)
- **Pink**: `#ec4899` (pink-500) to `#db2777` (pink-600)

### Gradient Patterns
```css
/* Primary Gradient */
bg-gradient-to-r from-blue-600 to-purple-600
hover:from-blue-700 hover:to-purple-700

/* Secondary Gradients */
bg-gradient-to-r from-purple-500 to-pink-500
bg-gradient-to-r from-purple-600 to-pink-600
bg-gradient-to-r from-indigo-500 to-blue-500

/* Logout/Danger Gradient */
bg-gradient-to-r from-red-500 to-pink-500
hover:from-red-600 hover:to-pink-600

/* Background Gradients */
bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50
bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5
```

### Semantic Colors
- **Success**: `#10b981` (green-500)
- **Warning**: `#f59e0b` (amber-500)
- **Error**: `#ef4444` (red-500)
- **Info**: `#3b82f6` (blue-500)

### Status Colors
- **Unpublished**: `bg-gray-100 text-gray-800`
- **Open**: `bg-green-100 text-green-800`
- **Review**: `bg-blue-100 text-blue-800`
- **Completed**: `bg-purple-100 text-purple-800`
- **Closed**: `bg-red-100 text-red-800`

### Neutral Palette
- **Gray Scale**: `#f9fafb` (gray-50) to `#111827` (gray-900)
- **White Variants**: `bg-white/95`, `bg-white/10`, `bg-white/20`, `bg-white/50`, `bg-white/60`

---

## Typography

### Font Family
- **Primary**: Inter (Google Fonts)
- **Fallback**: system fonts (`font-sans`)

### Heading Hierarchy
```css
/* Hero Headings */
text-4xl sm:text-6xl lg:text-8xl font-bold

/* Page Titles */
text-2xl font-bold text-gray-900

/* Section Headings */
text-lg font-semibold

/* Card Titles */
text-base font-semibold text-gray-900

/* Dropdown Headers */
text-lg font-semibold text-gray-900 mb-4
```

### Text Styles
- **Body Text**: `text-gray-600`, `text-gray-700`
- **Muted Text**: `text-gray-500`, `text-white/80`
- **Small Text**: `text-xs`, `text-sm`
- **Links**: `text-blue-600 hover:text-blue-500`
- **Menu Items**: `text-sm font-medium text-gray-700 hover:text-gray-900`

---

## Layout Patterns

### Container Structure
```html
<!-- Standard Container -->
<div class="container mx-auto px-2 sm:px-4">

<!-- Max Width Container -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

<!-- Centered Content -->
<div class="max-w-2xl mx-auto">
```

### Grid Systems
```html
<!-- Two Column Layout -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2"><!-- Main Content --></div>
  <div><!-- Sidebar --></div>
</div>

<!-- Card Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
```

### Spacing System
- **Section Spacing**: `space-y-6`
- **Component Spacing**: `space-y-4`
- **Element Spacing**: `space-y-2`
- **Padding**: `p-4`, `p-6`, `p-8`

---

## Component Library

### Card Components

#### Standard Card
```html
<div class="bg-white rounded-lg border border-base-300 shadow-sm p-4">
  <!-- Content -->
</div>
```

#### Enhanced Card (Client Management Style)
```html
<div class="bg-white/95 backdrop-blur-md shadow-xl border border-white/20 rounded-2xl p-8">
  <!-- Content -->
</div>
```

#### Glass Morphism Card
```html
<div class="bg-white/95 backdrop-blur-md rounded-xl shadow-xl border border-white/20">
  <!-- Background Effects -->
  <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5 rounded-xl"></div>
  <div class="relative z-10 p-6">
    <!-- Content -->
  </div>
</div>
```

#### Colored Cards
```html
<!-- Purple Theme -->
<div class="bg-purple-50 border border-purple-200 rounded-lg p-4">

<!-- Blue Theme -->
<div class="bg-blue-50 border border-blue-200 rounded-xl p-4">

<!-- Amber Theme -->
<div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
```

### Button Components

#### Primary Button
```html
<button class="group relative overflow-hidden bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-4 px-8 rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-2xl">
  <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
  <span class="relative">Button Text</span>
</button>
```

#### Secondary Button
```html
<button class="group relative overflow-hidden bg-white/10 backdrop-blur-md border border-white/20 hover:bg-white/20 text-white font-bold py-4 px-8 rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-2xl">
  <span class="relative">Button Text</span>
</button>
```

#### Gradient Logout Button
```html
<button class="group flex w-full items-center px-4 py-3 text-sm font-medium bg-gradient-to-r from-red-500 to-pink-500 text-white hover:from-red-600 hover:to-pink-600 rounded-b-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
  <svg class="mr-3 h-5 w-5 text-white"><!-- Icon --></svg>
  Sign Out
</button>
```

#### Outline Button
```html
<button class="btn btn-outline btn-primary w-full">
  <i class="fas fa-icon mr-2"></i>Button Text
</button>
```

### Form Components

#### Enhanced Input Field with Icon
```html
<div class="space-y-2">
  <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
  <div class="relative">
    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
      <svg class="h-5 w-5 text-gray-400"><!-- Icon --></svg>
    </div>
    <input class="pl-10 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-20 transition-all duration-300" placeholder="Enter text">
  </div>
</div>
```

#### Selection Cards
```html
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div class="relative cursor-pointer rounded-lg border-2 p-4 transition-all duration-200 hover:shadow-md border-blue-500 bg-blue-50 shadow-md">
    <div class="flex items-start space-x-3">
      <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
        <i class="fas fa-icon text-blue-600"></i>
      </div>
      <div class="flex-1">
        <h4 class="text-base font-semibold text-gray-900 mb-2">Title</h4>
        <p class="text-sm text-gray-600">Description</p>
      </div>
    </div>
  </div>
</div>
```

### Navigation Components

#### Desktop Navigation Link
```html
<a class="group relative inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 bg-gradient-to-r from-blue-500 to-purple-500 text-white shadow-lg">
  <svg class="h-4 w-4 mr-2 text-white transition-colors duration-300"><!-- Icon --></svg>
  Link Text
  <div class="absolute inset-0 bg-gradient-to-r from-blue-500/20 to-purple-500/20 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
</a>
```

#### Mobile Navigation Link
```html
<a class="group flex items-center pl-4 pr-4 py-3 mx-3 rounded-xl bg-gradient-to-r from-blue-500 to-purple-500 text-white shadow-lg text-base font-medium transition-all duration-300">
  <svg class="h-5 w-5 mr-3 text-white transition-colors duration-300"><!-- Icon --></svg>
  Link Text
</a>
```

#### Navigation User Button
```html
<button class="group flex items-center space-x-2 rounded-xl px-3 py-2 hover:bg-white/60 hover:backdrop-blur-md hover:shadow-md transition-all duration-300 focus:outline-none">
  <img class="h-9 w-9 rounded-full border-2 border-transparent group-hover:border-purple-200 object-cover transition-all duration-300 shadow-sm" src="..." alt="...">
  <div class="hidden md:block">
    <div class="text-sm font-medium text-gray-800">User Name</div>
    <div class="text-xs text-gray-500 truncate max-w-[120px]">@username</div>
  </div>
  <svg class="h-5 w-5 text-gray-400 group-hover:text-purple-500 transition-colors duration-300"><!-- Chevron --></svg>
</button>
```

---

## Status & Badge Components

### Status Badge System
```html
<!-- Dynamic Status Badge -->
@php
    $statusConfig = [
        'unpublished' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'fa-eye-slash'],
        'open' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'fa-check-circle'],
        'review' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-eye'],
        'completed' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-check'],
        'closed' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'fa-times-circle'],
    ];
    $config = $statusConfig[$status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'fa-question-circle'];
@endphp
<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $config['bg'] }} {{ $config['text'] }} border border-white/20 shadow-sm">
    <i class="fas {{ $config['icon'] }} mr-1"></i>
    {{ ucfirst($status) }}
</span>
```

### Genre Badge
```html
<span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-800">
    <i class="fas fa-music mr-1"></i>{{ $genre }}
</span>
```

### Budget Badge
```html
<div class="bg-blue-50 border border-blue-200 text-blue-800 font-semibold px-3 py-2 rounded-lg text-sm">
    ${{ number_format($budget) }}
</div>
```

---

## Dropdown & Modal Patterns

### Modern Dropdown Container
```html
<div class="absolute right-0 z-50 mt-2 w-64 bg-white/95 backdrop-blur-md rounded-xl shadow-xl border border-white/20">
    <!-- Background Effects -->
    <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5 rounded-xl"></div>
    
    <div class="relative z-10">
        <!-- Content -->
    </div>
</div>
```

### Dropdown User Header
```html
<div class="border-b border-white/20 bg-white/50 backdrop-blur-md rounded-t-xl px-4 py-4">
    <div class="flex items-center">
        <div class="shrink-0 mr-3">
            <img class="h-12 w-12 rounded-full object-cover border-2 border-purple-200 shadow-lg" src="..." alt="...">
        </div>
        <div>
            <div class="font-semibold text-base text-gray-900">User Name</div>
            <div class="font-medium text-sm text-gray-600 truncate max-w-[160px]">@username</div>
        </div>
    </div>
</div>
```

### Dropdown Menu Item
```html
<a class="group flex items-center px-4 py-3 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-white/60 hover:backdrop-blur-md transition-all duration-300">
    <svg class="mr-3 h-5 w-5 text-gray-500 group-hover:text-green-500 transition-colors duration-300"><!-- Icon --></svg>
    Menu Item
</a>
```

### Form Dropdown Panel
```html
<div class="absolute z-50 right-0 mt-3 w-80 md:w-96 bg-white/95 backdrop-blur-md rounded-xl shadow-xl border border-white/20">
    <!-- Background Effects -->
    <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5 rounded-xl"></div>
    
    <div class="relative z-10 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="h-5 w-5 mr-2 text-blue-500"><!-- Icon --></svg>
            Form Title
        </h3>
        <!-- Form Content -->
    </div>
</div>
```

### Dropdown Transitions
```html
<!-- Alpine.js Transitions -->
x-transition:enter="transition ease-out duration-200"
x-transition:enter-start="opacity-0 scale-95"
x-transition:enter-end="opacity-100 scale-100"
x-transition:leave="transition ease-in duration-75"
x-transition:leave-start="opacity-100 scale-100"
x-transition:leave-end="opacity-0 scale-95"
```

---

## Interactive Elements

### Hover Effects
```css
/* Scale Transform */
transform hover:scale-105
transform hover:scale-[1.02]

/* Shadow Enhancement */
shadow-lg hover:shadow-xl
hover:shadow-2xl

/* Background Transitions */
hover:bg-white/60 hover:backdrop-blur-md hover:shadow-md

/* Icon Color Transitions */
group-hover:text-green-500 /* Dashboard */
group-hover:text-purple-500 /* Settings */
group-hover:text-blue-500 /* Billing */
group-hover:text-indigo-500 /* Profile */
group-hover:text-orange-500 /* Setup */
```

### Focus States
```css
/* Input Focus */
focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
focus:ring-purple-500 focus:border-purple-500

/* Button Focus */
focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
```

### Loading States
```html
<button wire:loading.attr="disabled" class="disabled:opacity-75 disabled:cursor-not-allowed disabled:transform-none">
  <span wire:loading.remove>Normal Text</span>
  <span wire:loading>
    <i class="fas fa-spinner fa-spin mr-2"></i>Loading...
  </span>
</button>
```

### Interactive Button States
```html
<!-- Toggle Button with Active State -->
<button class="group relative inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 {{ $isActive ? 'bg-gradient-to-r from-blue-500 to-purple-500 text-white shadow-lg' : 'text-gray-700 hover:text-gray-900 hover:bg-white/60 hover:backdrop-blur-md hover:shadow-md' }}">
    @if(!$isActive)
    <div class="absolute inset-0 bg-gradient-to-r from-blue-500/20 to-purple-500/20 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    @endif
</button>
```

---

## Background Effects

### Glass Morphism
```css
bg-white/95 backdrop-blur-md shadow-xl border border-white/20
bg-white/50 backdrop-blur-md /* For headers */
bg-white/60 backdrop-blur-md /* For hover states */
```

### Gradient Overlays
```html
<!-- Subtle Background -->
<div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5"></div>

<!-- Hero Background -->
<div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 via-purple-600/20 to-pink-600/20"></div>

<!-- Navigation Background -->
<div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5"></div>
```

### Decorative Elements
```html
<!-- Floating Blur Circles -->
<div class="absolute top-20 left-10 w-20 h-20 bg-blue-200/30 rounded-full blur-xl"></div>
<div class="absolute bottom-20 right-10 w-32 h-32 bg-purple-200/30 rounded-full blur-xl"></div>
<div class="absolute top-1/2 left-1/4 w-16 h-16 bg-pink-200/30 rounded-full blur-xl"></div>
```

---

## Animation & Transitions

### Standard Transitions
```css
transition-all duration-300
transition-all duration-200
transition-colors duration-300
transition-transform duration-300
transition-opacity duration-300
```

### Custom Animations
```css
/* Fade In Up */
@keyframes fadeIn {
  0% { opacity: 0; transform: translateY(10px); }
  100% { opacity: 1; transform: translateY(0); }
}

/* Shimmer Effect */
<div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>

/* Pulse Animation */
animate-pulse

/* Spin Animation */
animate-spin

/* Rotation on Interaction */
:class="{'rotate-90': open}" /* For hamburger menu */
```

### Hover Scale Effects Guidelines
```css
/* Standard Button Hover Scale - RECOMMENDED */
hover:scale-105 /* For buttons, cards, and interactive elements */

/* Subtle Card Hover Scale - OPTIONAL */
hover:scale-[1.02] /* For large content cards when appropriate */

/* Pitch Components - NO SCALE */
/* Pitch list items and cards should NOT use hover scale effects */
/* Use shadow and ring effects instead for better mobile experience */
hover:shadow-xl hover:ring-2 hover:ring-blue-200/50
```

### Component-Specific Animation Rules

#### Pitch List Components
- **NO scale effects**: Removed for better mobile experience and reduced motion sensitivity
- **Use instead**: `hover:shadow-xl`, `hover:ring-2`, backdrop blur transitions
- **Transitions**: `transition-all duration-200` and `duration-300`

#### Button Components  
- **Scale effects OK**: `hover:scale-105` acceptable for action buttons
- **Exception**: Pitch action buttons use shadow enhancement instead

#### Navigation Components
- **Scale effects OK**: Logo and interactive navigation elements
- **Dropdown items**: Use backdrop blur and color transitions instead

### Staggered Animations
```html
<div class="animate-fade-in-up" style="animation-delay: 0.2s;"></div>
<div class="animate-fade-in-up" style="animation-delay: 0.4s;"></div>
<div class="animate-fade-in-up" style="animation-delay: 0.6s;"></div>
```

---

## Responsive Design

### Breakpoint Strategy
- **Mobile First**: Base styles for mobile
- **sm**: `640px` - Small tablets
- **md**: `768px` - Tablets
- **lg**: `1024px` - Laptops
- **xl**: `1280px` - Desktops

### Common Responsive Patterns
```css
/* Text Scaling */
text-4xl sm:text-6xl lg:text-8xl

/* Grid Responsiveness */
grid-cols-1 md:grid-cols-2 lg:grid-cols-3

/* Spacing Adjustments */
px-4 sm:px-6 lg:px-8
space-y-4 lg:space-y-6

/* Layout Changes */
flex-col sm:flex-row
hidden lg:block
lg:hidden

/* Dropdown Responsiveness */
w-80 md:w-96 /* Form dropdowns */
w-64 /* User dropdowns */
max-w-[120px] md:max-w-[160px] /* Text truncation */
```

---

## Page-Specific Patterns

### Homepage Hero
- **Background**: Dark gradient with animated overlays
- **Typography**: Large, bold headings with gradient text
- **Interactive Elements**: Role toggle with glass morphism
- **CTA Buttons**: Gradient buttons with shimmer effects

### Authentication Pages
- **Layout**: Centered card with decorative background
- **Background**: Light gradients with floating blur elements
- **Forms**: Icon-enhanced inputs with rounded corners
- **Cards**: Glass morphism with backdrop blur

### Navigation
- **Desktop**: Horizontal layout with gradient active states
- **Mobile**: Slide-down menu with backdrop blur
- **Logo**: Interactive with hover scale effect
- **Links**: Gradient backgrounds for active states
- **Dropdowns**: Glass morphism with user headers and gradient logout buttons

### Project Management
- **Layout**: Two-column grid (main content + sidebar)
- **Cards**: Clean white backgrounds with subtle borders
- **Color Coding**: Purple for client details, amber for warnings
- **Spacing**: Consistent `space-y-6` between sections
- **Status Badges**: Color-coded with icons for project status

### Client Management
- **Design**: Professional, clean aesthetic
- **Cards**: `bg-white rounded-lg border border-base-300 shadow-sm`
- **Sections**: Color-coded with appropriate semantic colors
- **Typography**: Clear hierarchy with proper contrast

### Create Project Wizard
- **Selection Cards**: Large, interactive cards with icons
- **Progress**: Visual step indicators
- **Forms**: Enhanced inputs with validation states
- **Layout**: Single-column focus with centered content

---

## Implementation Guidelines

### CSS Class Patterns
1. **Always use Tailwind utilities** for consistency
2. **Combine related classes** logically (layout → appearance → behavior)
3. **Use semantic color names** when possible
4. **Maintain consistent spacing** with the established scale
5. **Apply glass morphism consistently** across dropdowns and modals

### Component Structure
1. **Wrap in semantic containers** with proper spacing
2. **Use consistent heading hierarchy**
3. **Include proper accessibility attributes**
4. **Implement loading and error states**
5. **Add background effects for modern appearance**

### Interactive Behavior
1. **Provide immediate visual feedback** for user actions
2. **Use consistent transition durations** (300ms standard, 200ms fast)
3. **Implement proper focus management**
4. **Ensure mobile-friendly touch targets**
5. **Include hover states with backdrop blur**

### Dropdown Best Practices
1. **Use glass morphism** for modern appearance
2. **Include gradient background overlays** for depth
3. **Implement proper user headers** with profile images
4. **Use semantic colors** for different menu items
5. **Add gradient logout buttons** for clear action hierarchy

### Performance Considerations
1. **Use backdrop-blur sparingly** for performance
2. **Optimize animations** for 60fps
3. **Lazy load heavy visual effects**
4. **Test on various devices and browsers**
5. **Monitor glass morphism performance** on lower-end devices

---

## Future Enhancements

### Planned Additions
- Dark mode support with CSS custom properties
- Enhanced micro-interactions
- Advanced animation library integration
- Component documentation with Storybook
- Accessibility improvements for glass morphism effects

### Maintenance Notes
- Regular audit of unused CSS classes
- Performance monitoring of animations and backdrop blur
- Accessibility testing with screen readers
- Cross-browser compatibility verification
- Glass morphism fallbacks for unsupported browsers

---

*This style guide serves as the single source of truth for MixPitch's design system. All new components and pages should follow these established patterns to maintain consistency and quality across the platform.* 

---

## Modal Components

#### Update Pitch Status Component
- **Component**: `<x-update-pitch-status :pitch="$pitch" :status="$pitch->status" />`
- **Button Styling**: Glass morphism with gradient backgrounds and hover effects
- **Status-Specific Colors**:
  - Allow Access: Green gradient (`from-green-600 to-emerald-600`)
  - Remove Access: Amber gradient (`from-amber-500 to-orange-500`)
  - Review: Blue gradient (`from-blue-600 to-indigo-600`)
  - Approve: Green gradient (`from-green-600 to-emerald-600`)
  - Deny: Red gradient (`from-red-500 to-red-600`)
  - Request Revisions: Blue gradient (`from-blue-500 to-blue-600`)
  - Return Actions: Blue gradient (`from-blue-500 to-blue-600`)
- **Interactive Effects**: Scale on hover (`hover:scale-105`) with enhanced shadows
- **Information States**: Glass morphism cards for inactive/locked states

#### Button Pattern for Pitch Actions
```html
<button class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg z-30 relative">
    <i class="fas fa-check mr-2"></i>Action Text
</button>
```

#### Information Card Pattern
```html
<div class="flex items-center text-gray-500 text-sm italic bg-gradient-to-r from-gray-50/80 to-gray-100/80 backdrop-blur-sm border border-gray-200/50 rounded-xl p-3">
    <i class="fas fa-info-circle mr-2"></i>Information message
</div>
```

---

## Pitch List Components

### Overview
The Pitch List component provides a comprehensive interface for project owners to review and manage pitch submissions. It features modern glass morphism design, status-based theming, and mobile-optimized layouts.

### Core Component Structure
```blade
<x-project.pitch-list :project="$project" />
```

### Container Design Pattern
```html
    <!-- Background Effects -->
<div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="absolute top-4 right-4 w-32 h-32 bg-gradient-to-br from-purple-400/10 to-pink-400/10 rounded-full blur-2xl"></div>
    <div class="absolute bottom-8 left-8 w-24 h-24 bg-gradient-to-tr from-blue-400/10 to-purple-400/10 rounded-full blur-xl"></div>
    </div>

<div class="relative bg-gradient-to-br from-white/95 to-purple-50/90 backdrop-blur-md border border-white/50 rounded-2xl shadow-xl overflow-hidden">
    <!-- Header -->
        <!-- Content -->
</div>
```

### Modern Header Pattern
```html
<div class="bg-gradient-to-r from-purple-50/80 to-blue-50/80 backdrop-blur-sm border-b border-purple-200/30 p-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-purple-500 to-blue-600 rounded-xl mr-4 shadow-lg">
                <i class="fas fa-paper-plane text-white text-lg"></i>
</div>
        <div>
                <h3 class="text-xl font-bold text-purple-900">Submitted Pitches</h3>
                <p class="text-sm text-purple-700 mt-1">Review and manage pitch submissions</p>
        </div>
    </div>
        <div class="bg-gradient-to-br from-white/80 to-purple-50/80 backdrop-blur-sm border border-purple-200/50 rounded-xl px-4 py-2 shadow-sm">
            <div class="text-lg font-bold text-purple-900">{{ $project->pitches->count() }}</div>
            <div class="text-xs text-purple-600">Total</div>
</div>
        </div>
</div>
```

### Status-Based Dynamic Theming
```php
@php
$statusTheme = match($pitch->status) {
    'completed' => [
        'bg' => 'bg-gradient-to-br from-green-50/90 to-emerald-50/80',
        'border' => 'border-green-200/50',
        'accent' => 'bg-gradient-to-br from-green-500 to-emerald-600',
        'ring' => 'ring-green-200/50'
    ],
    'approved' => $hasMultipleApprovedPitches ? [
        'bg' => 'bg-gradient-to-br from-amber-50/90 to-orange-50/80',
        'border' => 'border-amber-200/50',
        'accent' => 'bg-gradient-to-br from-amber-500 to-orange-600',
        'ring' => 'ring-amber-200/50'
    ] : [
        'bg' => 'bg-gradient-to-br from-blue-50/90 to-indigo-50/80',
        'border' => 'border-blue-200/50',
        'accent' => 'bg-gradient-to-br from-blue-500 to-indigo-600',
        'ring' => 'ring-blue-200/50'
    ],
    'denied' => [
        'bg' => 'bg-gradient-to-br from-red-50/90 to-pink-50/80',
        'border' => 'border-red-200/50',
        'accent' => 'bg-gradient-to-br from-red-500 to-pink-600',
        'ring' => 'ring-red-200/50'
    ],
    // Additional status mappings...
};
@endphp
```

### Individual Pitch Card Pattern
```html
<div class="relative group">
    <!-- Status Accent Bar -->
    <div class="absolute left-0 top-6 bottom-6 w-1 {{ $statusTheme['accent'] }} rounded-r-full z-10"></div>
    
    <!-- Main Card -->
    <div class="relative {{ $statusTheme['bg'] }} backdrop-blur-md border {{ $statusTheme['border'] }} rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 hover:{{ $statusTheme['ring'] }} hover:ring-2 overflow-hidden">
            <!-- Content -->
                </div>
</div>
```

### Enhanced User Profile Section
```html
<div class="p-6 pb-4">
    <div class="flex items-start space-x-4">
        <div class="relative flex-shrink-0">
            <img class="h-12 w-12 rounded-xl object-cover border-2 border-white shadow-lg ring-2 ring-white/20" src="..." alt="..." />
            <div class="absolute -bottom-1 -right-1 w-4 h-4 {{ $statusTheme['accent'] }} rounded-full border-2 border-white shadow-sm"></div>
</div>
        <div class="flex-1 min-w-0">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <!-- User info and status badge -->
</div>
</div>
</div>
</div>
```

### Mobile-Optimized Layout
- **Profile Section**: Uses `flex-start` for better alignment on mobile
- **Status Badge**: Positioned separately from user info for better spacing
- **Action Buttons**: Grouped in dedicated section with conditional rendering
- **Responsive Typography**: Scales appropriately across devices
- **Touch Targets**: Adequate sizing for mobile interaction

### Modern Status Badges
```html
<span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold {{ $pitch->getStatusColorClass() }} border-2 border-white/50 shadow-lg backdrop-blur-sm">
    <div class="w-2 h-2 rounded-full mr-2 {{ $statusTheme['accent'] }}"></div>
    {{ $pitch->readable_status }}
</span>
```

### Enhanced Snapshots Section
```html
<div class="mx-6 mb-4 p-4 bg-gradient-to-br from-white/60 to-gray-50/40 backdrop-blur-sm border border-white/40 rounded-xl shadow-inner">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg mr-3">
                <i class="fas fa-history text-white text-sm"></i>
    </div>
            <div>
                <h5 class="font-bold text-gray-800 text-sm">Snapshots</h5>
                <p class="text-xs text-gray-600">Version history</p>
</div>
</div>
        <!-- Status badges -->
            </div>
            
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <!-- Snapshot items -->
    </div>
</div>
```

### Individual Snapshot Card
```html
<a href="..." class="group flex items-center p-3 bg-gradient-to-br from-white/80 to-gray-50/60 hover:from-white/90 hover:to-blue-50/60 backdrop-blur-sm border border-white/50 hover:border-blue-200/50 rounded-xl transition-all duration-200 hover:shadow-md">
    <!-- Icon, content, and status badge -->
</a>
```

### Enhanced Empty State
```html
<div class="text-center py-12">
    <div class="relative mb-6">
        <div class="flex items-center justify-center w-20 h-20 bg-gradient-to-br from-purple-100 to-blue-100 backdrop-blur-sm border border-purple-200/50 rounded-2xl mx-auto shadow-lg">
            <i class="fas fa-paper-plane text-3xl text-purple-500"></i>
        </div>
        <div class="absolute -top-2 -right-2 w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
            <i class="fas fa-plus text-white text-sm"></i>
            </div>
            </div>
    
        <div class="mb-6">
        <h4 class="text-xl font-bold text-gray-800 mb-2">No pitches submitted yet</h4>
        <p class="text-gray-600 max-w-md mx-auto leading-relaxed">...</p>
                    </div>
    
    <!-- Enhanced tips grid -->
</div>
```

### Enhanced Tips Cards
```html
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left">
    <div class="flex items-center p-3 bg-gradient-to-br from-white/60 to-purple-50/40 rounded-lg border border-white/50">
        <i class="fas fa-share-alt text-purple-500 mr-3"></i>
        <span class="text-sm font-medium text-purple-800">Share on social media</span>
        </div>
    <!-- Additional tip cards -->
</div>
```

### Action Button Patterns

#### Primary Action Buttons
```html
<a href="..." class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-medium transition-all duration-200 hover:shadow-lg shadow-sm">
    <i class="fas fa-credit-card mr-2"></i> Process Payment
</a>
```

#### Secondary Action Buttons
```html
<a href="..." class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white rounded-xl font-medium transition-all duration-200 hover:shadow-lg shadow-sm">
    <i class="fas fa-file-invoice-dollar mr-2"></i> View Receipt
</a>
```

### Payment Status Display
```html
<div class="bg-gradient-to-r from-white/80 to-gray-50/80 backdrop-blur-sm border border-gray-200/50 rounded-lg px-3 py-2 shadow-sm w-fit">
    <div class="flex items-center text-xs">
        <i class="fas fa-credit-card mr-2 text-gray-500"></i>
        <span class="text-gray-600 font-medium">Payment Status:</span>
        <span class="ml-2 font-bold text-green-600">Paid</span>
    </div>
</div>
```

### Enhanced Rating Display
```html
<div class="bg-gradient-to-r from-orange-100 to-amber-100 border border-orange-200 rounded-lg px-3 py-1 flex items-center">
    <div class="flex items-center mr-2">
        @for($i = 1; $i <= 5; $i++)
            <i class="fas fa-star text-xs {{ $i <= $rating ? 'text-orange-500' : 'text-gray-300' }}"></i>
        @endfor
        </div>
    <span class="text-sm font-bold text-orange-800">{{ number_format($rating, 1) }}</span>
</div>
```

### Completion Feedback Section
```html
<div class="mx-6 mb-4 p-4 bg-gradient-to-br from-green-50/80 to-emerald-50/70 backdrop-blur-sm border border-green-200/50 rounded-xl shadow-lg">
    <div class="flex items-center mb-3">
        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl mr-3 shadow-lg">
            <i class="fas fa-comment-alt text-white"></i>
        </div>
        <div>
            <h5 class="font-bold text-green-900">Project Completed</h5>
            <p class="text-sm text-green-700">Feedback from project owner</p>
        </div>
    </div>
    <div class="bg-gradient-to-br from-white/80 to-green-50/60 backdrop-blur-sm border border-green-200/30 rounded-lg p-4">
        <p class="text-green-800 leading-relaxed">{{ $pitch->completion_feedback }}</p>
    </div>
</div>
```

### Key Design Principles

#### Status-Based Theming
- **Dynamic Colors**: Each pitch status has its own color theme
- **Consistent Application**: Colors apply to backgrounds, borders, accents, and rings
- **Visual Hierarchy**: Status accent bars provide immediate visual recognition
- **Context Awareness**: Multiple approved pitches use amber theme to highlight conflicts

#### Glass Morphism Implementation
- **Backdrop Blur**: Consistent use of `backdrop-blur-md` and `backdrop-blur-sm`
- **Semi-transparent Backgrounds**: `bg-white/95`, `bg-white/80`, `bg-white/60`
- **Layered Effects**: Multiple backdrop blur layers for depth
- **Border Enhancement**: `border border-white/20` for glass effect

#### Mobile-First Responsive Design
- **Flexible Layouts**: `flex-col sm:flex-row` patterns
- **Responsive Typography**: Proper text scaling across devices
- **Touch-Friendly Targets**: Adequate button and link sizing
- **Conditional Sections**: Actions section only shows when needed

#### Animation Guidelines
- **Hover Effects**: Removed scale transforms as requested
- **Subtle Transitions**: `transition-all duration-200` and `duration-300`
- **Shadow Enhancements**: `hover:shadow-lg` and `hover:shadow-xl`
- **Ring Effects**: `hover:ring-2` for focus indication

#### Content Organization
- **Conditional Rendering**: Actions section only displays when relevant
- **Logical Grouping**: User info, actions, and supplementary content separated
- **Visual Separation**: Proper spacing and container hierarchy
- **Information Density**: Balanced content without overcrowding

### Implementation Notes
- **No Scale Effects**: Scale hover effects removed for better mobile experience
- **Performance**: Backdrop blur used selectively for optimal performance
- **Accessibility**: Proper contrast ratios maintained across all themes
- **Consistency**: Follows established MIXPITCH design patterns
- **Maintainability**: Uses PHP match expressions for scalable status theming

---