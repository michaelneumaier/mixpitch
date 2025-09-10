<div class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 min-h-screen">
    <div class="mx-auto px-2 md:py-2">
        <div class="mx-auto">

            <!-- Compact Dashboard Header -->
            <flux:card class="mb-2 bg-white/50">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <flux:heading size="lg" class="bg-gradient-to-r from-gray-900 via-indigo-800 to-purple-800 dark:from-gray-100 dark:via-indigo-300 dark:to-purple-300 bg-clip-text text-transparent">
                        Portfolio Management
                    </flux:heading>
                    
                    <div class="flex items-center gap-2">
                        <flux:button href="{{ route('profile.username', '@' . auth()->user()->username) }}" icon="eye" variant="outline" size="xs">
                            View Profile
                        </flux:button>
                        <flux:button wire:click="addItem()" icon="plus" variant="primary" size="xs">
                            Add Item
                        </flux:button>
                    </div>
                </div>
                
                <flux:subheading class="text-slate-600 dark:text-slate-400">
                    Showcase your best work and manage your creative portfolio
                </flux:subheading>
            </flux:card>

            <!-- Portfolio Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <flux:card class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-sm">
                            <flux:icon name="photo" class="text-white" size="sm" />
                        </div>
                        <div>
                            <div class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Items</div>
                            <div class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ count($portfolioItems ?? []) }}</div>
                        </div>
                    </div>
                </flux:card>
                
                <flux:card class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gradient-to-r from-emerald-500 to-green-600 rounded-lg shadow-sm">
                            <flux:icon name="eye" class="text-white" size="sm" />
                        </div>
                        <div>
                            <div class="text-sm font-medium text-slate-600 dark:text-slate-400">Public</div>
                            <div class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ collect($portfolioItems ?? [])->where('is_public', true)->count() }}</div>
                        </div>
                    </div>
                </flux:card>
                
                <flux:card class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gradient-to-r from-gray-500 to-slate-600 rounded-lg shadow-sm">
                            <flux:icon name="eye-slash" class="text-white" size="sm" />
                        </div>
                        <div>
                            <div class="text-sm font-medium text-slate-600 dark:text-slate-400">Private</div>
                            <div class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ collect($portfolioItems ?? [])->where('is_public', false)->count() }}</div>
                        </div>
                    </div>
                </flux:card>
            </div>

            <!-- Portfolio Items Section -->
            <flux:card>
                @if(count($portfolioItems ?? []) > 0)
                    <!-- Portfolio Grid Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-sm">
                                <flux:icon name="squares-2x2" class="text-white" size="lg" />
                            </div>
                            <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Your Portfolio Items</flux:heading>
                        </div>
                        <flux:badge color="blue" size="sm">
                            <flux:icon name="arrows-pointing-in" size="xs" class="mr-1" />
                            Drag to reorder
                        </flux:badge>
                    </div>

                    <!-- Portfolio Items Grid -->
                    <div id="portfolio-sortable" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($portfolioItems as $item)
                        <flux:card data-item-id="{{ $item->id }}" wire:key="portfolio-item-{{ $item->id }}" class="cursor-move hover:shadow-lg transition-all duration-300">
                            <!-- Order Display Header -->
                            <div class="flex items-center justify-between p-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                                <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                    <flux:icon name="bars-3" size="xs" class="text-slate-400" />
                                    <span class="font-medium">Order: {{ $item->display_order }}</span>
                                </div>
                                <flux:icon name="arrows-pointing-in" size="xs" class="text-slate-400" />
                            </div>
                            
                            <!-- Item Content -->
                            <div class="p-4">
                                <div class="mb-3">
                                    <flux:heading size="sm" class="mb-1 truncate group-hover:text-indigo-600 transition-colors duration-200">
                                        {{ $item->title }}
                                    </flux:heading>
                                    @if($item->description)
                                        <flux:text size="sm" class="text-slate-600 dark:text-slate-400 line-clamp-2">{{ $item->description }}</flux:text>
                                    @endif
                                </div>
                                
                                <!-- Item Type and Visibility Badges -->
                                <div class="flex items-center gap-2 mb-4">
                                    <flux:badge 
                                        :color="match($item->item_type) {
                                            \App\Models\PortfolioItem::TYPE_AUDIO => 'blue',
                                            \App\Models\PortfolioItem::TYPE_YOUTUBE => 'red',
                                            default => 'gray'
                                        }"
                                        size="sm"
                                    >
                                        @if($item->item_type === \App\Models\PortfolioItem::TYPE_AUDIO)
                                            Audio
                                        @elseif($item->item_type === \App\Models\PortfolioItem::TYPE_YOUTUBE)
                                            YouTube
                                        @else
                                            {{ ucfirst($item->item_type) }}
                                        @endif
                                    </flux:badge>
                                    
                                    <flux:badge 
                                        :color="$item->is_public ? 'green' : 'gray'"
                                        size="sm"
                                    >
                                        {{ $item->is_public ? 'Public' : 'Private' }}
                                    </flux:badge>
                                </div>

                                <!-- Item Preview -->
                                @if($item->item_type === \App\Models\PortfolioItem::TYPE_AUDIO && $item->file_path)
                                    <flux:callout color="blue" size="sm" class="mb-4">
                                        <div class="flex items-center gap-3">
                                            <flux:icon name="musical-note" size="lg" />
                                            <div>
                                                <div class="font-medium text-sm">Audio File</div>
                                                <div class="text-xs opacity-75">{{ basename($item->file_path) }}</div>
                                            </div>
                                        </div>
                                    </flux:callout>
                                @elseif($item->item_type === \App\Models\PortfolioItem::TYPE_YOUTUBE && $item->video_id)
                                    <flux:callout color="red" size="sm" class="mb-4">
                                        <div class="flex items-center gap-3">
                                            <flux:icon name="play" size="lg" />
                                            <div>
                                                <div class="font-medium text-sm">YouTube Video</div>
                                                <div class="text-xs opacity-75">ID: {{ $item->video_id }}</div>
                                            </div>
                                        </div>
                                    </flux:callout>
                                @endif

                                <!-- Item Actions -->
                                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-700">
                                    <flux:button wire:click="editItem({{ $item->id }})" data-no-drag icon="pencil" variant="outline" size="xs">
                                        Edit
                                    </flux:button>
                                    <flux:button wire:click="deleteItem({{ $item->id }})" data-no-drag icon="trash" variant="danger" size="xs">
                                        Delete
                                    </flux:button>
                                </div>
                            </div>
                        </flux:card>
                        @endforeach
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="text-center py-12">
                        <div class="mb-4">
                            <flux:icon name="photo" class="mx-auto text-slate-400 dark:text-slate-500" size="2xl" />
                        </div>
                        <flux:heading size="lg" class="mb-2">No Portfolio Items Yet</flux:heading>
                        <flux:text class="text-slate-600 dark:text-slate-400 mb-6 max-w-md mx-auto">
                            Start building your portfolio by adding your best work. Showcase audio files, YouTube videos, and more to attract potential clients.
                        </flux:text>
                        
                        <flux:button wire:click="addItem()" icon="plus" variant="primary">
                            Add Your First Portfolio Item
                        </flux:button>
                    </div>
                @endif
            </flux:card>

            <!-- Add/Edit Form Modal -->
            @if($showForm)
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-sm">
                                <flux:icon name="{{ $editingItemId ? 'pencil' : 'plus' }}" class="text-white" size="sm" />
                            </div>
                            <flux:heading size="lg">{{ $editingItemId ? 'Edit' : 'Add' }} Portfolio Item</flux:heading>
                        </div>
                        <flux:button wire:click="resetForm()" icon="x-mark" variant="ghost" size="sm" />
                    </div>
                    
                    <!-- Modal Body -->
                    <div class="p-6">

                    <div x-data="{ 
                        currentType: @entangle('type').live,
                        init() {
                            this.$watch('currentType', value => {
                                // Type change handling
                            });
                        }
                    }" class="space-y-6">
                        
                        <!-- Form Loading Overlay -->
                        <div wire:loading wire:target="saveItem" class="absolute inset-0 bg-white/80 backdrop-blur-sm rounded-lg z-10 flex items-center justify-center">
                            <div class="text-center">
                                <flux:icon name="arrow-path" class="animate-spin mx-auto mb-4 text-indigo-600" size="lg" />
                                <flux:heading size="base" class="mb-2">Saving Portfolio Item</flux:heading>
                                <flux:text size="sm" class="animate-pulse">Processing your upload...</flux:text>
                            </div>
                        </div>
                        
                        <!-- Form Fields Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Item Type -->
                            <flux:field>
                                <flux:label>Item Type</flux:label>
                                <select x-model="currentType" wire:model.live="type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                                    <option value="{{\App\Models\PortfolioItem::TYPE_AUDIO}}">🎵 Audio File</option>
                                    <option value="{{\App\Models\PortfolioItem::TYPE_YOUTUBE}}">📺 YouTube Video</option>
                                </select>
                                <flux:error name="type" />
                            </flux:field>

                            <!-- Title -->
                            <flux:field>
                                <flux:label>Title</flux:label>
                                <flux:input wire:model="title" placeholder="Enter a descriptive title" />
                                <flux:error name="title" />
                            </flux:field>
                        </div>

                        <!-- Description -->
                        <flux:field>
                            <flux:label>Description (Optional)</flux:label>
                            <flux:textarea wire:model="description" rows="3" placeholder="Describe your work, the process, or any relevant details..." />
                            <flux:error name="description" />
                        </flux:field>

                        <!-- Audio File Section -->
                        <div x-show="currentType === '{{\App\Models\PortfolioItem::TYPE_AUDIO}}'" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100">
                            
                            <flux:field>
                                <flux:label>Audio File (MP3, WAV - Max 100MB)</flux:label>
                                
                                @if($editingItemId && $existingFilePath)
                                    <flux:callout color="blue" class="mb-4">
                                        <div class="flex items-center gap-3">
                                            <flux:icon name="musical-note" />
                                            <div>
                                                <div class="font-medium">Current file: {{ basename($existingFilePath) }}</div>
                                                <div class="text-sm opacity-75">Upload a new file to replace the current one</div>
                                            </div>
                                        </div>
                                    </flux:callout>
                                @endif
                                
                                <!-- File Upload Area -->
                                <div class="relative">
                                    <label for="audioFile" class="cursor-pointer block">
                                        <div class="border-2 border-dashed rounded-lg p-8 text-center transition-all duration-200 hover:border-indigo-400 hover:bg-indigo-50/50
                                            {{ $audioFile ? 'border-green-400 bg-green-50/50' : 'border-gray-300' }}">
                                            @if($audioFile)
                                                <flux:icon name="check-circle" class="text-green-500 mx-auto mb-3" size="xl" />
                                                <flux:text class="text-green-700 font-medium">{{ $audioFile->getClientOriginalName() }}</flux:text>
                                                <flux:text size="sm" class="text-green-600 mt-1">File ready for upload</flux:text>
                                            @else
                                                <flux:icon name="cloud-arrow-up" class="text-gray-400 mx-auto mb-3" size="xl" />
                                                <flux:text class="text-gray-600 font-medium">Click to select audio file</flux:text>
                                                <flux:text size="sm" class="text-gray-500 mt-1">or drag and drop here</flux:text>
                                            @endif
                                        </div>
                                        <input id="audioFile" type="file" wire:model.live="audioFile" class="hidden" accept=".mp3,.wav">
                                    </label>
                                    
                                    <div wire:loading wire:target="audioFile" class="absolute inset-0 bg-white/80 backdrop-blur-sm rounded-lg flex items-center justify-center">
                                        <div class="text-indigo-600 text-center">
                                            <flux:icon name="arrow-path" class="animate-spin mx-auto mb-2" size="lg" />
                                            <flux:text class="font-medium">Uploading...</flux:text>
                                        </div>
                                    </div>
                                </div>
                                <flux:error name="audioFile" />
                            </flux:field>
                        </div>

                        <!-- YouTube Video Section -->
                        <div x-show="currentType === '{{\App\Models\PortfolioItem::TYPE_YOUTUBE}}'" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100">
                            
                            <flux:field>
                                <flux:label>YouTube Video URL</flux:label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <flux:icon name="play" class="text-red-500" />
                                    </div>
                                    <flux:input type="url" wire:model.live="video_url" class="pl-10" placeholder="https://www.youtube.com/watch?v=... or https://youtu.be/..." />
                                </div>
                                <flux:error name="video_url" />
                            </flux:field>
                        </div>

                        <!-- Visibility Toggle -->
                        <flux:field>
                            <div class="flex items-start gap-4">
                                <flux:checkbox wire:model="isPublic" />
                                <div>
                                    <flux:label>Show publicly on profile</flux:label>
                                    <flux:text size="sm" class="text-slate-600 dark:text-slate-400">When enabled, this item will be visible to anyone who views your profile</flux:text>
                                </div>
                            </div>
                            <flux:error name="isPublic" />
                        </flux:field>

                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="flex justify-end gap-4 p-6 border-t border-gray-200 dark:border-gray-700">
                        <flux:button wire:click="resetForm()" wire:loading.attr="disabled" wire:target="saveItem" variant="ghost">
                            Cancel
                        </flux:button>
                        
                        <flux:button wire:click="saveItem()" wire:loading.attr="disabled" wire:target="saveItem" variant="primary">
                            <span wire:loading.remove wire:target="saveItem">Save Item</span>
                            <span wire:loading wire:target="saveItem" class="flex items-center gap-2">
                                <flux:icon name="arrow-path" class="animate-spin" size="sm" />
                                Saving...
                            </span>
                        </flux:button>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>


<!-- Simple CSS for drag states -->
<style>
    /* Prevent text selection during drag operations */
    [wire\:sortable] {
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }
    
    /* Portfolio grid with smooth transitions */
    #portfolio-sortable {
        transition: all 0.3s ease;
    }
    
    /* Sortable item styling with enhanced transitions */
    #portfolio-sortable > div {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: move;
        transform-origin: center;
    }
    
    /* Enhanced visual feedback during drag */
    #portfolio-sortable .sortable-ghost {
        opacity: 0.4;
        transform: scale(1.05) rotate(2deg);
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(168, 85, 247, 0.15));
        border: 2px dashed rgba(99, 102, 241, 0.4);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    #portfolio-sortable .sortable-chosen {
        transform: scale(1.03);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        z-index: 1000;
        cursor: grabbing;
    }
    
    /* Smooth hover transitions */
    #portfolio-sortable > div:hover:not(.sortable-chosen):not(.sortable-ghost) {
        transform: translateY(-2px) scale(1.01);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    
    /* Prevent dragging on action buttons */
    [data-no-drag] {
        cursor: pointer !important;
    }
    
    /* Loading state for smoother transitions */
    .sortable-fallback {
        opacity: 0.6;
        transform: scale(1.05);
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
        border-radius: 12px;
    }
    
    /* Animation for items settling into place */
    @keyframes settle {
        0% { transform: scale(1.02); }
        50% { transform: scale(0.98); }
        100% { transform: scale(1); }
    }
    
    .item-settling {
        animation: settle 0.4s ease-out;
    }
</style>

<!-- SortableJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing manual SortableJS');
    
    // Initialize SortableJS manually
    function initializeSortable() {
        const container = document.getElementById('portfolio-sortable');
        
        if (!container || typeof Sortable === 'undefined') {
            console.log('Container or SortableJS not found, retrying in 100ms');
            setTimeout(initializeSortable, 100);
                        return;
                    }
                    
        console.log('Creating Sortable instance on:', container);
        
        const sortable = Sortable.create(container, {
            animation: 300,
            easing: "cubic-bezier(0.4, 0, 0.2, 1)",
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            fallbackClass: 'sortable-fallback',
            filter: '[data-no-drag]',
            preventOnFilter: false,
            swapThreshold: 0.65,
            forceFallback: false,
            
            onStart: function(evt) {
                console.log('Drag started');
                // Add visual feedback when dragging starts
                container.style.transform = 'scale(1.01)';
                
                // Add subtle highlighting to other items
                Array.from(container.children).forEach(child => {
                    if (child !== evt.item) {
                        child.style.opacity = '0.7';
                        child.style.transform = 'scale(0.98)';
                    }
                });
            },
            
            onMove: function(evt) {
                // Add visual feedback during drag
                const related = evt.related;
                if (related && !related.classList.contains('sortable-ghost')) {
                    related.style.transform = 'translateY(-5px) scale(1.02)';
                    setTimeout(() => {
                        if (related) {
                            related.style.transform = '';
                        }
                    }, 200);
                }
            },
            
            onEnd: function(evt) {
                console.log('Drag ended:', evt);
                
                // Reset container styling
                container.style.transform = '';
                
                // Reset all items styling and add settling animation
                Array.from(container.children).forEach(child => {
                    child.style.opacity = '';
                    child.style.transform = '';
                    child.classList.add('item-settling');
                    
                    // Remove settling class after animation
                    setTimeout(() => {
                        child.classList.remove('item-settling');
                    }, 400);
                });
                
                // Get new order of items
                const itemIds = Array.from(container.children).map(item => {
                    return parseInt(item.getAttribute('data-item-id'));
                });
                
                console.log('New order:', itemIds);
                
                // Add a brief loading state
                container.style.pointerEvents = 'none';
                container.style.opacity = '0.8';
                
                // Call Livewire method
                if (window.Livewire) {
                    console.log('Calling Livewire updateSort with:', itemIds);
                    // Find the Livewire component
                    const component = window.Livewire.find(container.closest('[wire\\:id]').getAttribute('wire:id'));
                    if (component) {
                        component.call('updateSort', itemIds).then(() => {
                            // Reset loading state after successful update
                 setTimeout(() => {
                                container.style.pointerEvents = '';
                                container.style.opacity = '';
                     }, 300);
                        }).catch(() => {
                            // Reset loading state even if there's an error
                            container.style.pointerEvents = '';
                            container.style.opacity = '';
                        });
                    } else {
                        console.error('Could not find Livewire component');
                        container.style.pointerEvents = '';
                        container.style.opacity = '';
                    }
                } else {
                    console.error('Livewire not available');
                    container.style.pointerEvents = '';
                    container.style.opacity = '';
                }
            }
        });
        
        console.log('SortableJS initialized successfully:', sortable);
    }
    
    // Initialize immediately if DOM is ready, otherwise wait for Livewire
    if (document.getElementById('portfolio-sortable')) {
        initializeSortable();
    } else {
        // Wait for Livewire to render the component
        document.addEventListener('livewire:initialized', function() {
            setTimeout(initializeSortable, 100);
        });
        
        // Fallback for older Livewire versions
        document.addEventListener('livewire:load', function() {
            setTimeout(initializeSortable, 100);
        });
    }
    
    // Re-initialize after Livewire updates
    document.addEventListener('livewire:navigated', function() {
        setTimeout(initializeSortable, 100);
            });
        });
    </script>
    </div>
