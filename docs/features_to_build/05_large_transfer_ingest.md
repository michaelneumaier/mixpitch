# Large Transfer Ingest Implementation Plan

## Overview

Enhance MixPitch's upload capabilities to handle professional-grade audio files and large project folders through resumable uploads, intelligent chunking, progress tracking, and batch processing. This eliminates the frustration of failed uploads and enables seamless handling of high-quality audio content.

## UX/UI Implementation

### Enhanced Upload Interface

**Location**: Replace/enhance existing Uppy file uploader  
**Current**: Basic file upload with limited resumability  
**New**: Professional-grade upload experience with advanced progress tracking

```blade
{{-- Enhanced large file upload interface --}}
<div class="space-y-6">
    {{-- Upload zone with drag-drop and folder support --}}
    <div 
        id="uppy-upload-area"
        class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:border-indigo-400 transition-colors"
        x-data="{ dragover: false }"
        @dragover.prevent="dragover = true"
        @dragleave.prevent="dragover = false"
        @drop.prevent="dragover = false"
        :class="{ 'border-indigo-400 bg-indigo-50': dragover }"
    >
        <div class="space-y-4">
            <div class="mx-auto h-16 w-16 text-slate-400">
                <flux:icon name="cloud-upload" size="lg" />
            </div>
            
            <div>
                <h3 class="text-lg font-medium text-slate-900">Upload Files or Folders</h3>
                <p class="text-sm text-slate-600 mt-1">
                    Drag and drop files here, or click to browse
                </p>
            </div>
            
            <div class="flex items-center justify-center space-x-4">
                <flux:button id="uppy-browse-button" variant="primary">
                    <flux:icon name="folder" size="sm" />
                    Choose Files
                </flux:button>
                
                <flux:button id="uppy-folder-button" variant="outline">
                    <flux:icon name="folder-open" size="sm" />
                    Choose Folder
                </flux:button>
            </div>
            
            <div class="text-xs text-slate-500 space-y-1">
                <p>Maximum file size: {{ $maxFileSize }}MB per file</p>
                <p>Supported formats: WAV, FLAC, AIFF, MP3, ZIP, PDF</p>
                <p>✓ Resumable uploads ✓ Folder structure preserved ✓ Background processing</p>
            </div>
        </div>
    </div>
    
    {{-- Upload queue and progress --}}
    <div id="uppy-progress-area" class="hidden">
        <flux:card>
            <div class="p-4 border-b">
                <div class="flex items-center justify-between">
                    <h3 class="font-medium">Upload Progress</h3>
                    <div class="flex items-center space-x-2">
                        <flux:button 
                            id="uppy-pause-all" 
                            size="sm" 
                            variant="outline"
                        >
                            <flux:icon name="pause" size="sm" />
                            Pause All
                        </flux:button>
                        
                        <flux:button 
                            id="uppy-cancel-all" 
                            size="sm" 
                            variant="outline"
                        >
                            <flux:icon name="x" size="sm" />
                            Cancel All
                        </flux:button>
                    </div>
                </div>
                
                {{-- Overall progress --}}
                <div class="mt-3">
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-slate-600">Overall Progress</span>
                        <span id="overall-progress-text" class="font-medium">0%</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2">
                        <div 
                            id="overall-progress-bar"
                            class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                            style="width: 0%"
                        ></div>
                    </div>
                </div>
            </div>
            
            {{-- Individual file progress --}}
            <div id="uppy-file-list" class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                {{-- Dynamically populated by Uppy --}}
            </div>
        </flux:card>
    </div>
    
    {{-- Network quality indicator --}}
    <div class="flex items-center justify-between text-sm text-slate-600">
        <div class="flex items-center space-x-2">
            <div id="network-indicator" class="flex items-center space-x-1">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                <span id="network-status">Good Connection</span>
            </div>
            <span>•</span>
            <span id="upload-speed">Upload Speed: --</span>
        </div>
        
        <div class="flex items-center space-x-2">
            <span>Chunk Size:</span>
            <span id="current-chunk-size" class="font-medium">Auto</span>
        </div>
    </div>
</div>
```

### Upload Settings Dashboard

```blade
{{-- Admin upload settings interface --}}
<flux:card class="p-6">
    <h3 class="text-lg font-semibold mb-4">Large File Upload Settings</h3>
    
    <div class="space-y-6">
        {{-- Chunk size configuration --}}
        <flux:field>
            <flux:label>Default Chunk Size</flux:label>
            <flux:select wire:model.defer="settings.default_chunk_size">
                <option value="1048576">1 MB (Slow connections)</option>
                <option value="5242880">5 MB (Standard)</option>
                <option value="10485760">10 MB (Fast connections)</option>
                <option value="20971520">20 MB (Very fast connections)</option>
                <option value="auto">Auto-detect based on connection</option>
            </flux:select>
            <flux:text size="sm" class="text-slate-500">
                Larger chunks = faster uploads but less resilient to connection issues
            </flux:text>
        </flux:field>
        
        {{-- Concurrent uploads --}}
        <flux:field>
            <flux:label>Maximum Concurrent Uploads</flux:label>
            <flux:input 
                type="number" 
                wire:model.defer="settings.max_concurrent_uploads"
                min="1" 
                max="10"
            />
            <flux:text size="sm" class="text-slate-500">
                Number of files that can upload simultaneously
            </flux:text>
        </flux:field>
        
        {{-- Retry configuration --}}
        <flux:field>
            <flux:label>Retry Settings</flux:label>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <flux:label size="sm">Max Retries</flux:label>
                    <flux:input 
                        type="number" 
                        wire:model.defer="settings.max_retries"
                        min="0" 
                        max="10"
                    />
                </div>
                <div>
                    <flux:label size="sm">Retry Delay (seconds)</flux:label>
                    <flux:input 
                        type="number" 
                        wire:model.defer="settings.retry_delay"
                        min="1" 
                        max="60"
                    />
                </div>
            </div>
        </flux:field>
        
        {{-- Connection quality settings --}}
        <flux:field>
            <flux:label>Adaptive Chunking</flux:label>
            <div class="space-y-3">
                <div class="flex items-center space-x-2">
                    <flux:checkbox wire:model.defer="settings.adaptive_chunking" />
                    <flux:label>Enable adaptive chunk sizing based on connection quality</flux:label>
                </div>
                
                @if($settings['adaptive_chunking'] ?? false)
                    <div class="pl-6 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Slow connection (&lt; 1 Mbps):</span>
                            <span class="font-medium">1 MB chunks</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Medium connection (1-10 Mbps):</span>
                            <span class="font-medium">5 MB chunks</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Fast connection (&gt; 10 Mbps):</span>
                            <span class="font-medium">10+ MB chunks</span>
                        </div>
                    </div>
                @endif
            </div>
        </flux:field>
    </div>
    
    <div class="mt-6">
        <flux:button wire:click="saveSettings" variant="primary">
            Save Upload Settings
        </flux:button>
    </div>
</flux:card>
```

### Upload Analytics Dashboard

```blade
{{-- Upload performance analytics --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <flux:card class="p-4">
        <div class="flex items-center">
            <div class="p-2 bg-indigo-100 rounded-lg">
                <flux:icon name="cloud-upload" class="h-6 w-6 text-indigo-600" />
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-slate-600">Total Uploads Today</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['uploads_today'] }}</p>
            </div>
        </div>
    </flux:card>
    
    <flux:card class="p-4">
        <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
                <flux:icon name="check-circle" class="h-6 w-6 text-green-600" />
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-slate-600">Success Rate</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['success_rate'] }}%</p>
            </div>
        </div>
    </flux:card>
    
    <flux:card class="p-4">
        <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
                <flux:icon name="clock" class="h-6 w-6 text-blue-600" />
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-slate-600">Avg Upload Time</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['avg_upload_time'] }}</p>
            </div>
        </div>
    </flux:card>
    
    <flux:card class="p-4">
        <div class="flex items-center">
            <div class="p-2 bg-purple-100 rounded-lg">
                <flux:icon name="database" class="h-6 w-6 text-purple-600" />
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-slate-600">Data Transferred</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['data_transferred'] }}</p>
            </div>
        </div>
    </flux:card>
</div>
```

## Database Schema

### New Table: `upload_sessions`

```php
Schema::create('upload_sessions', function (Blueprint $table) {
    $table->id();
    $table->string('session_id')->unique(); // UUID for tracking
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
    $table->string('upload_type'); // 'project_files', 'pitch_files', 'profile_images', etc.
    $table->json('file_metadata'); // Original filenames, sizes, types
    $table->integer('total_files');
    $table->bigInteger('total_size_bytes');
    $table->enum('status', ['pending', 'uploading', 'processing', 'completed', 'failed', 'cancelled']);
    $table->integer('files_completed')->default(0);
    $table->bigInteger('bytes_uploaded')->default(0);
    $table->float('progress_percentage')->default(0);
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->json('upload_settings')->nullable(); // Chunk size, concurrent uploads, etc.
    $table->json('performance_metrics')->nullable(); // Upload speeds, retry counts, etc.
    $table->text('error_message')->nullable();
    $table->timestamps();
    
    $table->index(['user_id', 'status']);
    $table->index(['project_id', 'status']);
    $table->index(['session_id']);
    $table->index(['status', 'started_at']);
});
```

### New Table: `upload_chunks`

```php
Schema::create('upload_chunks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('upload_session_id')->constrained()->onDelete('cascade');
    $table->string('file_identifier'); // Unique ID for the file within session
    $table->string('chunk_id'); // UUID for this specific chunk
    $table->integer('chunk_number'); // 0, 1, 2, etc.
    $table->integer('total_chunks'); // Total expected chunks for this file
    $table->bigInteger('chunk_size');
    $table->bigInteger('start_byte');
    $table->bigInteger('end_byte');
    $table->string('checksum', 64); // SHA-256 of chunk content
    $table->enum('status', ['pending', 'uploading', 'completed', 'failed']);
    $table->string('storage_path')->nullable(); // Temporary storage location
    $table->string('s3_upload_id')->nullable(); // S3 multipart upload ID
    $table->string('s3_etag')->nullable(); // S3 ETag for this chunk
    $table->integer('retry_count')->default(0);
    $table->timestamp('uploaded_at')->nullable();
    $table->json('metadata')->nullable(); // Additional chunk metadata
    $table->timestamps();
    
    $table->index(['upload_session_id', 'file_identifier', 'chunk_number']);
    $table->index(['chunk_id']);
    $table->index(['status']);
    $table->unique(['upload_session_id', 'file_identifier', 'chunk_number']);
});
```

### New Table: `connection_quality_metrics`

```php
Schema::create('connection_quality_metrics', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('session_id');
    $table->float('download_speed_mbps')->nullable(); // Measured download speed
    $table->float('upload_speed_mbps')->nullable(); // Measured upload speed
    $table->integer('latency_ms')->nullable(); // Round-trip time
    $table->float('packet_loss_percentage')->default(0);
    $table->string('connection_type')->nullable(); // 'wifi', 'cellular', 'ethernet'
    $table->integer('recommended_chunk_size');
    $table->json('test_results')->nullable(); // Full speed test results
    $table->timestamp('measured_at');
    $table->timestamps();
    
    $table->index(['user_id', 'measured_at']);
    $table->index(['session_id']);
});
```

### Extend `project_files` table

```php
Schema::table('project_files', function (Blueprint $table) {
    $table->string('upload_session_id')->nullable()->after('metadata');
    $table->json('upload_metrics')->nullable()->after('upload_session_id'); // Speed, retries, etc.
    $table->boolean('is_resumable_upload')->default(false)->after('upload_metrics');
    $table->string('folder_path')->nullable()->after('is_resumable_upload'); // Preserve folder structure
    
    $table->index('upload_session_id');
    $table->index(['project_id', 'folder_path']);
});
```

## Service Layer Architecture

### New Service: `LargeFileUploadService`

```php
<?php

namespace App\Services;

use App\Models\UploadSession;
use App\Models\UploadChunk;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class LargeFileUploadService
{
    protected ConnectionQualityService $connectionService;
    protected ChunkManagementService $chunkService;
    protected FileManagementService $fileService;
    
    public function __construct(
        ConnectionQualityService $connectionService,
        ChunkManagementService $chunkService,
        FileManagementService $fileService
    ) {
        $this->connectionService = $connectionService;
        $this->chunkService = $chunkService;
        $this->fileService = $fileService;
    }
    
    public function initializeUploadSession(
        User $user,
        array $files,
        ?Project $project = null,
        string $uploadType = 'project_files'
    ): UploadSession {
        
        $sessionId = Str::uuid()->toString();
        $totalSize = collect($files)->sum('size');
        $totalFiles = count($files);
        
        // Get optimal upload settings for user's connection
        $uploadSettings = $this->connectionService->getOptimalUploadSettings($user);
        
        $session = UploadSession::create([
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'project_id' => $project?->id,
            'upload_type' => $uploadType,
            'file_metadata' => $files,
            'total_files' => $totalFiles,
            'total_size_bytes' => $totalSize,
            'status' => 'pending',
            'upload_settings' => $uploadSettings,
            'started_at' => now(),
        ]);
        
        // Pre-generate chunk information for all files
        $this->prepareChunksForSession($session, $files);
        
        Log::info('Upload session initialized', [
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'project_id' => $project?->id,
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'upload_settings' => $uploadSettings,
        ]);
        
        return $session;
    }
    
    public function processChunkUpload(
        string $sessionId,
        string $fileIdentifier,
        int $chunkNumber,
        string $chunkData,
        array $metadata = []
    ): array {
        
        $session = UploadSession::where('session_id', $sessionId)->firstOrFail();
        
        if ($session->status === 'cancelled') {
            throw new \Exception('Upload session has been cancelled');
        }
        
        $chunk = UploadChunk::where('upload_session_id', $session->id)
            ->where('file_identifier', $fileIdentifier)
            ->where('chunk_number', $chunkNumber)
            ->firstOrFail();
            
        try {
            // Validate chunk data
            $expectedSize = $chunk->chunk_size;
            $actualSize = strlen($chunkData);
            
            if ($actualSize !== $expectedSize && $chunkNumber < $chunk->total_chunks - 1) {
                throw new \Exception("Chunk size mismatch. Expected: {$expectedSize}, Got: {$actualSize}");
            }
            
            // Verify checksum
            $calculatedChecksum = hash('sha256', $chunkData);
            if ($calculatedChecksum !== $chunk->checksum) {
                throw new \Exception('Chunk checksum verification failed');
            }
            
            // Store chunk using appropriate strategy
            $result = $this->chunkService->storeChunk($chunk, $chunkData, $metadata);
            
            // Update chunk status
            $chunk->update([
                'status' => 'completed',
                'storage_path' => $result['storage_path'],
                's3_upload_id' => $result['s3_upload_id'] ?? null,
                's3_etag' => $result['s3_etag'] ?? null,
                'uploaded_at' => now(),
            ]);
            
            // Update session progress
            $this->updateSessionProgress($session);
            
            // Check if file is complete
            $fileComplete = $this->checkFileCompletion($session, $fileIdentifier);
            
            if ($fileComplete) {
                $this->finalizeFile($session, $fileIdentifier);
            }
            
            return [
                'success' => true,
                'chunk_id' => $chunk->chunk_id,
                'file_complete' => $fileComplete,
                'session_progress' => $session->fresh()->progress_percentage,
            ];
            
        } catch (\Exception $e) {
            $chunk->increment('retry_count');
            $chunk->update(['status' => 'failed']);
            
            Log::error('Chunk upload failed', [
                'session_id' => $sessionId,
                'file_identifier' => $fileIdentifier,
                'chunk_number' => $chunkNumber,
                'error' => $e->getMessage(),
                'retry_count' => $chunk->retry_count,
            ]);
            
            throw $e;
        }
    }
    
    protected function prepareChunksForSession(UploadSession $session, array $files): void
    {
        $chunkSize = $session->upload_settings['chunk_size'] ?? 5242880; // 5MB default
        
        foreach ($files as $fileIndex => $file) {
            $fileIdentifier = Str::uuid()->toString();
            $fileSize = $file['size'];
            $totalChunks = (int) ceil($fileSize / $chunkSize);
            
            for ($chunkNumber = 0; $chunkNumber < $totalChunks; $chunkNumber++) {
                $startByte = $chunkNumber * $chunkSize;
                $endByte = min($startByte + $chunkSize - 1, $fileSize - 1);
                $currentChunkSize = $endByte - $startByte + 1;
                
                UploadChunk::create([
                    'upload_session_id' => $session->id,
                    'file_identifier' => $fileIdentifier,
                    'chunk_id' => Str::uuid()->toString(),
                    'chunk_number' => $chunkNumber,
                    'total_chunks' => $totalChunks,
                    'chunk_size' => $currentChunkSize,
                    'start_byte' => $startByte,
                    'end_byte' => $endByte,
                    'checksum' => $file['chunks'][$chunkNumber]['checksum'] ?? '',
                    'status' => 'pending',
                ]);
            }
        }
    }
    
    protected function updateSessionProgress(UploadSession $session): void
    {
        $totalChunks = $session->uploadChunks()->count();
        $completedChunks = $session->uploadChunks()->where('status', 'completed')->count();
        
        $progressPercentage = $totalChunks > 0 ? ($completedChunks / $totalChunks) * 100 : 0;
        
        $session->update([
            'progress_percentage' => round($progressPercentage, 2),
            'bytes_uploaded' => $session->uploadChunks()
                ->where('status', 'completed')
                ->sum('chunk_size'),
        ]);
        
        // Check if session is complete
        if ($completedChunks === $totalChunks) {
            $this->finalizeSession($session);
        }
    }
    
    protected function checkFileCompletion(UploadSession $session, string $fileIdentifier): bool
    {
        $totalChunks = $session->uploadChunks()
            ->where('file_identifier', $fileIdentifier)
            ->count();
            
        $completedChunks = $session->uploadChunks()
            ->where('file_identifier', $fileIdentifier)
            ->where('status', 'completed')
            ->count();
            
        return $totalChunks === $completedChunks;
    }
    
    protected function finalizeFile(UploadSession $session, string $fileIdentifier): void
    {
        // Assemble file from chunks and create ProjectFile record
        $chunks = $session->uploadChunks()
            ->where('file_identifier', $fileIdentifier)
            ->orderBy('chunk_number')
            ->get();
            
        $fileMetadata = collect($session->file_metadata)
            ->first(fn($file) => $file['identifier'] === $fileIdentifier);
            
        if (!$fileMetadata) {
            throw new \Exception("File metadata not found for identifier: {$fileIdentifier}");
        }
        
        // Use chunk service to assemble final file
        $finalFilePath = $this->chunkService->assembleChunks($chunks, $fileMetadata);
        
        // Create ProjectFile record
        if ($session->project_id) {
            $this->fileService->storeProjectFile(
                $session->project,
                $finalFilePath,
                $fileMetadata['name'],
                $session->user,
                [
                    'upload_session_id' => $session->session_id,
                    'is_resumable_upload' => true,
                    'folder_path' => $fileMetadata['folder_path'] ?? null,
                    'upload_metrics' => $this->calculateFileUploadMetrics($chunks),
                ]
            );
        }
        
        // Clean up temporary chunk files
        $this->chunkService->cleanupChunks($chunks);
        
        $session->increment('files_completed');
    }
    
    protected function finalizeSession(UploadSession $session): void
    {
        $session->update([
            'status' => 'completed',
            'completed_at' => now(),
            'performance_metrics' => $this->calculateSessionMetrics($session),
        ]);
        
        // Dispatch completion events
        event(new \App\Events\LargeUploadCompleted($session));
        
        Log::info('Upload session completed', [
            'session_id' => $session->session_id,
            'user_id' => $session->user_id,
            'total_files' => $session->total_files,
            'total_size' => $session->total_size_bytes,
            'duration' => $session->completed_at->diffInSeconds($session->started_at),
        ]);
    }
    
    protected function calculateFileUploadMetrics(Collection $chunks): array
    {
        $totalTime = $chunks->last()->uploaded_at->diffInSeconds($chunks->first()->uploaded_at);
        $totalBytes = $chunks->sum('chunk_size');
        $totalRetries = $chunks->sum('retry_count');
        
        return [
            'upload_time_seconds' => $totalTime,
            'average_speed_mbps' => $totalTime > 0 ? ($totalBytes * 8) / ($totalTime * 1000000) : 0,
            'total_retries' => $totalRetries,
            'chunk_count' => $chunks->count(),
        ];
    }
    
    protected function calculateSessionMetrics(UploadSession $session): array
    {
        $duration = $session->completed_at->diffInSeconds($session->started_at);
        $averageSpeed = $duration > 0 ? ($session->total_size_bytes * 8) / ($duration * 1000000) : 0;
        
        return [
            'total_duration_seconds' => $duration,
            'average_speed_mbps' => $averageSpeed,
            'total_retries' => $session->uploadChunks()->sum('retry_count'),
            'chunk_success_rate' => $this->calculateChunkSuccessRate($session),
        ];
    }
    
    protected function calculateChunkSuccessRate(UploadSession $session): float
    {
        $totalChunks = $session->uploadChunks()->count();
        $successfulChunks = $session->uploadChunks()->where('status', 'completed')->count();
        
        return $totalChunks > 0 ? ($successfulChunks / $totalChunks) * 100 : 0;
    }
}
```

### New Service: `ConnectionQualityService`

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\ConnectionQualityMetric;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConnectionQualityService
{
    public function measureConnectionQuality(User $user, string $sessionId): array
    {
        // This would be called from frontend JavaScript with actual speed test results
        // For now, return default configuration
        
        $cacheKey = "connection_quality_{$user->id}";
        
        return Cache::remember($cacheKey, 300, function() use ($user, $sessionId) {
            // Get recent metrics for this user
            $recentMetric = ConnectionQualityMetric::where('user_id', $user->id)
                ->where('measured_at', '>', now()->subHours(1))
                ->latest('measured_at')
                ->first();
                
            if ($recentMetric) {
                return $this->buildSettingsFromMetric($recentMetric);
            }
            
            // Default settings for unknown connection quality
            return $this->getDefaultUploadSettings();
        });
    }
    
    public function storeConnectionMetrics(
        User $user,
        string $sessionId,
        array $speedTestResults
    ): ConnectionQualityMetric {
        
        $uploadSpeed = $speedTestResults['upload_speed_mbps'] ?? 0;
        $downloadSpeed = $speedTestResults['download_speed_mbps'] ?? 0;
        $latency = $speedTestResults['latency_ms'] ?? 0;
        $packetLoss = $speedTestResults['packet_loss_percentage'] ?? 0;
        
        $recommendedChunkSize = $this->calculateOptimalChunkSize($uploadSpeed, $latency);
        
        $metric = ConnectionQualityMetric::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'upload_speed_mbps' => $uploadSpeed,
            'download_speed_mbps' => $downloadSpeed,
            'latency_ms' => $latency,
            'packet_loss_percentage' => $packetLoss,
            'connection_type' => $speedTestResults['connection_type'] ?? null,
            'recommended_chunk_size' => $recommendedChunkSize,
            'test_results' => $speedTestResults,
            'measured_at' => now(),
        ]);
        
        // Invalidate cached settings
        Cache::forget("connection_quality_{$user->id}");
        
        Log::info('Connection quality measured', [
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'upload_speed' => $uploadSpeed,
            'latency' => $latency,
            'recommended_chunk_size' => $recommendedChunkSize,
        ]);
        
        return $metric;
    }
    
    public function getOptimalUploadSettings(User $user): array
    {
        $metrics = $this->measureConnectionQuality($user, 'default');
        
        return [
            'chunk_size' => $metrics['chunk_size'],
            'max_concurrent_uploads' => $metrics['max_concurrent'],
            'retry_attempts' => $metrics['retry_attempts'],
            'retry_delay_seconds' => $metrics['retry_delay'],
            'timeout_seconds' => $metrics['timeout'],
        ];
    }
    
    protected function buildSettingsFromMetric(ConnectionQualityMetric $metric): array
    {
        $uploadSpeed = $metric->upload_speed_mbps;
        $latency = $metric->latency_ms;
        $packetLoss = $metric->packet_loss_percentage;
        
        // Determine connection quality tier
        if ($uploadSpeed >= 25 && $latency <= 50 && $packetLoss <= 1) {
            $tier = 'excellent';
        } elseif ($uploadSpeed >= 10 && $latency <= 100 && $packetLoss <= 2) {
            $tier = 'good';
        } elseif ($uploadSpeed >= 5 && $latency <= 200 && $packetLoss <= 5) {
            $tier = 'fair';
        } else {
            $tier = 'poor';
        }
        
        return match($tier) {
            'excellent' => [
                'chunk_size' => 20971520, // 20MB
                'max_concurrent' => 6,
                'retry_attempts' => 3,
                'retry_delay' => 1,
                'timeout' => 60,
                'quality_tier' => 'excellent'
            ],
            'good' => [
                'chunk_size' => 10485760, // 10MB
                'max_concurrent' => 4,
                'retry_attempts' => 3,
                'retry_delay' => 2,
                'timeout' => 90,
                'quality_tier' => 'good'
            ],
            'fair' => [
                'chunk_size' => 5242880, // 5MB
                'max_concurrent' => 2,
                'retry_attempts' => 5,
                'retry_delay' => 5,
                'timeout' => 120,
                'quality_tier' => 'fair'
            ],
            'poor' => [
                'chunk_size' => 1048576, // 1MB
                'max_concurrent' => 1,
                'retry_attempts' => 8,
                'retry_delay' => 10,
                'timeout' => 180,
                'quality_tier' => 'poor'
            ],
        };
    }
    
    protected function getDefaultUploadSettings(): array
    {
        return [
            'chunk_size' => 5242880, // 5MB
            'max_concurrent' => 3,
            'retry_attempts' => 5,
            'retry_delay' => 3,
            'timeout' => 120,
            'quality_tier' => 'unknown'
        ];
    }
    
    protected function calculateOptimalChunkSize(float $uploadSpeedMbps, int $latencyMs): int
    {
        // Base chunk size on upload speed and latency
        // Faster connections can handle larger chunks
        // Higher latency requires smaller chunks for better resumability
        
        if ($uploadSpeedMbps >= 25 && $latencyMs <= 50) {
            return 20971520; // 20MB
        } elseif ($uploadSpeedMbps >= 10 && $latencyMs <= 100) {
            return 10485760; // 10MB
        } elseif ($uploadSpeedMbps >= 5 && $latencyMs <= 200) {
            return 5242880; // 5MB
        } elseif ($uploadSpeedMbps >= 1) {
            return 2097152; // 2MB
        } else {
            return 1048576; // 1MB
        }
    }
}
```

### New Service: `ChunkManagementService`

```php
<?php

namespace App\Services;

use App\Models\UploadChunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChunkManagementService
{
    public function storeChunk(UploadChunk $chunk, string $chunkData, array $metadata = []): array
    {
        $storageStrategy = config('filesystems.large_uploads.strategy', 's3_multipart');
        
        return match($storageStrategy) {
            's3_multipart' => $this->storeChunkS3Multipart($chunk, $chunkData, $metadata),
            'local_temp' => $this->storeChunkLocalTemp($chunk, $chunkData, $metadata),
            default => throw new \Exception("Unknown storage strategy: {$storageStrategy}")
        };
    }
    
    protected function storeChunkS3Multipart(UploadChunk $chunk, string $chunkData, array $metadata): array
    {
        $s3 = Storage::disk('s3');
        
        // Initialize multipart upload if this is the first chunk
        if ($chunk->chunk_number === 0) {
            $uploadId = $this->initializeS3MultipartUpload($chunk);
            
            // Store upload ID in session metadata
            $chunk->uploadSession->update([
                'upload_settings' => array_merge(
                    $chunk->uploadSession->upload_settings ?? [],
                    ['s3_upload_id' => $uploadId]
                )
            ]);
        } else {
            $uploadId = $chunk->uploadSession->upload_settings['s3_upload_id'] ?? null;
            if (!$uploadId) {
                throw new \Exception('S3 multipart upload ID not found');
            }
        }
        
        // Upload chunk part
        $partNumber = $chunk->chunk_number + 1; // S3 part numbers start at 1
        
        try {
            $result = $s3->getDriver()->getAdapter()->getClient()->uploadPart([
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $this->generateS3Key($chunk),
                'UploadId' => $uploadId,
                'PartNumber' => $partNumber,
                'Body' => $chunkData,
            ]);
            
            return [
                'storage_path' => $this->generateS3Key($chunk),
                's3_upload_id' => $uploadId,
                's3_etag' => $result['ETag'],
                'part_number' => $partNumber,
            ];
            
        } catch (\Exception $e) {
            Log::error('S3 chunk upload failed', [
                'chunk_id' => $chunk->chunk_id,
                'upload_id' => $uploadId,
                'part_number' => $partNumber,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    protected function storeChunkLocalTemp(UploadChunk $chunk, string $chunkData, array $metadata): array
    {
        $tempDir = storage_path('app/temp/chunks/' . $chunk->uploadSession->session_id);
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $chunkPath = $tempDir . '/' . $chunk->file_identifier . '_' . $chunk->chunk_number;
        
        if (file_put_contents($chunkPath, $chunkData) === false) {
            throw new \Exception("Failed to write chunk to temporary storage: {$chunkPath}");
        }
        
        return [
            'storage_path' => $chunkPath,
            's3_upload_id' => null,
            's3_etag' => null,
        ];
    }
    
    public function assembleChunks(Collection $chunks, array $fileMetadata): string
    {
        $storageStrategy = config('filesystems.large_uploads.strategy', 's3_multipart');
        
        return match($storageStrategy) {
            's3_multipart' => $this->assembleS3MultipartUpload($chunks, $fileMetadata),
            'local_temp' => $this->assembleLocalTempChunks($chunks, $fileMetadata),
            default => throw new \Exception("Unknown storage strategy: {$storageStrategy}")
        };
    }
    
    protected function assembleS3MultipartUpload(Collection $chunks, array $fileMetadata): string
    {
        $firstChunk = $chunks->first();
        $uploadId = $firstChunk->s3_upload_id;
        
        if (!$uploadId) {
            throw new \Exception('S3 upload ID not found for multipart assembly');
        }
        
        // Prepare parts array
        $parts = $chunks->map(function ($chunk) {
            return [
                'ETag' => $chunk->s3_etag,
                'PartNumber' => $chunk->chunk_number + 1,
            ];
        })->sortBy('PartNumber')->values()->toArray();
        
        $s3 = Storage::disk('s3');
        $finalKey = $this->generateFinalS3Key($fileMetadata);
        
        try {
            // Complete multipart upload
            $result = $s3->getDriver()->getAdapter()->getClient()->completeMultipartUpload([
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $finalKey,
                'UploadId' => $uploadId,
                'MultipartUpload' => [
                    'Parts' => $parts,
                ],
            ]);
            
            Log::info('S3 multipart upload completed', [
                'upload_id' => $uploadId,
                'final_key' => $finalKey,
                'parts_count' => count($parts),
                'location' => $result['Location'],
            ]);
            
            return $finalKey;
            
        } catch (\Exception $e) {
            // Abort multipart upload on failure
            $s3->getDriver()->getAdapter()->getClient()->abortMultipartUpload([
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $finalKey,
                'UploadId' => $uploadId,
            ]);
            
            Log::error('S3 multipart assembly failed', [
                'upload_id' => $uploadId,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    protected function assembleLocalTempChunks(Collection $chunks, array $fileMetadata): string
    {
        $finalDir = storage_path('app/temp/assembled');
        if (!is_dir($finalDir)) {
            mkdir($finalDir, 0755, true);
        }
        
        $finalPath = $finalDir . '/' . Str::uuid() . '_' . $fileMetadata['name'];
        $finalFile = fopen($finalPath, 'wb');
        
        if (!$finalFile) {
            throw new \Exception("Failed to create final file: {$finalPath}");
        }
        
        try {
            foreach ($chunks->sortBy('chunk_number') as $chunk) {
                $chunkData = file_get_contents($chunk->storage_path);
                if ($chunkData === false) {
                    throw new \Exception("Failed to read chunk: {$chunk->storage_path}");
                }
                
                if (fwrite($finalFile, $chunkData) === false) {
                    throw new \Exception("Failed to write chunk to final file");
                }
            }
            
            fclose($finalFile);
            
            // Verify final file size
            $expectedSize = $chunks->sum('chunk_size');
            $actualSize = filesize($finalPath);
            
            if ($actualSize !== $expectedSize) {
                throw new \Exception("File size mismatch. Expected: {$expectedSize}, Got: {$actualSize}");
            }
            
            return $finalPath;
            
        } catch (\Exception $e) {
            fclose($finalFile);
            @unlink($finalPath);
            throw $e;
        }
    }
    
    public function cleanupChunks(Collection $chunks): void
    {
        foreach ($chunks as $chunk) {
            if ($chunk->storage_path && file_exists($chunk->storage_path)) {
                @unlink($chunk->storage_path);
            }
        }
        
        // Remove chunk records
        UploadChunk::whereIn('id', $chunks->pluck('id'))->delete();
    }
    
    protected function initializeS3MultipartUpload(UploadChunk $chunk): string
    {
        $s3 = Storage::disk('s3');
        $key = $this->generateS3Key($chunk);
        
        $result = $s3->getDriver()->getAdapter()->getClient()->createMultipartUpload([
            'Bucket' => config('filesystems.disks.s3.bucket'),
            'Key' => $key,
            'Metadata' => [
                'upload-session-id' => $chunk->uploadSession->session_id,
                'file-identifier' => $chunk->file_identifier,
            ],
        ]);
        
        return $result['UploadId'];
    }
    
    protected function generateS3Key(UploadChunk $chunk): string
    {
        return "large-uploads/{$chunk->uploadSession->session_id}/{$chunk->file_identifier}";
    }
    
    protected function generateFinalS3Key(array $fileMetadata): string
    {
        $timestamp = now()->format('Y/m/d');
        $uuid = Str::uuid();
        $extension = pathinfo($fileMetadata['name'], PATHINFO_EXTENSION);
        
        return "project-files/{$timestamp}/{$uuid}.{$extension}";
    }
}
```

## Frontend Integration

### Enhanced Uppy Configuration

```javascript
// enhanced-uppy-config.js
import Uppy from '@uppy/core';
import Dashboard from '@uppy/dashboard';
import AwsS3Multipart from '@uppy/aws-s3-multipart';
import Tus from '@uppy/tus';
import Webcam from '@uppy/webcam';
import Audio from '@uppy/audio';
import DropTarget from '@uppy/drop-target';

class EnhancedUppyManager {
    constructor(options = {}) {
        this.options = {
            maxFileSize: 2 * 1024 * 1024 * 1024, // 2GB
            allowedFileTypes: ['.wav', '.flac', '.aiff', '.mp3', '.zip', '.pdf'],
            maxNumberOfFiles: 100,
            ...options
        };
        
        this.uppy = null;
        this.connectionQuality = null;
        this.uploadSettings = null;
        this.sessionId = null;
    }
    
    async initialize() {
        // Measure connection quality first
        await this.measureConnectionQuality();
        
        // Get optimal upload settings from backend
        await this.fetchUploadSettings();
        
        // Initialize Uppy with optimal settings
        this.initializeUppy();
        
        // Set up event listeners
        this.setupEventListeners();
        
        return this.uppy;
    }
    
    async measureConnectionQuality() {
        const startTime = performance.now();
        
        try {
            // Download a small test file to measure speed
            const testUrl = '/api/speed-test/download';
            const response = await fetch(testUrl);
            const testData = await response.blob();
            
            const downloadTime = (performance.now() - startTime) / 1000;
            const downloadSpeed = (testData.size * 8) / (downloadTime * 1000000); // Mbps
            
            // Measure latency with a ping request
            const pingStart = performance.now();
            await fetch('/api/speed-test/ping');
            const latency = performance.now() - pingStart;
            
            this.connectionQuality = {
                download_speed_mbps: downloadSpeed,
                latency_ms: latency,
                connection_type: this.detectConnectionType(),
                measured_at: new Date().toISOString()
            };
            
            // Send metrics to backend
            await fetch('/api/connection-quality', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_id: this.getSessionId(),
                    metrics: this.connectionQuality
                })
            });
            
        } catch (error) {
            console.warn('Connection quality measurement failed:', error);
            this.connectionQuality = { quality_tier: 'unknown' };
        }
    }
    
    async fetchUploadSettings() {
        try {
            const response = await fetch('/api/upload-settings/optimized');
            this.uploadSettings = await response.json();
        } catch (error) {
            console.warn('Failed to fetch upload settings:', error);
            this.uploadSettings = this.getDefaultSettings();
        }
    }
    
    initializeUppy() {
        this.uppy = new Uppy({
            restrictions: {
                maxFileSize: this.options.maxFileSize,
                maxNumberOfFiles: this.options.maxNumberOfFiles,
                allowedFileTypes: this.options.allowedFileTypes,
            },
            meta: {
                sessionId: this.getSessionId()
            }
        });
        
        // Add Dashboard
        this.uppy.use(Dashboard, {
            target: '#uppy-upload-area',
            inline: true,
            width: '100%',
            height: 400,
            showProgressDetails: true,
            showLinkToFileUploadResult: false,
            showSelectedFiles: true,
            locale: {
                strings: {
                    addMoreFiles: 'Add more files',
                    addingMoreFiles: 'Adding more files',
                    dropPasteFiles: 'Drop files here, paste or %{browse}',
                    browse: 'browse',
                    uploadComplete: 'Upload complete',
                    uploadPaused: 'Upload paused',
                    resumeUpload: 'Resume upload',
                    cancelUpload: 'Cancel upload',
                    retryUpload: 'Retry upload',
                    xFilesSelected: {
                        0: '%{smart_count} file selected',
                        1: '%{smart_count} files selected'
                    }
                }
            }
        });
        
        // Configure upload method based on settings
        if (this.uploadSettings.strategy === 's3_multipart') {
            this.uppy.use(AwsS3Multipart, {
                limit: this.uploadSettings.max_concurrent || 3,
                companionUrl: '/api/uppy-companion',
                companionHeaders: {
                    'X-Session-ID': this.getSessionId()
                },
                createMultipartUpload: async (file) => {
                    const response = await fetch('/api/multipart/create', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            filename: file.name,
                            type: file.type,
                            sessionId: this.getSessionId()
                        })
                    });
                    return response.json();
                },
                prepareUploadParts: async (file, { uploadId, key, parts }) => {
                    const response = await fetch('/api/multipart/prepare', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            uploadId,
                            key,
                            parts,
                            sessionId: this.getSessionId()
                        })
                    });
                    return response.json();
                },
                completeMultipartUpload: async (file, { uploadId, key, parts }) => {
                    const response = await fetch('/api/multipart/complete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            uploadId,
                            key,
                            parts,
                            sessionId: this.getSessionId()
                        })
                    });
                    return response.json();
                }
            });
        } else {
            // Fallback to TUS for resumable uploads
            this.uppy.use(Tus, {
                endpoint: '/api/tus-upload',
                resume: true,
                autoRetry: true,
                retryDelays: [0, 1000, 3000, 5000],
                chunkSize: this.uploadSettings.chunk_size || 5242880,
                limit: this.uploadSettings.max_concurrent || 3,
                headers: {
                    'X-Session-ID': this.getSessionId()
                }
            });
        }
        
        // Add folder support
        this.uppy.use(DropTarget, {
            target: document.body
        });
    }
    
    setupEventListeners() {
        // Progress tracking
        this.uppy.on('upload-progress', (file, progress) => {
            this.updateFileProgress(file.id, progress);
        });
        
        // Success handling
        this.uppy.on('upload-success', (file, response) => {
            this.handleUploadSuccess(file, response);
        });
        
        // Error handling
        this.uppy.on('upload-error', (file, error, response) => {
            this.handleUploadError(file, error, response);
        });
        
        // Overall progress
        this.uppy.on('progress', (progress) => {
            this.updateOverallProgress(progress);
        });
        
        // Connection monitoring
        this.setupConnectionMonitoring();
    }
    
    setupConnectionMonitoring() {
        // Monitor upload speeds and adjust chunk size dynamically
        let speedSamples = [];
        
        this.uppy.on('upload-progress', (file, progress) => {
            if (progress.uploadStarted && progress.uploadStarted > 0) {
                const elapsed = (Date.now() - progress.uploadStarted) / 1000;
                const speed = progress.bytesUploaded / elapsed;
                
                speedSamples.push(speed);
                
                // Keep only recent samples
                if (speedSamples.length > 10) {
                    speedSamples = speedSamples.slice(-10);
                }
                
                // Calculate average speed and adjust settings if needed
                const avgSpeed = speedSamples.reduce((a, b) => a + b) / speedSamples.length;
                this.adaptToConnectionSpeed(avgSpeed);
            }
        });
    }
    
    adaptToConnectionSpeed(speedBytesPerSecond) {
        const speedMbps = (speedBytesPerSecond * 8) / 1000000;
        
        // Update UI with current speed
        document.getElementById('upload-speed').textContent = 
            `Upload Speed: ${speedMbps.toFixed(1)} Mbps`;
        
        // Adjust chunk size based on performance
        let newChunkSize;
        if (speedMbps > 25) {
            newChunkSize = 20971520; // 20MB
        } else if (speedMbps > 10) {
            newChunkSize = 10485760; // 10MB
        } else if (speedMbps > 5) {
            newChunkSize = 5242880; // 5MB
        } else {
            newChunkSize = 1048576; // 1MB
        }
        
        // Update chunk size display
        const chunkSizeMB = (newChunkSize / 1048576).toFixed(0);
        document.getElementById('current-chunk-size').textContent = `${chunkSizeMB} MB`;
    }
    
    updateFileProgress(fileId, progress) {
        // Update individual file progress in UI
        const progressElement = document.querySelector(`[data-file-id="${fileId}"] .progress-bar`);
        if (progressElement) {
            const percentage = Math.round((progress.bytesUploaded / progress.bytesTotal) * 100);
            progressElement.style.width = `${percentage}%`;
        }
    }
    
    updateOverallProgress(progress) {
        const percentage = Math.round(progress);
        
        const progressBar = document.getElementById('overall-progress-bar');
        const progressText = document.getElementById('overall-progress-text');
        
        if (progressBar) progressBar.style.width = `${percentage}%`;
        if (progressText) progressText.textContent = `${percentage}%`;
    }
    
    handleUploadSuccess(file, response) {
        console.log('Upload successful:', file.name, response);
        
        // Notify Livewire component
        if (window.Livewire) {
            window.Livewire.dispatch('fileUploaded', {
                fileId: file.id,
                fileName: file.name,
                response: response
            });
        }
    }
    
    handleUploadError(file, error, response) {
        console.error('Upload failed:', file.name, error, response);
        
        // Show user-friendly error message
        this.showErrorNotification(file, error);
    }
    
    showErrorNotification(file, error) {
        // Integrate with your notification system
        const message = `Failed to upload ${file.name}: ${error.message}`;
        
        if (window.Toaster) {
            window.Toaster.error(message);
        } else {
            alert(message);
        }
    }
    
    detectConnectionType() {
        // Attempt to detect connection type
        if (navigator.connection) {
            return navigator.connection.effectiveType || 'unknown';
        }
        return 'unknown';
    }
    
    getSessionId() {
        if (!this.sessionId) {
            this.sessionId = crypto.randomUUID();
        }
        return this.sessionId;
    }
    
    getDefaultSettings() {
        return {
            strategy: 'tus',
            chunk_size: 5242880, // 5MB
            max_concurrent: 3,
            max_retries: 5,
            retry_delay: 3000
        };
    }
    
    pause() {
        this.uppy.pauseAll();
    }
    
    resume() {
        this.uppy.resumeAll();
    }
    
    cancel() {
        this.uppy.cancelAll();
    }
    
    destroy() {
        if (this.uppy) {
            this.uppy.destroy();
        }
    }
}

// Initialize enhanced uploader
window.UppyManager = EnhancedUppyManager;
```

## Testing Strategy

### Feature Tests

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\UploadSession;
use App\Services\LargeFileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LargeFileUploadTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_initializes_upload_session()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        $files = [
            [
                'name' => 'large_track.wav',
                'size' => 100 * 1024 * 1024, // 100MB
                'type' => 'audio/wav',
                'identifier' => 'file-1',
            ]
        ];
        
        $service = app(LargeFileUploadService::class);
        $session = $service->initializeUploadSession($user, $files, $project);
        
        $this->assertDatabaseHas('upload_sessions', [
            'session_id' => $session->session_id,
            'user_id' => $user->id,
            'project_id' => $project->id,
            'total_files' => 1,
            'total_size_bytes' => 100 * 1024 * 1024,
            'status' => 'pending',
        ]);
        
        // Check that chunks were created
        $this->assertGreaterThan(0, $session->uploadChunks()->count());
    }
    
    public function test_processes_chunk_upload()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        $session = UploadSession::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
        
        $chunk = \App\Models\UploadChunk::factory()->create([
            'upload_session_id' => $session->id,
            'chunk_number' => 0,
            'chunk_size' => 1024,
            'checksum' => hash('sha256', 'test data'),
        ]);
        
        $service = app(LargeFileUploadService::class);
        
        $result = $service->processChunkUpload(
            $session->session_id,
            $chunk->file_identifier,
            0,
            'test data'
        );
        
        $this->assertTrue($result['success']);
        $this->assertEquals('completed', $chunk->fresh()->status);
    }
    
    public function test_handles_chunk_retry_on_failure()
    {
        $user = User::factory()->create();
        $session = UploadSession::factory()->create(['user_id' => $user->id]);
        
        $chunk = \App\Models\UploadChunk::factory()->create([
            'upload_session_id' => $session->id,
            'checksum' => hash('sha256', 'correct data'),
        ]);
        
        $service = app(LargeFileUploadService::class);
        
        try {
            $service->processChunkUpload(
                $session->session_id,
                $chunk->file_identifier,
                $chunk->chunk_number,
                'wrong data' // This will fail checksum
            );
        } catch (\Exception $e) {
            // Expected to fail
        }
        
        $chunk->refresh();
        $this->assertEquals('failed', $chunk->status);
        $this->assertEquals(1, $chunk->retry_count);
    }
}
```

### Performance Tests

```php
<?php

namespace Tests\Performance;

use App\Services\ConnectionQualityService;
use App\Models\User;
use Tests\TestCase;

class ConnectionQualityTest extends TestCase
{
    public function test_connection_quality_assessment_performance()
    {
        $user = User::factory()->create();
        $service = app(ConnectionQualityService::class);
        
        $startTime = microtime(true);
        
        $settings = $service->getOptimalUploadSettings($user);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Should complete within 100ms
        $this->assertLessThan(100, $executionTime);
        
        $this->assertArrayHasKey('chunk_size', $settings);
        $this->assertArrayHasKey('max_concurrent', $settings);
        $this->assertArrayHasKey('retry_attempts', $settings);
    }
}
```

## Implementation Steps

### Phase 1: Core Infrastructure (Week 1)
1. Create database migrations for upload sessions, chunks, and metrics
2. Implement basic `LargeFileUploadService` with session management
3. Set up S3 multipart upload configuration
4. Create basic chunk management service

### Phase 2: Connection Quality & Adaptive Settings (Week 2)
1. Implement `ConnectionQualityService` with speed testing
2. Add adaptive chunk sizing based on connection quality
3. Create upload settings optimization algorithms
4. Build connection monitoring and adjustment logic

### Phase 3: Enhanced Frontend (Week 3)
1. Upgrade Uppy configuration with multipart support
2. Implement real-time progress tracking and speed monitoring
3. Add folder upload support with structure preservation
4. Create adaptive UI based on connection quality

### Phase 4: Advanced Features (Week 4)
1. Add pause/resume functionality for uploads
2. Implement automatic retry with exponential backoff
3. Create upload queue management for multiple files
4. Add background upload processing

### Phase 5: Monitoring & Analytics (Week 5)
1. Build upload analytics dashboard
2. Implement performance monitoring and alerting
3. Create user upload history and statistics
4. Add administrative tools for upload management

## Monitoring & Analytics

### Upload Performance Metrics
- Track average upload speeds by user and connection type
- Monitor chunk failure rates and retry patterns
- Measure session completion rates
- Alert on unusual upload patterns or failures

### User Experience Metrics
- Track user satisfaction with upload experience
- Monitor abandonment rates during large uploads
- Measure time-to-completion for different file sizes
- Analyze optimal chunk sizes for different scenarios

This implementation creates a professional-grade upload system that can handle the largest audio files while maintaining reliability and providing excellent user experience through intelligent adaptation to network conditions.