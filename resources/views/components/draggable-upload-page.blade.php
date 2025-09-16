@props(['model', 'title' => null, 'disabled' => false])

@php
    // Generate metadata for the global drag drop system
    $meta = [
        'modelType' => get_class($model),
        'modelId' => $model->id,
        'context' => $model instanceof \App\Models\Project ? 'projects' : ($model instanceof \App\Models\Pitch ? 'pitches' : 'global'),
        'modelLabel' => $model instanceof \App\Models\Project ? 'Project' : ($model instanceof \App\Models\Pitch ? 'Pitch' : 'Model'),
    ];

    // Add additional context information
    if ($model instanceof \App\Models\Project) {
        $meta['projectTitle'] = $model->title ?? null;
        $meta['workflowType'] = $model->workflow_type ?? null;
    } elseif ($model instanceof \App\Models\Pitch) {
        $meta['pitchTitle'] = $model->project->title ?? null;
        $meta['projectId'] = $model->project_id ?? null;
        $meta['pitchStatus'] = $model->status ?? null;
        $meta['workflowType'] = $model->project->workflow_type ?? null;
        
        // Add context for client management workflow
        if ($model->project->workflow_type === 'client_management') {
            $meta['isClientManagement'] = true;
            $meta['clientName'] = $model->project->client_name ?? null;
        }
    }

    // Override title if provided
    if ($title) {
        $meta['pageTitle'] = $title;
    }
@endphp

<div 
    x-data="{
        dragMeta: @js($meta),
        isDisabled: @js($disabled),
        
        init() {
            if (!this.isDisabled) {
                // Enable global page drag & drop when component initializes
                this.$nextTick(() => {
                    if (window.GlobalDragDrop) {
                        window.GlobalDragDrop.enablePageDragDrop(this.dragMeta);
                        console.log('Drag drop enabled for page with meta:', this.dragMeta);
                    } else {
                        console.warn('GlobalDragDrop not yet available, retrying...');
                        // Retry after a short delay
                        setTimeout(() => {
                            if (window.GlobalDragDrop) {
                                window.GlobalDragDrop.enablePageDragDrop(this.dragMeta);
                                console.log('Drag drop enabled for page (delayed) with meta:', this.dragMeta);
                            } else {
                                console.error('GlobalDragDrop still not available after retry');
                            }
                        }, 500);
                    }
                });
            }
        },
        
        // Method to update metadata if needed
        updateMeta(newMeta) {
            this.dragMeta = { ...this.dragMeta, ...newMeta };
            if (!this.isDisabled) {
                window.GlobalDragDrop?.enablePageDragDrop(this.dragMeta);
            }
        },
        
        // Method to disable/enable drag drop
        toggleDragDrop(enabled = null) {
            this.isDisabled = enabled !== null ? !enabled : !this.isDisabled;
            if (this.isDisabled) {
                window.GlobalDragDrop?.disablePageDragDrop();
            } else {
                window.GlobalDragDrop?.enablePageDragDrop(this.dragMeta);
            }
        }
    }"
    x-init="init()"
    class="draggable-upload-page"
    x-cloak
>
    <!-- Page content slot -->
    {{ $slot }}
    
    <!-- Debug info (only in development) -->
    @if(config('app.debug') && request()->has('debug-drag'))
        <div class="fixed bottom-4 right-4 bg-black/80 text-white p-3 rounded-lg text-xs max-w-sm z-50">
            <div class="font-semibold mb-2">Drag Drop Debug</div>
            <div>Model: <span x-text="dragMeta.modelLabel"></span></div>
            <div>ID: <span x-text="dragMeta.modelId"></span></div>
            <div>Context: <span x-text="dragMeta.context"></span></div>
            <div>Disabled: <span x-text="isDisabled"></span></div>
            @if(isset($meta['projectTitle']))
                <div>Project: <span x-text="dragMeta.projectTitle"></span></div>
            @endif
            @if(isset($meta['workflowType']))
                <div>Workflow: <span x-text="dragMeta.workflowType"></span></div>
            @endif
        </div>
    @endif
</div>

{{-- Global drag drop manager is loaded in the main layout --}}

<!-- Add page-specific styling for drag states -->
@once
    @push('styles')
        <style>
            .draggable-upload-page {
                position: relative;
                min-height: 100vh;
            }
            
            /* Smooth transitions for drag states */
            .draggable-upload-page * {
                transition: background-color 0.2s ease-out, border-color 0.2s ease-out;
            }
            
            /* Visual feedback when dragging over the page */
            body.dragging .draggable-upload-page {
                background-color: rgba(59, 130, 246, 0.02);
            }
            
            /* Prevent text selection during drag operations */
            .draggable-upload-page.dragging {
                user-select: none;
                pointer-events: none;
            }
            
            /* Re-enable pointer events for the drag overlay */
            .draggable-upload-page.dragging #global-drag-overlay {
                pointer-events: auto;
            }
            
            /* Hide scrollbars during drag to prevent visual glitches */
            body.dragging {
                overflow: hidden;
            }
        </style>
    @endpush
@endonce

@script
<script>
    // Add body class for global drag state
    window.addEventListener('global-drag-drop:show', () => {
        document.body.classList.add('dragging');
    });

    window.addEventListener('global-drag-drop:hide', () => {
        document.body.classList.remove('dragging');
    });

    // Listen for Alpine component initialization to ensure proper setup
    document.addEventListener('alpine:initialized', () => {
        // Ensure the global drag drop manager is available
        if (window.GlobalDragDrop) {
            console.log('GlobalDragDrop is available and ready');
        } else {
            console.error('GlobalDragDrop not available. Check if global-drag-drop-manager.js is loaded.');
        }
    });
</script>
@endscript