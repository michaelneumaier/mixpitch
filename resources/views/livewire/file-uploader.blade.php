<div x-data="{
    isUploading: false,
    uploadProgress: @entangle('uploadProgress').defer // Defer entanglement for performance
}"
    x-on:livewire-upload-start="isUploading = true"
    x-on:livewire-upload-finish="isUploading = false"
    x-on:livewire-upload-error="isUploading = false"
    x-on:livewire-upload-progress="isUploading = true"
>
    <div class="mb-4">
        <div class="flex flex-col">
            <label class="mb-1.5 sm:mb-2 text-sm sm:text-base text-gray-700">Upload files</label>
            <div class="flex flex-col">
                <div class="flex flex-col sm:flex-row gap-2 mb-2">
                    <div class="flex-grow min-w-0 overflow-hidden">
                        <label for="fileInput-{{ $this->getId() }}"
                            class="flex flex-col items-center justify-center w-full h-14 sm:h-16 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div wire:loading wire:target="file" class="w-full h-full flex items-center justify-center">
                                <span class="loading loading-spinner loading-sm text-gray-400"></span>
                                <span class="text-xs sm:text-sm text-gray-500 ml-2">Processing file...</span>
                            </div>
                            <div wire:loading.remove wire:target="file" class="flex items-center justify-center">
                                <i class="fas fa-cloud-upload-alt text-gray-400 mr-2"></i>
                                <span class="text-xs sm:text-sm text-gray-500">
                                    Click to add audio, PDF, or image files
                                </span>
                            </div>
                            <input type="file" id="fileInput-{{ $this->getId() }}" class="hidden" wire:model="file" />
                        </label>
                        @error('file') <span class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex-shrink-0">
                        <button wire:click="saveFile" wire:loading.attr="disabled" wire:target="saveFile, file"
                            class="btn bg-primary hover:bg-primary-focus text-white w-full h-14 sm:h-16 sm:h-full"
                            @if(empty($file) || $errors->any()) disabled @endif>
                            <span wire:loading wire:target="saveFile" class="loading loading-spinner loading-xs mr-2"></span>
                            <i wire:loading.remove wire:target="saveFile" class="fas fa-upload mr-2"></i> Upload File
                        </button>
                    </div>
                </div>

                @if($file)
                <div class="bg-base-200/50 p-2.5 sm:p-3 rounded-lg mb-3">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-medium text-sm sm:text-base">Selected file</h4>
                        <button wire:click="$set('file', null); $set('uploadProgress', []);" 
                                wire:loading.attr="disabled" wire:target="file, saveFile"
                                class="text-red-500 hover:text-red-700 transition-colors text-xs sm:text-sm">
                            Clear
                        </button>
                    </div>
                    <div class="space-y-1.5 sm:space-y-2 max-h-36 sm:max-h-48 overflow-y-auto pr-2">
                        <div class="flex items-center justify-between bg-white p-1.5 sm:p-2 rounded-md">
                            <div class="flex items-center flex-1 min-w-0">
                                <i class="fas fa-file-alt text-purple-500 mr-1.5 sm:mr-2"></i>
                                <div class="truncate flex-1 text-xs sm:text-sm">
                                    {{ $file->getClientOriginalName() }}
                                    <span class="text-2xs sm:text-xs text-gray-500 ml-1">({{ $this->formatFileSize($file->getSize()) }})</span>
                                    
                                    {{-- Progress Bar or Error --}}
                                    @if($file && is_object($file))
                                        @php
                                            try {
                                                $tempFilename = $file->getFilename();
                                            } catch (\Exception $e) {
                                                $tempFilename = '';
                                            }
                                        @endphp
                                        @if($tempFilename && isset($uploadProgress[$tempFilename]))
                                            @if(is_numeric($uploadProgress[$tempFilename]))
                                                <div class="w-full bg-gray-200 rounded-full h-1 mt-1">
                                                    <div class="bg-blue-500 h-1 rounded-full" style="width: {{ $uploadProgress[$tempFilename] }}%"></div>
                                                </div>
                                                <span class="text-xs text-blue-600">Uploading... {{ $uploadProgress[$tempFilename] }}%</span>
                                            @elseif(is_string($uploadProgress[$tempFilename]) && Str::startsWith($uploadProgress[$tempFilename], 'Error:'))
                                                <div class="text-xs text-red-600 mt-1 font-medium">
                                                    {{ $uploadProgress[$tempFilename] }}
                                                </div>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <button wire:click="$set('file', null)" 
                                    wire:loading.attr="disabled" wire:target="file, saveFile"
                                    class="text-red-500 hover:text-red-700 transition-colors ml-2 p-1">
                                <i class="fas fa-times text-sm sm:text-base"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
