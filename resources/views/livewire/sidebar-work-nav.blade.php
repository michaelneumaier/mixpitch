<div x-data="{
    expandedSection: @js(
        request()->routeIs('projects.manage') ? 'projects' : 
        (request()->routeIs('projects.manage-client') ? 'client' : 
        (request()->routeIs('projects.pitches.show') ? 'pitches' : 'none'))
    ),
    toggleSection(section) {
        this.expandedSection = this.expandedSection === section ? 'none' : section;
    }
}">
@if($counts['total'] > 0)
<!-- My Work Heading -->
<div class="px-2 mb-2">
    <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
        <flux:icon name="briefcase" size="xs" />
        My Work
    </a>
</div>

<!-- Projects Section -->
@if($counts['projects'] > 0)
<div class="mb-2">
    <!-- Custom Header -->
    <div class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg cursor-pointer transition-colors" 
         x-on:click="toggleSection('projects')">
        <span class="flex items-center gap-2">
            <flux:icon name="folder" size="sm" class="text-gray-500 dark:text-gray-400" />
            Projects
            <flux:badge size="sm" color="blue">{{ $counts['projects'] }}</flux:badge>
        </span>
        <flux:icon name="chevron-down" size="sm" class="text-gray-400 transition-transform duration-200" 
                   x-bind:class="{ 'rotate-180': expandedSection === 'projects' }" />
    </div>
    
    <!-- Collapsible Content -->
    <div x-show="expandedSection === 'projects'" x-transition x-cloak class="ml-4 mt-1 space-y-1">
        @foreach($projects as $project)
        <a href="{{ route('projects.manage', $project) }}" 
           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition-colors {{ request()->route('project')?->id == $project->id ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' : '' }}">
            <span class="truncate">{{ $project->name ?? 'Untitled' }}</span>
        </a>
        @endforeach
        
        @if($counts['projects'] > 5)
        <a href="{{ route('dashboard') }}#projects" class="block px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 rounded-md transition-colors">
            View all {{ $counts['projects'] }} projects →
        </a>
        @endif
    </div>
</div>
@endif

<!-- Pitches Section -->
@if($counts['pitches'] > 0)
<div class="mb-2">
    <!-- Custom Header -->
    <div class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg cursor-pointer transition-colors" 
         x-on:click="toggleSection('pitches')">
        <span class="flex items-center gap-2">
            <flux:icon name="paper-airplane" size="sm" class="text-gray-500 dark:text-gray-400" />
            Pitches
            <flux:badge size="sm" color="indigo">{{ $counts['pitches'] }}</flux:badge>
        </span>
        <flux:icon name="chevron-down" size="sm" class="text-gray-400 transition-transform duration-200" 
                   x-bind:class="{ 'rotate-180': expandedSection === 'pitches' }" />
    </div>
    
    <!-- Collapsible Content -->
    <div x-show="expandedSection === 'pitches'" x-transition x-cloak class="ml-4 mt-1 space-y-1">
        @foreach($pitches as $pitch)
        <a href="{{ route('projects.pitches.show', [$pitch->project, $pitch]) }}" 
           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition-colors {{ request()->route('pitch')?->id == $pitch->id ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' : '' }}">
            <span class="truncate">{{ $pitch->project->name ?? 'Untitled Project' }}</span>
        </a>
        @endforeach
        
        @if($counts['pitches'] > 5)
        <a href="{{ route('dashboard') }}#pitches" class="block px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 rounded-md transition-colors">
            View all {{ $counts['pitches'] }} pitches →
        </a>
        @endif
    </div>
</div>
@endif

<!-- Contests Section -->
@if($counts['contests'] > 0)
<div class="mb-2">
    <!-- Custom Header -->
    <div class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg cursor-pointer transition-colors" 
         x-on:click="toggleSection('contests')">
        <span class="flex items-center gap-2">
            <flux:icon name="trophy" size="sm" class="text-gray-500 dark:text-gray-400" />
            Contests
            <flux:badge size="sm" color="yellow">{{ $counts['contests'] }}</flux:badge>
        </span>
        <flux:icon name="chevron-down" size="sm" class="text-gray-400 transition-transform duration-200" 
                   x-bind:class="{ 'rotate-180': expandedSection === 'contests' }" />
    </div>
    
    <!-- Collapsible Content -->
    <div x-show="expandedSection === 'contests'" x-transition x-cloak class="ml-4 mt-1 space-y-1">
        @foreach($contests as $contest)
        <a href="{{ route('projects.manage', $contest->project) }}" 
           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition-colors {{ request()->route('pitch')?->id == $contest->id ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' : '' }}">
            <span class="truncate">{{ $contest->project->name ?? 'Untitled Contest' }}</span>
        </a>
        @endforeach
        
        @if($counts['contests'] > 5)
        <a href="{{ route('dashboard') }}#contests" class="block px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 rounded-md transition-colors">
            View all {{ $counts['contests'] }} contests →
        </a>
        @endif
    </div>
</div>
@endif

<!-- Client Projects Section -->
@if($counts['client_projects'] > 0)
<div class="mb-2">
    <!-- Custom Header -->
    <div class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg cursor-pointer transition-colors" 
         x-on:click="toggleSection('client')">
        <span class="flex items-center gap-2">
            <flux:icon name="users" size="sm" class="text-gray-500 dark:text-gray-400" />
            Client Work
            <flux:badge size="sm" color="purple">{{ $counts['client_projects'] }}</flux:badge>
        </span>
        <flux:icon name="chevron-down" size="sm" class="text-gray-400 transition-transform duration-200" 
                   x-bind:class="{ 'rotate-180': expandedSection === 'client' }" />
    </div>
    
    <!-- Collapsible Content -->
    <div x-show="expandedSection === 'client'" x-transition x-cloak class="ml-4 mt-1 space-y-1">
        @foreach($clientProjects as $clientProject)
        <a href="{{ route('projects.manage-client', $clientProject) }}" 
           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition-colors {{ request()->route('project')?->id == $clientProject->id ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' : '' }}">
            <span class="truncate">{{ $clientProject->name ?? 'Untitled' }}</span>
        </a>
        @endforeach
        
        @if($counts['client_projects'] > 5)
        <a href="{{ route('dashboard') }}#client-projects" class="block px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 rounded-md transition-colors">
            View all {{ $counts['client_projects'] }} client projects →
        </a>
        @endif
    </div>
</div>
@endif

@endif
</div>
