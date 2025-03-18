<div>
    <div class="mt-2 p-2 bg-white dark:bg-gray-800 shadow rounded-xl">
    <div class="p-4">
        <h2 class="text-xl font-bold tracking-tight">
            {{ config('app.name') }} - Music Collaboration Platform
        </h2>
        
        <p class="mt-2 text-gray-500 dark:text-gray-400">
            Welcome to the administrative dashboard. Here you can manage all aspects of the platform including projects, pitches, users, and settings.
        </p>
    </div>
</div>
@if ($this->hasHeaderWidgets())
    <x-filament-widgets::widgets
        :columns="$this->getHeaderWidgetsColumns()"
        :widgets="$this->getHeaderWidgets()"
        :data="$this->getHeaderWidgetsData()"
    />
@endif
<x-filament-widgets::widgets
    :columns="$this->getColumns()"
    :widgets="$this->getWidgets()"
    :data="$this->getWidgetData()"
/>






@if ($this->hasFooterWidgets())
    <x-filament-widgets::widgets
        :columns="$this->getFooterWidgetsColumns()"
        :widgets="$this->getFooterWidgets()"
        :data="$this->getFooterWidgetsData()"
    />
@endif 
</div>