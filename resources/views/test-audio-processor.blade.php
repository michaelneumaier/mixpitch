@extends('components.layouts.app')

@section('content')
<div class="container mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold mb-6">AWS Lambda Audio Processor Test</h1>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Configuration</h2>
        <div class="mb-4">
            <p class="mb-2"><strong>Lambda URL:</strong> <code id="lambda-url" class="bg-gray-100 px-2 py-1 rounded">{{ $lambdaUrl ?: 'Not configured' }}</code></p>
            <p class="mb-2"><strong>Status:</strong> <span id="lambda-status" class="px-2 py-1 rounded {{ $lambdaUrl ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $lambdaUrl ? 'Configured' : 'Not configured' }}</span></p>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Test with existing files -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Test with Existing Files</h2>
            
            @if(count($files) > 0)
                <div class="mb-4">
                    <label for="file-select" class="block text-sm font-medium text-gray-700 mb-2">Select an audio file:</label>
                    <select id="file-select" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select a file...</option>
                        @foreach($files as $file)
                            <option value="{{ $file->id }}">{{ $file->file_name }} ({{ $file->formatted_size }})</option>
                        @endforeach
                    </select>
                </div>
                
                <button id="test-button" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Test Lambda Function
                </button>
                
                <div id="test-result" class="mt-4 hidden">
                    <h3 class="font-medium text-lg mb-2">Result:</h3>
                    <div id="test-output" class="bg-gray-100 p-4 rounded-md overflow-auto max-h-60"></div>
                </div>
                
                <div id="visualization-container" class="mt-6 hidden">
                    <h3 class="font-medium text-lg mb-2">Waveform Visualization:</h3>
                    <div id="waveform" class="bg-gray-100 p-4 h-32 rounded-md"></div>
                    <div class="mt-2 text-sm text-gray-500 flex justify-between">
                        <span>0:00</span>
                        <span id="duration-display">0:00</span>
                    </div>
                </div>
            @else
                <p>No audio files found. Please upload some files first.</p>
            @endif
        </div>
        
        <!-- Test with upload -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Test with File Upload</h2>
            
            <form id="upload-form" class="mb-4">
                <div class="mb-4">
                    <label for="audio-file" class="block text-sm font-medium text-gray-700 mb-2">Upload an audio file:</label>
                    <input type="file" id="audio-file" name="audio_file" accept="audio/mp3,audio/wav" class="block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-md file:border-0
                        file:text-sm file:font-semibold
                        file:bg-blue-50 file:text-blue-700
                        hover:file:bg-blue-100">
                    <p class="mt-1 text-sm text-gray-500">Accepted formats: MP3, WAV (max 100MB)</p>
                </div>
                
                <button type="submit" id="upload-button" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Upload and Test
                </button>
            </form>
            
            <div id="upload-result" class="mt-4 hidden">
                <h3 class="font-medium text-lg mb-2">Result:</h3>
                <div id="upload-output" class="bg-gray-100 p-4 rounded-md overflow-auto max-h-60"></div>
            </div>
            
            <div id="upload-visualization-container" class="mt-6 hidden">
                <h3 class="font-medium text-lg mb-2">Waveform Visualization:</h3>
                <div id="upload-waveform" class="bg-gray-100 p-4 h-32 rounded-md"></div>
                <div class="mt-2 text-sm text-gray-500 flex justify-between">
                    <span>0:00</span>
                    <span id="upload-duration-display">0:00</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/wavesurfer.js@6.6.4/dist/wavesurfer.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const testButton = document.getElementById('test-button');
        const fileSelect = document.getElementById('file-select');
        const testResult = document.getElementById('test-result');
        const testOutput = document.getElementById('test-output');
        const uploadForm = document.getElementById('upload-form');
        const uploadButton = document.getElementById('upload-button');
        const uploadResult = document.getElementById('upload-result');
        const uploadOutput = document.getElementById('upload-output');
        const visualizationContainer = document.getElementById('visualization-container');
        const uploadVisualizationContainer = document.getElementById('upload-visualization-container');
        const durationDisplay = document.getElementById('duration-display');
        const uploadDurationDisplay = document.getElementById('upload-duration-display');
        
        // Format seconds to MM:SS
        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }
        
        // Function to create waveform visualization
        function createWaveformVisualization(containerId, peaksData, duration) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
            
            // Create canvas
            const canvas = document.createElement('canvas');
            canvas.width = container.offsetWidth;
            canvas.height = container.offsetHeight;
            container.appendChild(canvas);
            
            const ctx = canvas.getContext('2d');
            const width = canvas.width;
            const height = canvas.height;
            
            // Draw background
            ctx.fillStyle = '#f3f4f6';
            ctx.fillRect(0, 0, width, height);
            
            // Draw waveform if we have data
            if (peaksData && peaksData.length > 0) {
                ctx.fillStyle = '#4f46e5';
                
                const barWidth = width / peaksData.length;
                const centerY = height / 2;
                
                for (let i = 0; i < peaksData.length; i++) {
                    const peak = peaksData[i];
                    const x = i * barWidth;
                    
                    // Draw negative peak
                    const negativeHeight = Math.abs(peak[0]) * height;
                    ctx.fillRect(x, centerY, barWidth - 1, negativeHeight);
                    
                    // Draw positive peak
                    const positiveHeight = Math.abs(peak[1]) * height;
                    ctx.fillRect(x, centerY - positiveHeight, barWidth - 1, positiveHeight);
                }
            } else {
                // No data, draw message
                ctx.fillStyle = '#6b7280';
                ctx.font = '14px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('No waveform data available', width / 2, height / 2);
            }
            
            // Update duration display
            if (containerId === 'waveform') {
                durationDisplay.textContent = formatTime(duration);
            } else {
                uploadDurationDisplay.textContent = formatTime(duration);
            }
        }
        
        // Test with existing file
        if (testButton) {
            testButton.addEventListener('click', function() {
                const fileId = fileSelect.value;
                
                if (!fileId) {
                    alert('Please select a file to test');
                    return;
                }
                
                testButton.disabled = true;
                testButton.textContent = 'Processing...';
                testResult.classList.add('hidden');
                visualizationContainer.classList.add('hidden');
                
                fetch(`/test-audio-processor/test/${fileId}`)
                    .then(response => response.json())
                    .then(data => {
                        testButton.disabled = false;
                        testButton.textContent = 'Test Lambda Function';
                        testResult.classList.remove('hidden');
                        
                        // Show the JSON result
                        testOutput.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                        
                        // If successful, show visualization
                        if (data.success && data.visualize_data) {
                            visualizationContainer.classList.remove('hidden');
                            createWaveformVisualization(
                                'waveform', 
                                data.visualize_data.peaks, 
                                data.visualize_data.duration
                            );
                        }
                    })
                    .catch(error => {
                        testButton.disabled = false;
                        testButton.textContent = 'Test Lambda Function';
                        testResult.classList.remove('hidden');
                        testOutput.innerHTML = `<pre class="text-red-500">Error: ${error.message}</pre>`;
                    });
            });
        }
        
        // Test with file upload
        if (uploadForm) {
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const fileInput = document.getElementById('audio-file');
                if (!fileInput.files.length) {
                    alert('Please select a file to upload');
                    return;
                }
                
                const formData = new FormData();
                formData.append('audio_file', fileInput.files[0]);
                
                uploadButton.disabled = true;
                uploadButton.textContent = 'Uploading...';
                uploadResult.classList.add('hidden');
                uploadVisualizationContainer.classList.add('hidden');
                
                fetch('/test-audio-processor/upload', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    uploadButton.disabled = false;
                    uploadButton.textContent = 'Upload and Test';
                    uploadResult.classList.remove('hidden');
                    
                    // Show the JSON result
                    uploadOutput.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                    
                    // If successful, show visualization
                    if (data.success && data.visualize_data) {
                        uploadVisualizationContainer.classList.remove('hidden');
                        createWaveformVisualization(
                            'upload-waveform', 
                            data.visualize_data.peaks, 
                            data.visualize_data.duration
                        );
                    }
                })
                .catch(error => {
                    uploadButton.disabled = false;
                    uploadButton.textContent = 'Upload and Test';
                    uploadResult.classList.remove('hidden');
                    uploadOutput.innerHTML = `<pre class="text-red-500">Error: ${error.message}</pre>`;
                });
            });
        }
    });
</script>
@endsection 