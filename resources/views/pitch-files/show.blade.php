<!-- resources/views/pitch-files/show.blade.php -->

@extends('components.layouts.app')

@section('content')
<!-- Background Effects Container -->
<div class="relative min-h-screen bg-gradient-to-br from-purple-50/30 via-white to-indigo-50/30">
    <!-- Floating Blur Circles for Audio Theme -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-10 w-32 h-32 bg-purple-400/20 rounded-full blur-2xl"></div>
        <div class="absolute bottom-20 right-10 w-24 h-24 bg-indigo-400/20 rounded-full blur-xl"></div>
        <div class="absolute top-1/2 left-1/4 w-16 h-16 bg-pink-400/15 rounded-full blur-xl"></div>
        <div class="absolute bottom-1/3 left-3/4 w-20 h-20 bg-blue-400/15 rounded-full blur-xl"></div>
    </div>

    <!-- Main Container -->
    <div class="relative container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-center">
            <div class="w-full lg:w-4/5 2xl:w-3/4">
                
                <!-- Enhanced Page Header -->
                <div class="mb-8">
                    <div class="flex items-center mb-6">
                        <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl mr-4 shadow-lg">
                            <i class="fas fa-file-audio text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">
                                Audio File Viewer
                            </h1>
                            <p class="text-gray-600 text-lg">High-quality audio playback and analysis</p>
                        </div>
                    </div>
                    
                    <!-- Enhanced Breadcrumb Navigation -->
                    <nav class="bg-gradient-to-r from-gray-50/80 to-purple-50/80 backdrop-blur-sm border border-gray-200/50 rounded-xl p-4 shadow-lg">
                        <div class="flex items-center space-x-2 text-sm">
                            <a href="{{ route('dashboard') }}" class="text-purple-600 hover:text-purple-800 transition-colors font-medium">
                                <i class="fas fa-home mr-1"></i>Dashboard
                            </a>
                            <i class="fas fa-chevron-right text-gray-400"></i>
                            <a href="#" class="text-purple-600 hover:text-purple-800 transition-colors font-medium">
                                <i class="fas fa-folder-open mr-1"></i>Audio Files
                            </a>
                            <i class="fas fa-chevron-right text-gray-400"></i>
                            <span class="text-gray-700 font-semibold">{{ $file->original_name ?? 'Audio File' }}</span>
                        </div>
                    </nav>
                </div>

                <!-- Enhanced Audio Player Card -->
                <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl overflow-hidden mb-8">
                    <!-- Gradient Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-r from-purple-600/5 via-indigo-600/5 to-purple-600/5"></div>
                    
                    <div class="relative z-10 p-6 lg:p-8">
                        <!-- File Information Header -->
                        <div class="mb-6">
                            <div class="flex items-center mb-4">
                                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl mr-3">
                                    <i class="fas fa-music text-white"></i>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">
                                        {{ $file->original_name ?? 'Audio File' }}
                                    </h2>
                                    <p class="text-gray-600">Professional audio file</p>
                                </div>
                            </div>
                            
                            <!-- File Metadata Row -->
                            <div class="bg-gradient-to-r from-purple-50/80 to-indigo-50/80 backdrop-blur-sm border border-purple-200/50 rounded-xl p-4">
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                                    <div class="flex items-center">
                                        <i class="fas fa-clock text-purple-600 mr-2"></i>
                                        <div>
                                            <div class="font-medium text-gray-700">Duration</div>
                                            <div class="text-gray-600" id="file-duration">Loading...</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-file text-indigo-600 mr-2"></i>
                                        <div>
                                            <div class="font-medium text-gray-700">File Size</div>
                                            <div class="text-gray-600">{{ number_format($file->size / 1024, 1) }} KB</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar text-purple-600 mr-2"></i>
                                        <div>
                                            <div class="font-medium text-gray-700">Uploaded</div>
                                            <div class="text-gray-600">{{ $file->created_at->format('M j, Y') }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-code text-indigo-600 mr-2"></i>
                                        <div>
                                            <div class="font-medium text-gray-700">Format</div>
                                            <div class="text-gray-600">{{ strtoupper(pathinfo($file->filename, PATHINFO_EXTENSION)) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Enhanced Audio Player Container -->
                        <div class="bg-gradient-to-br from-white/80 to-purple-50/80 backdrop-blur-sm border border-purple-200/50 rounded-2xl p-6 shadow-lg">
                            <livewire:pitch-file-player :file="$file" />
                        </div>
                    </div>
                </div>

                <!-- File Details & Actions Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- File Information Card -->
                    <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-r from-purple-600/5 to-indigo-600/5"></div>
                        <div class="relative z-10 p-6">
                            <div class="flex items-center mb-4">
                                <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg mr-3">
                                    <i class="fas fa-info-circle text-white text-sm"></i>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">File Information</h3>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Original Name:</span>
                                    <span class="font-medium text-gray-900">{{ $file->original_name ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">File Path:</span>
                                    <span class="font-medium text-gray-900 truncate ml-2">{{ basename($file->filename) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">MIME Type:</span>
                                    <span class="font-medium text-gray-900">{{ $file->mime_type ?? 'audio/*' }}</span>
                                </div>
                                @if($file->note)
                                <div class="pt-2 border-t border-purple-200/50">
                                    <span class="text-gray-600 text-sm">Note:</span>
                                    <p class="text-gray-900 mt-1">{{ $file->note }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Audio Properties Card -->
                    <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 to-purple-600/5"></div>
                        <div class="relative z-10 p-6">
                            <div class="flex items-center mb-4">
                                <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg mr-3">
                                    <i class="fas fa-wave-square text-white text-sm"></i>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Audio Properties</h3>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Duration:</span>
                                    <span class="font-medium text-gray-900" id="audio-duration">Loading...</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Quality:</span>
                                    <span class="font-medium text-gray-900">High Definition</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Channels:</span>
                                    <span class="font-medium text-gray-900">Stereo</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Status:</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>Ready
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Card -->
                    <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-r from-indigo-600/5 to-purple-600/5"></div>
                        <div class="relative z-10 p-6">
                            <div class="flex items-center mb-4">
                                <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg mr-3">
                                    <i class="fas fa-cog text-white text-sm"></i>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Actions</h3>
                            </div>
                            <div class="space-y-3">
                                <!-- Download Button -->
                                <a href="{{ route('pitch-files.download', $file) }}" 
                                   class="w-full inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                    <i class="fas fa-download mr-2"></i> Download File
                                </a>
                                
                                <!-- Share Button -->
                                <button onclick="copyToClipboard(window.location.href)" 
                                        class="w-full inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                    <i class="fas fa-share mr-2"></i> Share Link
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Navigation & Back Buttons -->
                <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-gray-600/5 via-purple-600/5 to-gray-600/5"></div>
                    <div class="relative z-10 p-6">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div class="text-center sm:text-left">
                                <h4 class="text-lg font-bold text-gray-900 mb-1">Need to go back?</h4>
                                <p class="text-gray-600 text-sm">Return to your previous location or dashboard</p>
                            </div>
                            
                            <div class="flex gap-3">
                                <button onclick="window.history.back()" 
                                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                    <i class="fas fa-arrow-left mr-2"></i> Go Back
                                </button>
                                
                                <a href="{{ route('dashboard') }}" 
                                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Enhanced WaveSurfer Styling */
    #waveform {
        opacity: 0;
        transition: all 0.3s ease-in-out;
        border-radius: 12px;
        overflow: hidden;
        background: linear-gradient(to right, #f8fafc, #f1f5f9);
    }

    #waveform.loaded {
        opacity: 1;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    /* Enhanced Timeline Styling */
    #waveform-timeline {
        position: relative;
        height: 24px;
        margin-top: 12px;
        background: linear-gradient(to right, #e2e8f0, #cbd5e1);
        border-radius: 8px;
        padding: 4px 8px;
    }

    .timeline-mark {
        position: absolute;
        top: 2px;
        font-size: 11px;
        font-weight: 600;
        background: linear-gradient(to bottom, #6366f1, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        transform: translateX(-50%);
    }

    .timeline-container {
        background: linear-gradient(to right, #f8fafc, #f1f5f9);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px;
        margin-top: 8px;
    }

    /* Loading Animation */
    .audio-loading {
        background: linear-gradient(90deg, #f0f2f5 25%, #e4e6ea 50%, #f0f2f5 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }

    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Enhanced Button Hover Effects */
    .hover-lift {
        transition: transform 0.2s ease-in-out;
    }

    .hover-lift:hover {
        transform: translateY(-2px);
    }
</style>
@endpush

@push('scripts')
<!-- WaveSurfer.js library -->
<script src="https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.min.js"></script>

<script>
    // Enhanced functionality for the audio file viewer
    document.addEventListener('DOMContentLoaded', function() {
        // Copy to clipboard functionality
        window.copyToClipboard = function(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const button = event.target.closest('button');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check mr-2"></i> Copied!';
                button.classList.add('bg-green-600');
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('bg-green-600');
                }, 2000);
            });
        };

        // Listen for WaveSurfer ready event to update duration
        document.addEventListener('wavesurfer-ready', function(event) {
            const duration = event.detail.duration;
            const formattedDuration = formatDuration(duration);
            
            // Update duration displays
            const durationElements = document.querySelectorAll('#file-duration, #audio-duration');
            durationElements.forEach(el => {
                el.textContent = formattedDuration;
            });
        });

        // Format duration helper function
        function formatDuration(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }
    });
</script>
@endpush