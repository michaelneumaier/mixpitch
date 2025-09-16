<div x-data="{
    expandedSection: @js(
        request()->routeIs('projects.manage') && optional(request()->route('project'))->workflow_type === 'contest' ? 'contests' :
        (request()->routeIs('projects.manage') ? 'projects' : 
        (request()->routeIs('projects.manage-client') ? 'client' : 
        (request()->routeIs('projects.pitches.show') && optional(optional(request()->route('pitch'))->project)->workflow_type === 'contest' ? 'contests' :
        (request()->routeIs('projects.pitches.show') ? 'pitches' : 'none'))))
    ),
    expandTimeout: null,
    dragExpandSection: null,
    toggleSection(section) {
        this.expandedSection = this.expandedSection === section ? 'none' : section;
    },
    handleDragExpandEnter(section) {
        if (this.expandedSection !== section) {
            this.dragExpandSection = section;
            this.expandTimeout = setTimeout(() => {
                if (this.dragExpandSection === section) {
                    this.expandedSection = section;
                }
            }, 1000);
        }
    },
    handleDragExpandLeave(section) {
        if (this.dragExpandSection === section) {
            this.dragExpandSection = null;
            if (this.expandTimeout) {
                clearTimeout(this.expandTimeout);
                this.expandTimeout = null;
            }
        }
    }
}"
x-on:drag-expand-section.window="
    console.log('🎧 Alpine.js received drag-expand-section event:', $event.detail);
    console.log('📊 Current expandedSection:', expandedSection);
    if ($event.detail.section && expandedSection !== $event.detail.section) {
        console.log('🔄 Expanding section from', expandedSection, 'to', $event.detail.section);
        expandedSection = $event.detail.section;
        console.log('✅ Section expanded successfully');
    } else {
        console.log('❌ Section expansion skipped - already expanded or no section specified');
    }
">
@if($counts['total'] > 0)
<!-- My Work Heading -->
<div class="px-3 mb-2 pt-4 border-t border-gray-200/50 dark:border-gray-700/50">
    <a href="{{ route('dashboard') }}" wire:navigate class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
        <flux:icon name="briefcase" class="size-4" />
        My Work
    </a>
</div>

<!-- Projects Section -->
@if($counts['projects'] > 0)
<div>
    <!-- Custom Header -->
    <div class="section-header-drop-zone flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg cursor-pointer transition-colors" 
         x-on:click="toggleSection('projects')"
         x-data="{
             sectionMeta: {
                 section: 'projects',
                 modelLabel: 'Projects Section',
                 expandable: true,
                 expanded: false
             }
         }"
         x-init="
             if (window.GlobalDragDrop) {
                 window.GlobalDragDrop.registerDropZone($el, sectionMeta);
             }
         "
         x-bind:class="{ 
             'bg-blue-50 dark:bg-blue-900/20': dragExpandSection === 'projects'
         }">
        <span class="flex items-center gap-2">
            <flux:icon name="folder" class="size-4 text-gray-500 dark:text-gray-400" />
            Projects
            <flux:badge size="sm" color="blue">{{ $counts['projects'] }}</flux:badge>
        </span>
        <flux:icon name="chevron-down" class="size-4 text-gray-400 transition-transform duration-200" 
                   x-bind:class="{ 'rotate-180': expandedSection === 'projects' }" />
    </div>
    
    <!-- Collapsible Content -->
    <div x-show="expandedSection === 'projects'" x-transition x-cloak class="ml-4 mt-1 space-y-1">
        @foreach($projects as $project)
        <a href="{{ route('projects.manage', $project) }}" wire:navigate 
           class="sidebar-drop-zone block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition-colors {{ request()->route('project')?->id == $project->id ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' : '' }}"
           x-data="{
               projectMeta: {
                   modelType: 'App\\Models\\Project',
                   modelId: {{ $project->id }},
                   context: 'projects',
                   modelLabel: 'Project',
                   projectTitle: '{{ addslashes($project->name ?? 'Untitled') }}',
                   workflowType: '{{ $project->workflow_type ?? 'standard' }}'
               }
           }"
           x-init="
               if (window.GlobalDragDrop) {
                   window.GlobalDragDrop.registerDropZone($el, projectMeta);
               }
           "
           data-project-id="{{ $project->id }}"
           data-item-type="project"
           data-workflow-type="{{ $project->workflow_type }}">
            <span class="block truncate max-w-full overflow-hidden">{{ $project->name ?? 'Untitled' }}</span>
        </a>
        @endforeach
        
        @if($counts['projects'] > 5)
        <a href="{{ route('dashboard') }}#projects" wire:navigate class="block px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 rounded-md transition-colors">
            View all {{ $counts['projects'] }} projects →
        </a>
        @endif
    </div>
</div>
@endif

<!-- Pitches Section -->
@if($counts['pitches'] > 0)
<div>
    <!-- Custom Header -->
    <div class="section-header-drop-zone flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg cursor-pointer transition-colors" 
         x-on:click="toggleSection('pitches')"
         x-data="{
             sectionMeta: {
                 section: 'pitches',
                 modelLabel: 'Pitches Section',
                 expandable: true,
                 expanded: false
             }
         }"
         x-init="
             if (window.GlobalDragDrop) {
                 window.GlobalDragDrop.registerDropZone($el, sectionMeta);
             }
         "
         x-bind:class="{ 
             'bg-indigo-50 dark:bg-indigo-900/20': dragExpandSection === 'pitches'
         }">
        <span class="flex items-center gap-2">
            <flux:icon name="paper-airplane" class="size-4 text-gray-500 dark:text-gray-400" />
            Pitches
            <flux:badge size="sm" color="indigo">{{ $counts['pitches'] }}</flux:badge>
        </span>
        <flux:icon name="chevron-down" class="size-4 text-gray-400 transition-transform duration-200" 
                   x-bind:class="{ 'rotate-180': expandedSection === 'pitches' }" />
    </div>
    
    <!-- Collapsible Content -->
    <div x-show="expandedSection === 'pitches'" x-transition x-cloak class="ml-4 mt-1 space-y-1">
        @foreach($pitches as $pitch)
        <a href="{{ route('projects.pitches.show', [$pitch->project, $pitch]) }}" wire:navigate 
           class="sidebar-drop-zone block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition-colors {{ request()->route('pitch')?->id == $pitch->id ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' : '' }}"
           x-data="{
               pitchMeta: {
                   modelType: 'App\\Models\\Pitch',
                   modelId: {{ $pitch->id }},
                   context: 'pitches',
                   modelLabel: 'Pitch',
                   pitchTitle: '{{ addslashes($pitch->project->name ?? 'Untitled Project') }}',
                   projectId: {{ $pitch->project_id }},
                   pitchStatus: '{{ $pitch->status }}',
                   workflowType: '{{ $pitch->project->workflow_type ?? 'standard' }}',
                   @if($pitch->project->workflow_type === 'client_management')
                   isClientManagement: true,
                   clientName: '{{ addslashes($pitch->project->client_name ?? '') }}'
                   @endif
               }
           }"
           x-init="
               if (window.GlobalDragDrop) {
                   window.GlobalDragDrop.registerDropZone($el, pitchMeta);
               }
           "
           data-pitch-id="{{ $pitch->id }}"
           data-project-id="{{ $pitch->project_id }}"
           data-item-type="pitch"
           data-workflow-type="{{ $pitch->project->workflow_type }}"
           data-pitch-status="{{ $pitch->status }}">
            <span class="block truncate max-w-full overflow-hidden">{{ $pitch->project->name ?? 'Untitled Project' }}</span>
        </a>
        @endforeach
        
        @if($counts['pitches'] > 5)
        <a href="{{ route('dashboard') }}#pitches" wire:navigate class="block px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 rounded-md transition-colors">
            View all {{ $counts['pitches'] }} pitches →
        </a>
        @endif
    </div>
</div>
@endif

<!-- Contests Section -->
@if($counts['contests'] > 0)
<div>
    <!-- Custom Header -->
    <div class="section-header-drop-zone flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg cursor-pointer transition-colors" 
         x-on:click="toggleSection('contests')"
         x-data="{
             sectionMeta: {
                 section: 'contests',
                 modelLabel: 'Contests Section',
                 expandable: true,
                 expanded: false
             }
         }"
         x-init="
             if (window.GlobalDragDrop) {
                 window.GlobalDragDrop.registerDropZone($el, sectionMeta);
             }
         "
         x-bind:class="{ 
             'bg-yellow-50 dark:bg-yellow-900/20': dragExpandSection === 'contests'
         }">
        <span class="flex items-center gap-2">
            <flux:icon name="trophy" class="size-4 text-gray-500 dark:text-gray-400" />
            Contests
            <flux:badge size="sm" color="yellow">{{ $counts['contests'] }}</flux:badge>
        </span>
        <flux:icon name="chevron-down" class="size-4 text-gray-400 transition-transform duration-200" 
                   x-bind:class="{ 'rotate-180': expandedSection === 'contests' }" />
    </div>
    
    <!-- Collapsible Content -->
    <div x-show="expandedSection === 'contests'" x-transition x-cloak class="ml-4 mt-1 space-y-1">
        @foreach($contests as $contest)
        <a href="{{ route($contest->route_name, $contest->route_params) }}" wire:navigate 
           class="sidebar-drop-zone block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition-colors {{ 
               ($contest->type === 'project' && request()->route('project')?->id == $contest->id) || 
               ($contest->type === 'pitch' && request()->route('pitch')?->id == $contest->id) ? 
               'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' : '' 
           }}"
           x-data="{
               contestMeta: {
                   @if($contest->type === 'project')
                   modelType: 'App\\Models\\Project',
                   modelId: {{ $contest->id }},
                   context: 'projects',
                   modelLabel: 'Project',
                   projectTitle: '{{ addslashes($contest->name) }}',
                   workflowType: 'contest'
                   @else
                   modelType: 'App\\Models\\Pitch',
                   modelId: {{ $contest->id }},
                   context: 'pitches',
                   modelLabel: 'Pitch',
                   pitchTitle: '{{ addslashes($contest->name) }}',
                   workflowType: 'contest',
                   pitchStatus: '{{ $contest->status ?? 'contest_entry' }}'
                   @endif
               }
           }"
           x-init="
               if (window.GlobalDragDrop) {
                   window.GlobalDragDrop.registerDropZone($el, contestMeta);
               }
           "
           data-contest-id="{{ $contest->id }}"
           data-contest-type="{{ $contest->type }}"
           data-item-type="contest"
           data-workflow-type="contest">
            <span class="block truncate max-w-full overflow-hidden">
                {{ $contest->name }}
                @if($contest->type === 'pitch')
                    <span class="text-xs text-gray-500 dark:text-gray-400">(Entry)</span>
                @endif
            </span>
        </a>
        @endforeach
        
        @if($counts['contests'] > 5)
        <a href="{{ route('dashboard') }}#contests" wire:navigate class="block px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 rounded-md transition-colors">
            View all {{ $counts['contests'] }} contests →
        </a>
        @endif
    </div>
</div>
@endif

<!-- Client Projects Section -->
@if($counts['client_projects'] > 0)
<div>
    <!-- Custom Header -->
    <div class="section-header-drop-zone flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg cursor-pointer transition-colors" 
         x-on:click="toggleSection('client')"
         x-data="{
             sectionMeta: {
                 section: 'client',
                 modelLabel: 'Client Work Section',
                 expandable: true,
                 expanded: false
             }
         }"
         x-init="
             if (window.GlobalDragDrop) {
                 window.GlobalDragDrop.registerDropZone($el, sectionMeta);
             }
         "
         x-bind:class="{ 
             'bg-purple-50 dark:bg-purple-900/20': dragExpandSection === 'client'
         }">
        <span class="flex items-center gap-2">
            <flux:icon name="users" class="size-4 text-gray-500 dark:text-gray-400" />
            Client Work
            <flux:badge size="sm" color="purple">{{ $counts['client_projects'] }}</flux:badge>
        </span>
        <flux:icon name="chevron-down" class="size-4 text-gray-400 transition-transform duration-200" 
                   x-bind:class="{ 'rotate-180': expandedSection === 'client' }" />
    </div>
    
    <!-- Collapsible Content -->
    <div x-show="expandedSection === 'client'" x-transition x-cloak class="ml-4 mt-1 space-y-1">
        @foreach($clientProjects as $clientProject)
        @php
            // For client management, find the producer's pitch to upload deliverables
            $producerPitch = $clientProject->pitches()->where('user_id', auth()->id())->first();
        @endphp
        @if($producerPitch)
        <a href="{{ route('projects.manage-client', $clientProject) }}" wire:navigate 
           class="sidebar-drop-zone block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition-colors {{ request()->route('project')?->id == $clientProject->id ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' : '' }}"
           x-data="{
               clientProjectMeta: {
                   modelType: 'App\\Models\\Pitch',
                   modelId: {{ $producerPitch->id }},
                   context: 'pitches',
                   modelLabel: 'Pitch',
                   pitchTitle: '{{ addslashes($clientProject->name ?? 'Untitled') }}',
                   projectId: {{ $clientProject->id }},
                   pitchStatus: '{{ $producerPitch->status }}',
                   workflowType: 'client_management',
                   isClientManagement: true,
                   clientName: '{{ addslashes($clientProject->client_name ?? '') }}'
               }
           }"
           x-init="
               if (window.GlobalDragDrop) {
                   window.GlobalDragDrop.registerDropZone($el, clientProjectMeta);
               }
           "
           data-project-id="{{ $clientProject->id }}"
           data-pitch-id="{{ $producerPitch->id }}"
           data-item-type="client-project"
           data-workflow-type="client_management">
            <span class="block truncate max-w-full overflow-hidden">{{ $clientProject->name ?? 'Untitled' }}</span>
        </a>
        @else
        {{-- If no pitch exists, just show the link without drag & drop --}}
        <a href="{{ route('projects.manage-client', $clientProject) }}" wire:navigate 
           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition-colors {{ request()->route('project')?->id == $clientProject->id ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' : '' }}">
            <span class="block truncate max-w-full overflow-hidden">{{ $clientProject->name ?? 'Untitled' }}</span>
        </a>
        @endif
        @endforeach
        
        @if($counts['client_projects'] > 5)
        <a href="{{ route('dashboard') }}#client-projects" wire:navigate class="block px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 rounded-md transition-colors">
            View all {{ $counts['client_projects'] }} client projects →
        </a>
        @endif
    </div>
</div>
@endif

@endif
</div>
