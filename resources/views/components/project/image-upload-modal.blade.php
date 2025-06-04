@props([
    'show' => false,
    'project' => null,
    'uploading' => false,
    'imagePreviewUrl' => null
])

<!-- Image Upload Modal -->
<div x-data="{ 
    show: @entangle('showImageUploadModal'),
    uploading: @entangle('uploadingImage'),
    imagePreviewUrl: @entangle('imagePreviewUrl'),
    newProjectImage: @entangle('newProjectImage'),
    dragOver: false 
}" 
     x-show="show" 
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>
    
    <!-- Modal Content -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div @click.away="if (!uploading) $wire.hideImageUpload()" 
             class="relative w-full max-w-lg transform rounded-2xl bg-white/95 backdrop-blur-md p-6 shadow-2xl transition-all">
            
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center">
                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-2 mr-3">
                            <i class="fas fa-camera text-white"></i>
                        </div>
                        {{ $project && $project->image_path ? 'Update' : 'Add' }} Project Image
                    </h3>
                    <button @click="if (!uploading) $wire.hideImageUpload()" 
                            :disabled="uploading"
                            class="text-gray-400 hover:text-gray-600 p-2 rounded-full hover:bg-gray-100 transition-colors"
                            :class="{ 'opacity-50 cursor-not-allowed': uploading }">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                @if($project && $project->image_path)
                    <p class="text-sm text-gray-600 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        This will replace your current project image.
                    </p>
                @endif
            </div>

            <!-- Current Image (if exists) -->
            @if($project && $project->image_path)
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Image</label>
                    <div class="relative">
                        <img src="{{ $project->imageUrl }}" 
                             alt="{{ $project->name }}"
                             class="w-full h-32 object-cover rounded-lg border-2 border-gray-200">
                        <div class="absolute inset-0 bg-black/40 rounded-lg flex items-center justify-center">
                            <span class="text-white text-sm font-medium bg-black/60 px-3 py-1 rounded-full">
                                Will be replaced
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Upload Area -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    {{ $project && $project->image_path ? 'New' : 'Project' }} Image
                </label>
                
                <!-- Drag & Drop Area -->
                <div x-on:dragover.prevent="dragOver = true"
                     x-on:dragleave.prevent="dragOver = false"
                     x-on:drop.prevent="
                        dragOver = false;
                        const files = $event.dataTransfer.files;
                        if (files.length > 0) {
                            const file = files[0];
                            // Basic client-side validation
                            if (file.size > 5242880) { // 5MB
                                alert('File too large. Maximum size is 5MB.');
                                return;
                            }
                            if (!['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
                                alert('Invalid file type. Please select a JPG, PNG, GIF, or WebP image.');
                                return;
                            }
                            $wire.set('newProjectImage', file);
                        }
                     "
                     :class="{ 'border-blue-500 bg-blue-50': dragOver }"
                     class="relative border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-blue-400 hover:bg-blue-50/50 transition-all duration-200 cursor-pointer">
                    
                    <template x-if="imagePreviewUrl">
                        <!-- Image Preview -->
                        <div class="space-y-4">
                            <img :src="imagePreviewUrl" 
                                 alt="Preview" 
                                 class="mx-auto max-h-48 rounded-lg shadow-lg border-2 border-white">
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                Image ready to upload
                            </div>
                            <button type="button" 
                                    wire:click="$set('newProjectImage', null)"
                                    :disabled="uploading"
                                    class="text-sm text-gray-500 hover:text-red-600 transition-colors"
                                    :class="{ 'opacity-50 cursor-not-allowed': uploading }">
                                <i class="fas fa-times mr-1"></i>
                                Choose different image
                            </button>
                        </div>
                    </template>
                    <template x-if="!imagePreviewUrl">
                        <!-- Upload Prompt -->
                        <div class="space-y-4 pointer-events-none">
                            <div class="mx-auto w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-cloud-upload-alt text-white text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-lg font-medium text-gray-900 mb-2">
                                    Drop your image here, or click to browse
                                </p>
                                <p class="text-sm text-gray-500">
                                    JPG, PNG, GIF, or WebP up to 5MB
                                </p>
                            </div>
                        </div>
                    </template>
                    
                    <!-- Hidden File Input -->
                    <input type="file" 
                           id="image-upload-input"
                           wire:model.live="newProjectImage"
                           accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                           :disabled="uploading"
                           title="Click to select an image file">
                </div>
                
                <!-- Upload Progress -->
                <div x-show="uploading" 
                     x-transition
                     class="mt-4 space-y-3">
                    <div class="flex items-center text-sm text-blue-600">
                        <div class="animate-spin rounded-full h-4 w-4 border-2 border-blue-600 border-t-transparent mr-2"></div>
                        Uploading image...
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full transition-all duration-500"
                             style="width: 75%"></div>
                    </div>
                </div>
                
                <!-- Validation Errors -->
                @error('newProjectImage')
                    <div class="mt-3 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg p-3">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
                <button type="button" 
                        wire:click="hideImageUpload"
                        :disabled="uploading"
                        class="flex-1 px-4 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors"
                        :class="{ 'opacity-50 cursor-not-allowed': uploading }">
                    Cancel
                </button>
                
                @if($project && $project->image_path)
                    <button type="button" 
                            wire:click="removeProjectImage"
                            :disabled="uploading"
                            class="px-4 py-2.5 text-red-700 bg-red-100 hover:bg-red-200 rounded-lg font-medium transition-colors"
                            :class="{ 'opacity-50 cursor-not-allowed': uploading }">
                        <i class="fas fa-trash mr-2"></i>
                        Remove Image
                    </button>
                @endif
                
                <button type="button" 
                        wire:click="uploadProjectImage"
                        :disabled="uploading || !$wire.newProjectImage"
                        class="flex-1 px-4 py-2.5 text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 rounded-lg font-medium transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-blue-600 disabled:hover:to-purple-600">
                    <span x-show="!uploading">
                        <i class="fas fa-upload mr-2"></i>
                        {{ $project && $project->image_path ? 'Update' : 'Upload' }} Image
                    </span>
                    <span x-show="uploading" class="flex items-center justify-center">
                        <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                        Uploading...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:init', () => {
    // Handle file input change for better UX
    Livewire.on('project-image-updated', () => {
        // Reset file input
        const fileInput = document.getElementById('image-upload-input');
        if (fileInput) {
            fileInput.value = '';
        }
    });
});
</script> 