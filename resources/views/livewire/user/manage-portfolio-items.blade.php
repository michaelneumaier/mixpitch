<div>
    <!-- Background Effects -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-purple-400/20 to-indigo-600/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-tr from-indigo-400/20 to-blue-600/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 left-1/4 w-64 h-64 bg-gradient-to-r from-blue-300/10 to-purple-300/10 rounded-full blur-2xl"></div>
        <div class="absolute bottom-1/3 right-1/4 w-48 h-48 bg-gradient-to-l from-indigo-300/15 to-purple-300/15 rounded-full blur-xl"></div>
    </div>

    <div class="relative min-h-screen bg-gradient-to-br from-blue-50/30 via-white to-purple-50/30 py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Enhanced Header Section -->
        <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl overflow-hidden mb-8">
            <!-- Header Background Effects -->
            <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-br from-purple-50/30 via-indigo-50/20 to-blue-50/30"></div>
            <div class="absolute top-4 left-4 w-24 h-24 bg-purple-400/10 rounded-full blur-xl"></div>
            <div class="absolute top-4 right-4 w-16 h-16 bg-indigo-400/10 rounded-full blur-lg"></div>
            
            <div class="relative px-6 sm:px-8 py-8 z-10">
                <!-- Breadcrumb Navigation -->
                <div class="mb-6">
                    <nav class="flex items-center space-x-2 text-sm">
                        <a href="{{ route('profile.username', '@' . auth()->user()->username) }}" 
                           class="text-gray-600 hover:text-indigo-600 transition-colors duration-200 flex items-center">
                            <i class="fas fa-user-circle mr-1"></i>
                            My Profile
                        </a>
                        <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                        <span class="text-indigo-600 font-medium">Portfolio Management</span>
                    </nav>
                </div>

                <!-- Page Title and Stats -->
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <div class="flex-1">
                        <h1 class="text-3xl lg:text-4xl font-bold bg-gradient-to-r from-gray-900 via-indigo-800 to-purple-800 bg-clip-text text-transparent mb-3">
                            Portfolio Management
                        </h1>
                        <p class="text-lg text-gray-600 font-medium mb-6">Showcase your best work and manage your creative portfolio</p>
                        
                        <!-- Portfolio Stats -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm">
                                <div class="flex items-center">
                                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center mr-3 shadow-md">
                                        <i class="fas fa-images text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Total Items</p>
                                        <p class="text-xl font-bold text-gray-900">{{ count($portfolioItems ?? []) }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm">
                                <div class="flex items-center">
                                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center mr-3 shadow-md">
                                        <i class="fas fa-eye text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Public</p>
                                        <p class="text-xl font-bold text-gray-900">{{ collect($portfolioItems ?? [])->where('is_public', true)->count() }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm">
                                <div class="flex items-center">
                                    <div class="bg-gradient-to-r from-gray-500 to-slate-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center mr-3 shadow-md">
                                        <i class="fas fa-eye-slash text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Private</p>
                                        <p class="text-xl font-bold text-gray-900">{{ collect($portfolioItems ?? [])->where('is_public', false)->count() }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('profile.username', '@' . auth()->user()->username) }}" 
                           class="group inline-flex items-center justify-center px-6 py-3 bg-white/80 backdrop-blur-sm border border-white/30 text-gray-700 font-semibold rounded-xl shadow-lg hover:shadow-xl hover:bg-white/90 transition-all duration-200 hover:scale-105">
                            <i class="fas fa-external-link-alt mr-2 group-hover:scale-110 transition-transform"></i>
                            View Public Profile
                        </a>
                        <button wire:click="addItem()" 
                                class="group inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                            <i class="fas fa-plus mr-2 group-hover:scale-110 transition-transform"></i>
                            Add Portfolio Item
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Portfolio Items Section -->
        <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-lg overflow-hidden">
            <!-- Section Background -->
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/20 to-purple-50/20 rounded-2xl"></div>
            <div class="absolute top-4 right-4 w-20 h-20 bg-indigo-400/10 rounded-full blur-xl"></div>
            <div class="absolute bottom-4 left-4 w-16 h-16 bg-purple-400/10 rounded-full blur-lg"></div>
            
            <div class="relative p-6 sm:p-8">
                @if(count($portfolioItems ?? []) > 0)
                    <!-- Portfolio Grid Header -->
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-indigo-800 bg-clip-text text-transparent flex items-center">
                            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                                <i class="fas fa-th-large text-white text-sm"></i>
                            </div>
                            Your Portfolio Items
                        </h2>
                        <div class="text-sm text-gray-600 bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl px-4 py-2 shadow-sm">
                            <i class="fas fa-arrows-alt mr-2"></i>
                            Drag to reorder
                        </div>
                    </div>

                    <!-- Portfolio Items Grid -->
                    <div id="portfolio-sortable" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($portfolioItems as $item)
                            <div data-item-id="{{ $item->id }}" wire:key="portfolio-item-{{ $item->id }}" class="group bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl overflow-hidden hover:bg-white/80 hover:shadow-xl transition-all duration-300 cursor-move">
                                
                                <!-- Order Display Header -->
                                <div class="p-3 border-b border-white/30 bg-gradient-to-r from-indigo-50/50 to-purple-50/50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-grip-vertical text-gray-400 mr-2"></i>
                                            <span class="font-medium">Order: {{ $item->display_order }}</span>
                                        </div>
                                        <i class="fas fa-arrows-alt text-gray-400"></i>
                                    </div>
                                </div>
                            
                            <!-- Item Header -->
                            <div class="p-6 pb-4">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-gray-900 text-lg mb-1 truncate group-hover:text-indigo-600 transition-colors duration-200">
                                            {{ $item->title }}
                                        </h3>
                                    @if($item->description)
                                        <p class="text-gray-600 text-sm line-clamp-2 leading-relaxed">{{ $item->description }}</p>
                                    @endif
                                    </div>
                                </div>
                                
                                <!-- Item Type and Visibility Badges -->
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-sm border shadow-sm
                                        @if($item->item_type === \App\Models\PortfolioItem::TYPE_AUDIO) bg-blue-100/80 border-blue-200/50 text-blue-800 
                                        @elseif($item->item_type === \App\Models\PortfolioItem::TYPE_YOUTUBE) bg-red-100/80 border-red-200/50 text-red-800 
                                        @else bg-gray-100/80 border-gray-200/50 text-gray-800 @endif">
                                        @if($item->item_type === \App\Models\PortfolioItem::TYPE_AUDIO)
                                            <i class="fas fa-music mr-1"></i> Audio
                                        @elseif($item->item_type === \App\Models\PortfolioItem::TYPE_YOUTUBE)
                                            <i class="fab fa-youtube mr-1"></i> YouTube
                                        @else
                                        {{ ucfirst($item->item_type) }}
                                        @endif
                                    </span>
                                    
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-sm border shadow-sm
                                        @if($item->is_public) bg-green-100/80 border-green-200/50 text-green-800 
                                        @else bg-gray-100/80 border-gray-200/50 text-gray-800 @endif">
                                        @if($item->is_public)
                                            <i class="fas fa-eye mr-1"></i> Public
                                        @else
                                            <i class="fas fa-eye-slash mr-1"></i> Private
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <!-- Item Preview -->
                            <div class="px-6 pb-4">
                                @if($item->item_type === \App\Models\PortfolioItem::TYPE_AUDIO && $item->file_path)
                                    <div class="bg-gradient-to-r from-blue-100/80 to-indigo-100/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4 shadow-sm">
                                        <div class="flex items-center text-blue-700">
                                            <i class="fas fa-music text-lg mr-3"></i>
                                            <div>
                                                <p class="font-medium text-sm">Audio File</p>
                                                <p class="text-xs text-blue-600">{{ basename($item->file_path) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($item->item_type === \App\Models\PortfolioItem::TYPE_YOUTUBE && $item->video_id)
                                    <div class="bg-gradient-to-r from-red-100/80 to-pink-100/80 backdrop-blur-sm border border-red-200/50 rounded-xl p-4 shadow-sm">
                                        <div class="flex items-center text-red-700">
                                            <i class="fab fa-youtube text-lg mr-3"></i>
                                            <div>
                                                <p class="font-medium text-sm">YouTube Video</p>
                                                <p class="text-xs text-red-600">ID: {{ $item->video_id }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Item Actions -->
                            <div class="px-6 pb-6">
                                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-white/30">
                                        <button wire:click="editItem({{ $item->id }})" 
                                                data-no-drag
                                                class="group inline-flex items-center px-3 py-2 bg-indigo-100/80 hover:bg-indigo-200/80 text-indigo-700 hover:text-indigo-800 rounded-lg text-xs font-medium transition-all duration-200 hover:scale-105 shadow-sm">
                                            <i class="fas fa-edit mr-1 group-hover:scale-110 transition-transform"></i>
                                            Edit
                                    </button>
                                        <button wire:click="deleteItem({{ $item->id }})" 
                                                data-no-drag
                                                class="group inline-flex items-center px-3 py-2 bg-red-100/80 hover:bg-red-200/80 text-red-700 hover:text-red-800 rounded-lg text-xs font-medium transition-all duration-200 hover:scale-105 shadow-sm">
                                            <i class="fas fa-trash-alt mr-1 group-hover:scale-110 transition-transform"></i>
                                            Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                            @endforeach
                </div>
                @else
                    <!-- Enhanced Empty State -->
                    <div class="text-center py-16">
                        <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-2xl p-12 shadow-lg max-w-md mx-auto">
                            <!-- Animated Icon -->
                            <div class="relative mb-6">
                                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full p-6 w-24 h-24 mx-auto shadow-xl">
                                    <i class="fas fa-images text-white text-3xl"></i>
                                </div>
                                <div class="absolute -top-2 -right-2 bg-gradient-to-r from-purple-400 to-pink-500 rounded-full p-2 w-8 h-8 shadow-lg">
                                    <i class="fas fa-plus text-white text-xs"></i>
                                </div>
                    </div>
                            
                            <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-indigo-800 bg-clip-text text-transparent mb-3">
                                No Portfolio Items Yet
                            </h3>
                            <p class="text-gray-600 mb-8 leading-relaxed">
                                Start building your portfolio by adding your best work. Showcase audio files, YouTube videos, and more to attract potential clients.
                            </p>
                            
                            <button wire:click="addItem()" 
                                    class="group inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                <i class="fas fa-plus mr-3 group-hover:scale-110 transition-transform"></i>
                                Add Your First Portfolio Item
                    </button>
                        </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Add/Edit Form Modal -->
                @if($showForm)
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Background Effects -->
                <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-br from-indigo-50/30 via-purple-50/20 to-blue-50/30 rounded-t-2xl"></div>
                <div class="absolute top-4 left-4 w-16 h-16 bg-indigo-400/10 rounded-full blur-lg"></div>
                <div class="absolute top-4 right-4 w-12 h-12 bg-purple-400/10 rounded-full blur-md"></div>
                
                <div class="relative p-6 sm:p-8">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-indigo-800 bg-clip-text text-transparent flex items-center">
                            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                                <i class="fas fa-{{ $editingItemId ? 'edit' : 'plus' }} text-white text-sm"></i>
                            </div>
                            {{ $editingItemId ? 'Edit' : 'Add' }} Portfolio Item
                        </h3>
                        <button wire:click="resetForm()" 
                                class="group p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100/50 rounded-lg transition-all duration-200 hover:scale-110">
                            <i class="fas fa-times text-lg group-hover:scale-110 transition-transform"></i>
                        </button>
                    </div>

                    <!-- Form Content -->
                    <div x-data="{ 
                        currentType: @entangle('type').live,
                        init() {
                            this.$watch('currentType', value => {
                                // Type change handling
                            });
                        }
                    }" class="relative space-y-6">
                        
                        <!-- Form Loading Overlay -->
                        <div wire:loading wire:target="saveItem" class="absolute inset-0 bg-white/80 backdrop-blur-sm rounded-2xl z-10 flex items-center justify-center">
                            <div class="text-center">
                                <div class="bg-white/90 backdrop-blur-sm border border-white/30 rounded-2xl p-8 shadow-xl">
                                    <div class="flex items-center justify-center mb-4">
                                        <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Saving Portfolio Item</h4>
                                    <p class="text-sm text-gray-600">
                                        <span class="animate-pulse">Processing your upload...</span>
                                    </p>
                                    <div class="mt-4 bg-gray-200 rounded-full h-2 overflow-hidden">
                                        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 h-full rounded-full animate-pulse"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Fields Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Item Type -->
                            <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm">
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Item Type</label>
                                <select x-model="currentType"
                                    wire:model.live="type" 
                                        class="w-full rounded-xl border-white/30 bg-white/80 backdrop-blur-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200">
                                    <option value="{{\App\Models\PortfolioItem::TYPE_AUDIO}}">🎵 Audio File</option>
                                    <option value="{{\App\Models\PortfolioItem::TYPE_YOUTUBE}}">📺 YouTube Video</option>
                                </select>
                                @error('type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <!-- Title -->
                            <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm">
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Title</label>
                                <input type="text" 
                                       wire:model="title" 
                                       class="w-full rounded-xl border-white/30 bg-white/80 backdrop-blur-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200"
                                       placeholder="Enter a descriptive title">
                                @error('title') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Description (Optional)</label>
                            <textarea wire:model="description" 
                                      rows="3"
                                      class="w-full rounded-xl border-white/30 bg-white/80 backdrop-blur-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200"
                                      placeholder="Describe your work, the process, or any relevant details..."></textarea>
                            @error('description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Audio File Section -->
                        <div x-show="currentType === '{{\App\Models\PortfolioItem::TYPE_AUDIO}}'" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-6 shadow-sm">
                            
                            <label class="block text-sm font-semibold text-gray-700 mb-4">Audio File (MP3, WAV - Max 100MB)</label>
                            
                            @if($editingItemId && $existingFilePath)
                                <div class="mb-4 p-4 bg-blue-100/80 backdrop-blur-sm border border-blue-200/50 rounded-xl">
                                    <div class="flex items-center text-blue-700">
                                        <i class="fas fa-music mr-3"></i>
                                    <div>
                                            <p class="font-medium">Current file: {{ basename($existingFilePath) }}</p>
                                            <p class="text-sm text-blue-600">Upload a new file to replace the current one</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- File Upload Area -->
                            <div class="relative">
                                <label for="audioFile" class="cursor-pointer block">
                                    <div class="border-2 border-dashed rounded-xl p-8 text-center transition-all duration-200 hover:border-indigo-400 hover:bg-indigo-50/50
                                        {{ $audioFile ? 'border-green-400 bg-green-50/50' : 'border-gray-300' }}">
                                        @if($audioFile)
                                            <i class="fas fa-check-circle text-green-500 text-3xl mb-3"></i>
                                            <p class="text-green-700 font-medium">{{ $audioFile->getClientOriginalName() }}</p>
                                            <p class="text-sm text-green-600 mt-1">File ready for upload</p>
                                        @else
                                            <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-3"></i>
                                            <p class="text-gray-600 font-medium">Click to select audio file</p>
                                            <p class="text-sm text-gray-500 mt-1">or drag and drop here</p>
                                        @endif
                                    </div>
                                    <input id="audioFile" type="file" wire:model.live="audioFile" class="hidden" accept=".mp3,.wav">
                                </label>
                                
                                <div wire:loading wire:target="audioFile" class="absolute inset-0 bg-white/80 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                    <div class="text-indigo-600 text-center">
                                        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                        <p class="font-medium">Uploading...</p>
                                    </div>
                            </div>
                            </div>
                            @error('audioFile') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- YouTube Video Section -->
                        <div x-show="currentType === '{{\App\Models\PortfolioItem::TYPE_YOUTUBE}}'" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-6 shadow-sm">
                            
                            <label class="block text-sm font-semibold text-gray-700 mb-4">YouTube Video URL</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fab fa-youtube text-red-500 text-lg"></i>
                                </div>
                                <input type="url" 
                                       wire:model.live="video_url" 
                                       class="w-full pl-12 rounded-xl border-white/30 bg-white/80 backdrop-blur-sm shadow-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all duration-200"
                                       placeholder="https://www.youtube.com/watch?v=... or https://youtu.be/...">
                            </div>
                            @error('video_url') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Visibility Toggle -->
                        <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-6 shadow-sm">
                            <div class="flex items-start space-x-4">
                                <div class="flex items-center h-6">
                                    <input id="isPublic" 
                                           type="checkbox" 
                                           wire:model="isPublic" 
                                           class="h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 focus:ring-2">
                                </div>
                                <div class="flex-1">
                                    <label for="isPublic" class="text-sm font-semibold text-gray-700">Show publicly on profile</label>
                                    <p class="text-sm text-gray-600 mt-1">When enabled, this item will be visible to anyone who views your profile</p>
                                </div>
                            </div>
                            @error('isPublic') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end gap-4 pt-6 border-t border-white/30">
                            <button wire:click="resetForm()" 
                                    wire:loading.attr="disabled"
                                    wire:target="saveItem"
                                    class="inline-flex items-center px-6 py-3 bg-white/80 backdrop-blur-sm border border-white/30 text-gray-700 font-semibold rounded-xl shadow-lg hover:shadow-xl hover:bg-white/90 transition-all duration-200 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                                Cancel
                            </button>
                            
                            <!-- Save Button with Loading States -->
                            <button wire:click="saveItem()" 
                                    wire:loading.attr="disabled"
                                    wire:target="saveItem"
                                    class="group relative inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 disabled:opacity-75 disabled:cursor-not-allowed disabled:hover:scale-100 min-w-[140px]">
                                
                                <!-- Normal State -->
                                <span wire:loading.remove wire:target="saveItem" class="flex items-center">
                                    <i class="fas fa-save mr-2 group-hover:scale-110 transition-transform"></i>
                                    Save Item
                                </span>
                                
                                <!-- Loading State -->
                                <span wire:loading wire:target="saveItem" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="animate-pulse">Saving...</span>
                                </span>
                            </button>
                        </div>
                    </div>
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
