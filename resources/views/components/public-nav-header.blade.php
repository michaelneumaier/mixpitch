<!-- Simple Public Navigation Header -->
<flux:header container class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 dark:bg-gray-800/80 dark:border-gray-700/50">
    <flux:spacer />
    
    <flux:navbar class="mx-auto">
        <flux:navbar.item href="{{ url('/') }}" :current="request()->is('/')">
            Home
        </flux:navbar.item>
        
        <flux:navbar.item href="{{ route('pricing') }}" :current="request()->routeIs('pricing')">
            Pricing
        </flux:navbar.item>
        
        <flux:navbar.item href="{{ route('about') }}" :current="request()->routeIs('about')">
            About
        </flux:navbar.item>
    </flux:navbar>
    
    <flux:spacer />
</flux:header>