# Auto Versioning Implementation Plan

## Overview

Eliminate manual version tracking chaos by automatically appending version numbers (V01, V02, etc.) to uploaded files, providing intelligent filename normalization on download, and creating a visual version timeline with A/B comparison capabilities. This feature maintains professional file organization while enabling easy comparison between iterations.

## UX/UI Implementation

### Version Timeline Display

**Location**: Project file management and audio player interfaces  
**Current**: Flat file listing without version awareness  
**New**: Grouped version timeline with comparison tools

```blade
{{-- Version timeline component --}}
<div class="space-y-6">
    @foreach($project->versionGroups as $group)
        <flux:card class="overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-slate-50 to-slate-100 border-b">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-lg">{{ $group->base_name }}</h3>
                        <flux:text size="sm" class="text-slate-600">
                            {{ $group->versions->count() }} {{ Str::plural('version', $group->versions->count()) }}
                            • Latest: {{ $group->latestVersion->created_at->diffForHumans() }}
                        </flux:text>
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($group->versions->count() > 1)
                            <flux:button 
                                size="sm" 
                                variant="outline"
                                wire:click="showVersionComparison('{{ $group->id }}')"
                            >
                                <flux:icon name="compare" size="sm" />
                                Compare
                            </flux:button>
                        @endif
                        <flux:button 
                            size="sm" 
                            variant="primary"
                            wire:click="downloadLatest('{{ $group->id }}')"
                        >
                            <flux:icon name="download" size="sm" />
                            Download Latest
                        </flux:button>
                    </div>
                </div>
            </div>
            
            <div class="p-4">
                <div class="relative">
                    {{-- Version timeline --}}
                    <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-slate-200"></div>
                    
                    <div class="space-y-4">
                        @foreach($group->versions->sortByDesc('version_number') as $version)
                            <div class="relative flex items-start space-x-4">
                                {{-- Timeline dot --}}
                                <div class="relative z-10 flex h-12 w-12 items-center justify-center rounded-full 
                                    {{ $loop->first ? 'bg-indigo-600 text-white' : 'bg-white border-2 border-slate-300' }}">
                                    <span class="text-sm font-medium">
                                        V{{ str_pad($version->version_number, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                </div>
                                
                                {{-- Version details --}}
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="font-medium">{{ $version->display_name }}</h4>
                                            <div class="flex items-center space-x-4 mt-1">
                                                <flux:text size="sm" class="text-slate-500">
                                                    {{ $version->formatted_size }}
                                                </flux:text>
                                                <flux:text size="sm" class="text-slate-500">
                                                    {{ $version->created_at->format('M j, Y g:i A') }}
                                                </flux:text>
                                                @if($version->upload_source)
                                                    <flux:badge variant="outline" size="sm">
                                                        {{ $version->upload_source }}
                                                    </flux:badge>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center space-x-2">
                                            @if($version->audio_metadata)
                                                <flux:button 
                                                    size="sm" 
                                                    variant="ghost"
                                                    wire:click="playVersion('{{ $version->id }}')"
                                                >
                                                    <flux:icon name="play" size="sm" />
                                                </flux:button>
                                            @endif
                                            
                                            <flux:button 
                                                size="sm" 
                                                variant="ghost"
                                                wire:click="downloadVersion('{{ $version->id }}')"
                                            >
                                                <flux:icon name="download" size="sm" />
                                            </flux:button>
                                            
                                            @if(!$loop->first)
                                                <flux:button 
                                                    size="sm" 
                                                    variant="ghost"
                                                    wire:click="compareWithLatest('{{ $version->id }}')"
                                                >
                                                    <flux:icon name="compare" size="sm" />
                                                </flux:button>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    {{-- Audio metadata display --}}
                                    @if($version->audio_metadata)
                                        <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-4 text-xs text-slate-600">
                                            <div>
                                                <span class="font-medium">Duration:</span>
                                                {{ $version->audio_metadata['duration_formatted'] }}
                                            </div>
                                            <div>
                                                <span class="font-medium">LUFS:</span>
                                                {{ $version->audio_metadata['lufs'] ?? 'N/A' }}
                                            </div>
                                            <div>
                                                <span class="font-medium">Sample Rate:</span>
                                                {{ $version->audio_metadata['sample_rate'] ?? 'N/A' }}
                                            </div>
                                            <div>
                                                <span class="font-medium">Bit Depth:</span>
                                                {{ $version->audio_metadata['bit_depth'] ?? 'N/A' }}
                                            </div>
                                        </div>
                                    @endif
                                    
                                    {{-- Upload notes --}}
                                    @if($version->upload_notes)
                                        <div class="mt-2 p-2 bg-slate-50 rounded text-sm">
                                            {{ $version->upload_notes }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </flux:card>
    @endforeach
</div>
```

### A/B Comparison Interface

```blade
{{-- Version comparison modal --}}
<flux:modal wire:model="showComparison" size="full">
    <flux:modal.header>
        <h2 class="text-xl font-semibold">Compare Versions: {{ $comparisonGroup->base_name ?? '' }}</h2>
    </flux:modal.header>
    
    <flux:modal.body class="p-6">
        @if($comparisonVersions)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Version A --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium">Version A</h3>
                        <flux:select wire:model.live="comparisonVersionA">
                            @foreach($comparisonVersions as $version)
                                <option value="{{ $version->id }}">
                                    V{{ str_pad($version->version_number, 2, '0', STR_PAD_LEFT) }} - {{ $version->created_at->format('M j') }}
                                </option>
                            @endforeach
                        </flux:select>
                    </div>
                    
                    @if($versionA)
                        <div class="bg-slate-50 rounded-lg p-4">
                            <livewire:universal-audio-player 
                                :file="$versionA" 
                                :sync-group="'comparison'"
                                :player-id="'version-a'"
                                wire:key="version-a-{{ $versionA->id }}"
                            />
                            
                            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium">Duration:</span>
                                    {{ $versionA->audio_metadata['duration_formatted'] ?? 'N/A' }}
                                </div>
                                <div>
                                    <span class="font-medium">LUFS:</span>
                                    {{ $versionA->audio_metadata['lufs'] ?? 'N/A' }}
                                </div>
                                <div>
                                    <span class="font-medium">Size:</span>
                                    {{ $versionA->formatted_size }}
                                </div>
                                <div>
                                    <span class="font-medium">Uploaded:</span>
                                    {{ $versionA->created_at->format('M j, Y') }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                
                {{-- Version B --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium">Version B</h3>
                        <flux:select wire:model.live="comparisonVersionB">
                            @foreach($comparisonVersions as $version)
                                <option value="{{ $version->id }}">
                                    V{{ str_pad($version->version_number, 2, '0', STR_PAD_LEFT) }} - {{ $version->created_at->format('M j') }}
                                </option>
                            @endforeach
                        </flux:select>
                    </div>
                    
                    @if($versionB)
                        <div class="bg-slate-50 rounded-lg p-4">
                            <livewire:universal-audio-player 
                                :file="$versionB" 
                                :sync-group="'comparison'"
                                :player-id="'version-b'"
                                wire:key="version-b-{{ $versionB->id }}"
                            />
                            
                            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium">Duration:</span>
                                    {{ $versionB->audio_metadata['duration_formatted'] ?? 'N/A' }}
                                </div>
                                <div>
                                    <span class="font-medium">LUFS:</span>
                                    {{ $versionB->audio_metadata['lufs'] ?? 'N/A' }}
                                </div>
                                <div>
                                    <span class="font-medium">Size:</span>
                                    {{ $versionB->formatted_size }}
                                </div>
                                <div>
                                    <span class="font-medium">Uploaded:</span>
                                    {{ $versionB->created_at->format('M j, Y') }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- Comparison controls --}}
            <div class="mt-6 p-4 bg-indigo-50 rounded-lg">
                <div class="flex items-center justify-center space-x-4">
                    <flux:button 
                        wire:click="syncPlayback" 
                        variant="outline"
                        size="sm"
                    >
                        <flux:icon name="sync" size="sm" />
                        Sync Playback
                    </flux:button>
                    
                    <flux:button 
                        wire:click="toggleABMode" 
                        variant="{{ $abModeActive ? 'primary' : 'outline' }}"
                        size="sm"
                    >
                        {{ $abModeActive ? 'Stop' : 'Start' }} A/B Mode
                    </flux:button>
                    
                    @if($versionA && $versionB && $versionA->audio_metadata && $versionB->audio_metadata)
                        <div class="text-sm text-slate-600">
                            LUFS Difference: 
                            <span class="font-medium">
                                {{ abs(($versionA->audio_metadata['lufs'] ?? 0) - ($versionB->audio_metadata['lufs'] ?? 0)) }} dB
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </flux:modal.body>
    
    <flux:modal.footer>
        <flux:button wire:click="$set('showComparison', false)" variant="outline">
            Close
        </flux:button>
    </flux:modal.footer>
</flux:modal>
```

### Upload Interface Enhancement

```blade
{{-- Enhanced upload interface with version notes --}}
<div class="space-y-4">
    {{-- Existing Uppy file uploader --}}
    <livewire:uppy-file-uploader :model="$project" :config="$uploadConfig" />
    
    {{-- Version notes field --}}
    <flux:field>
        <flux:label>Version Notes (Optional)</flux:label>
        <flux:textarea 
            wire:model.defer="uploadNotes"
            placeholder="What changed in this version? e.g., 'Added reverb to vocals, adjusted mix levels'"
            rows="2"
        />
        <flux:text size="sm" class="text-slate-500">
            These notes will appear in the version timeline
        </flux:text>
    </flux:field>
</div>
```

## Database Schema

### New Table: `file_versions`

```php
Schema::create('file_versions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->onDelete('cascade');
    $table->foreignId('project_file_id')->constrained()->onDelete('cascade');
    $table->string('version_group_id'); // UUID for grouping related versions
    $table->string('base_name'); // Normalized filename without version suffix
    $table->integer('version_number'); // 1, 2, 3, etc.
    $table->string('display_name'); // Full filename with version (e.g., "Track_V01.wav")
    $table->string('original_filename'); // Exact filename as uploaded
    $table->json('audio_metadata')->nullable(); // Duration, LUFS, sample rate, etc.
    $table->text('upload_notes')->nullable(); // User-provided version notes
    $table->string('upload_source')->nullable(); // 'manual', 'link_import', 'email_attachment'
    $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamps();
    
    $table->index(['project_id', 'version_group_id', 'version_number']);
    $table->index(['version_group_id', 'version_number']);
    $table->unique(['version_group_id', 'version_number']);
});
```

### Extend `project_files` table

```php
Schema::table('project_files', function (Blueprint $table) {
    $table->string('version_group_id')->nullable()->after('metadata');
    $table->integer('version_number')->nullable()->after('version_group_id');
    $table->string('base_name')->nullable()->after('version_number');
    $table->json('audio_metadata')->nullable()->after('base_name');
    $table->text('upload_notes')->nullable()->after('audio_metadata');
    
    $table->index(['version_group_id', 'version_number']);
    $table->index(['project_id', 'base_name']);
});
```

### New Table: `version_comparisons`

```php
Schema::create('version_comparisons', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('version_group_id');
    $table->foreignId('version_a_id')->constrained('file_versions')->onDelete('cascade');
    $table->foreignId('version_b_id')->constrained('file_versions')->onDelete('cascade');
    $table->json('comparison_metadata')->nullable(); // LUFS differences, duration, etc.
    $table->text('notes')->nullable(); // User notes about the comparison
    $table->timestamps();
    
    $table->index(['user_id', 'version_group_id']);
    $table->index(['version_a_id', 'version_b_id']);
});
```

## Service Layer Architecture

### New Service: `VersioningService`

```php
<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\FileVersion;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class VersioningService
{
    protected AudioMetadataService $audioService;
    
    public function __construct(AudioMetadataService $audioService)
    {
        $this->audioService = $audioService;
    }
    
    public function processFileForVersioning(
        ProjectFile $projectFile, 
        ?string $uploadNotes = null,
        ?string $uploadSource = 'manual'
    ): FileVersion {
        
        // Extract base name from filename
        $baseName = $this->extractBaseName($projectFile->original_file_name);
        
        // Find or create version group
        $versionGroupId = $this->getVersionGroupId($projectFile->project, $baseName);
        
        // Determine next version number
        $versionNumber = $this->getNextVersionNumber($versionGroupId);
        
        // Generate display name with version
        $displayName = $this->generateVersionedFilename($baseName, $versionNumber, $projectFile->original_file_name);
        
        // Extract audio metadata if it's an audio file
        $audioMetadata = null;
        if ($this->isAudioFile($projectFile)) {
            $audioMetadata = $this->audioService->extractMetadata($projectFile);
        }
        
        // Create version record
        $version = FileVersion::create([
            'project_id' => $projectFile->project_id,
            'project_file_id' => $projectFile->id,
            'version_group_id' => $versionGroupId,
            'base_name' => $baseName,
            'version_number' => $versionNumber,
            'display_name' => $displayName,
            'original_filename' => $projectFile->original_file_name,
            'audio_metadata' => $audioMetadata,
            'upload_notes' => $uploadNotes,
            'upload_source' => $uploadSource,
            'uploaded_by' => auth()->id(),
        ]);
        
        // Update project file with versioning info
        $projectFile->update([
            'version_group_id' => $versionGroupId,
            'version_number' => $versionNumber,
            'base_name' => $baseName,
            'audio_metadata' => $audioMetadata,
            'upload_notes' => $uploadNotes,
        ]);
        
        Log::info('File version created', [
            'project_id' => $projectFile->project_id,
            'file_id' => $projectFile->id,
            'version_group_id' => $versionGroupId,
            'version_number' => $versionNumber,
            'base_name' => $baseName,
        ]);
        
        return $version;
    }
    
    protected function extractBaseName(string $filename): string
    {
        // Remove file extension
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        
        // Remove common version patterns:
        // - _V01, _V02, _v1, _v2
        // - (V01), (V02), (v1), (v2)
        // - -V01, -V02, -v1, -v2
        // - _final, _FINAL, _mix, _MIX
        // - _master, _MASTER
        
        $patterns = [
            '/[_\-\s]*[Vv](\d{1,2})$/i',  // _V01, -V01, V01
            '/[_\-\s]*\([Vv](\d{1,2})\)$/i',  // (V01), (v01)
            '/[_\-\s]*(final|mix|master)$/i',  // _final, _mix, _master
            '/[_\-\s]*\d{1,2}$/i',  // Simple numbers: _1, _2, -3
        ];
        
        foreach ($patterns as $pattern) {
            $nameWithoutExt = preg_replace($pattern, '', $nameWithoutExt);
        }
        
        return trim($nameWithoutExt, '_- ');
    }
    
    protected function getVersionGroupId(Project $project, string $baseName): string
    {
        // Check for existing version group with this base name
        $existingVersion = FileVersion::where('project_id', $project->id)
            ->where('base_name', $baseName)
            ->first();
            
        if ($existingVersion) {
            return $existingVersion->version_group_id;
        }
        
        // Create new version group ID
        return Str::uuid()->toString();
    }
    
    protected function getNextVersionNumber(string $versionGroupId): int
    {
        $maxVersion = FileVersion::where('version_group_id', $versionGroupId)
            ->max('version_number');
            
        return ($maxVersion ?? 0) + 1;
    }
    
    protected function generateVersionedFilename(string $baseName, int $versionNumber, string $originalFilename): string
    {
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $versionSuffix = 'V' . str_pad($versionNumber, 2, '0', STR_PAD_LEFT);
        
        return "{$baseName}_{$versionSuffix}.{$extension}";
    }
    
    protected function isAudioFile(ProjectFile $file): bool
    {
        $audioMimeTypes = [
            'audio/mpeg',
            'audio/wav',
            'audio/flac',
            'audio/aiff',
            'audio/ogg',
            'audio/m4a',
        ];
        
        return in_array($file->mime_type, $audioMimeTypes);
    }
    
    public function getVersionGroups(Project $project): \Illuminate\Support\Collection
    {
        return FileVersion::where('project_id', $project->id)
            ->with(['projectFile'])
            ->orderBy('base_name')
            ->orderBy('version_number', 'desc')
            ->get()
            ->groupBy('version_group_id')
            ->map(function ($versions) {
                $firstVersion = $versions->first();
                return (object) [
                    'id' => $firstVersion->version_group_id,
                    'base_name' => $firstVersion->base_name,
                    'versions' => $versions,
                    'latestVersion' => $versions->first(),
                    'totalVersions' => $versions->count(),
                ];
            })
            ->values();
    }
    
    public function generateDownloadFilename(FileVersion $version): string
    {
        // Return normalized filename for download
        return $version->display_name;
    }
}
```

### New Service: `AudioMetadataService`

```php
<?php

namespace App\Services;

use App\Models\ProjectFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class AudioMetadataService
{
    public function extractMetadata(ProjectFile $file): ?array
    {
        if (!$this->isAudioFile($file)) {
            return null;
        }
        
        try {
            $filePath = $this->getFilePath($file);
            
            // Use FFmpeg to extract metadata
            $result = Process::run([
                'ffprobe',
                '-v', 'quiet',
                '-print_format', 'json',
                '-show_format',
                '-show_streams',
                $filePath
            ]);
            
            if (!$result->successful()) {
                Log::warning('FFprobe failed for file', [
                    'file_id' => $file->id,
                    'error' => $result->errorOutput()
                ]);
                return null;
            }
            
            $probeData = json_decode($result->output(), true);
            
            // Extract audio stream info
            $audioStream = collect($probeData['streams'] ?? [])
                ->first(fn($stream) => $stream['codec_type'] === 'audio');
                
            if (!$audioStream) {
                return null;
            }
            
            $duration = (float) ($probeData['format']['duration'] ?? 0);
            $sampleRate = (int) ($audioStream['sample_rate'] ?? 0);
            $bitRate = (int) ($probeData['format']['bit_rate'] ?? 0);
            
            // Calculate LUFS if possible (requires additional analysis)
            $lufs = $this->calculateLUFS($filePath);
            
            return [
                'duration' => $duration,
                'duration_formatted' => $this->formatDuration($duration),
                'sample_rate' => $sampleRate,
                'bit_rate' => $bitRate,
                'bit_depth' => $this->extractBitDepth($audioStream),
                'channels' => (int) ($audioStream['channels'] ?? 0),
                'codec' => $audioStream['codec_name'] ?? null,
                'lufs' => $lufs,
                'extracted_at' => now()->toISOString(),
            ];
            
        } catch (\Exception $e) {
            Log::error('Audio metadata extraction failed', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    protected function calculateLUFS(string $filePath): ?float
    {
        try {
            // Use FFmpeg's loudnorm filter to calculate LUFS
            $result = Process::run([
                'ffmpeg',
                '-i', $filePath,
                '-af', 'loudnorm=I=-23:TP=-2:LRA=7:print_format=json',
                '-f', 'null',
                '-'
            ]);
            
            // Parse LUFS from output
            if (preg_match('/\"input_i\" : \"(-?\d+\.\d+)\"/', $result->errorOutput(), $matches)) {
                return (float) $matches[1];
            }
            
        } catch (\Exception $e) {
            Log::debug('LUFS calculation failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }
    
    protected function extractBitDepth(array $audioStream): ?int
    {
        // Try to extract bit depth from various format indicators
        if (isset($audioStream['bits_per_sample'])) {
            return (int) $audioStream['bits_per_sample'];
        }
        
        if (isset($audioStream['sample_fmt'])) {
            $sampleFormat = $audioStream['sample_fmt'];
            
            // Map common sample formats to bit depths
            $formatMap = [
                's16' => 16,
                's16p' => 16,
                's24' => 24,
                's24p' => 24,
                's32' => 32,
                's32p' => 32,
                'fltp' => 32,
                'dblp' => 64,
            ];
            
            return $formatMap[$sampleFormat] ?? null;
        }
        
        return null;
    }
    
    protected function formatDuration(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = floor($seconds % 60);
        
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        } else {
            return sprintf('%d:%02d', $minutes, $secs);
        }
    }
    
    protected function isAudioFile(ProjectFile $file): bool
    {
        $audioMimeTypes = [
            'audio/mpeg',
            'audio/wav',
            'audio/flac',
            'audio/aiff',
            'audio/ogg',
            'audio/m4a',
        ];
        
        return in_array($file->mime_type, $audioMimeTypes);
    }
    
    protected function getFilePath(ProjectFile $file): string
    {
        // Get actual file path from storage
        return storage_path('app/' . $file->storage_path);
    }
}
```

## Integration with Existing Systems

### Enhanced FileManagementService Integration

```php
// Extend existing FileManagementService

public function storeProjectFile(
    Project $project,
    string $filePath,
    string $originalFilename,
    ?User $user = null,
    array $metadata = []
): ProjectFile {
    
    // ... existing file storage logic ...
    
    $projectFile = ProjectFile::create([
        // ... existing fields ...
    ]);
    
    // Process for versioning
    $uploadNotes = $metadata['upload_notes'] ?? null;
    $uploadSource = $metadata['upload_source'] ?? 'manual';
    
    app(VersioningService::class)->processFileForVersioning(
        $projectFile,
        $uploadNotes,
        $uploadSource
    );
    
    return $projectFile;
}
```

### Download Enhancement

```php
// Enhanced download controller method

public function downloadProjectFile(Project $project, ProjectFile $file)
{
    $this->authorize('view', $project);
    
    if ($file->version_group_id) {
        // Use versioned filename for download
        $version = FileVersion::where('project_file_id', $file->id)->first();
        $downloadFilename = $version ? $version->display_name : $file->original_file_name;
    } else {
        $downloadFilename = $file->original_file_name;
    }
    
    return Storage::download($file->storage_path, $downloadFilename);
}
```

## Livewire Components

### Version Timeline Component

```php
<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\FileVersion;
use App\Services\VersioningService;
use Livewire\Component;
use Livewire\Attributes\On;

class VersionTimeline extends Component
{
    public Project $project;
    public $versionGroups = [];
    public $showComparison = false;
    public $comparisonGroup = null;
    public $comparisonVersions = [];
    public $comparisonVersionA = null;
    public $comparisonVersionB = null;
    public $abModeActive = false;
    
    public function mount(Project $project)
    {
        $this->project = $project;
        $this->loadVersionGroups();
    }
    
    #[On('refreshFiles')]
    public function refreshVersions()
    {
        $this->loadVersionGroups();
    }
    
    public function showVersionComparison(string $groupId)
    {
        $this->comparisonGroup = collect($this->versionGroups)
            ->first(fn($group) => $group->id === $groupId);
            
        if ($this->comparisonGroup) {
            $this->comparisonVersions = $this->comparisonGroup->versions;
            $this->comparisonVersionA = $this->comparisonVersions->first()->id ?? null;
            $this->comparisonVersionB = $this->comparisonVersions->skip(1)->first()->id ?? null;
            $this->showComparison = true;
        }
    }
    
    public function compareWithLatest(string $versionId)
    {
        $version = FileVersion::find($versionId);
        if (!$version) return;
        
        $this->comparisonGroup = collect($this->versionGroups)
            ->first(fn($group) => $group->id === $version->version_group_id);
            
        if ($this->comparisonGroup) {
            $this->comparisonVersions = $this->comparisonGroup->versions;
            $this->comparisonVersionA = $this->comparisonVersions->first()->id; // Latest
            $this->comparisonVersionB = $versionId;
            $this->showComparison = true;
        }
    }
    
    public function downloadLatest(string $groupId)
    {
        $group = collect($this->versionGroups)
            ->first(fn($g) => $g->id === $groupId);
            
        if ($group && $group->latestVersion) {
            return $this->downloadVersion($group->latestVersion->id);
        }
    }
    
    public function downloadVersion(string $versionId)
    {
        $version = FileVersion::find($versionId);
        if (!$version || $version->project_id !== $this->project->id) {
            return;
        }
        
        return redirect()->route('project.file.download', [
            'project' => $this->project,
            'file' => $version->projectFile
        ]);
    }
    
    public function playVersion(string $versionId)
    {
        $this->dispatch('play-file', ['fileId' => $versionId]);
    }
    
    public function syncPlayback()
    {
        $this->dispatch('sync-comparison-playback');
    }
    
    public function toggleABMode()
    {
        $this->abModeActive = !$this->abModeActive;
        $this->dispatch('toggle-ab-mode', ['active' => $this->abModeActive]);
    }
    
    protected function loadVersionGroups()
    {
        $service = app(VersioningService::class);
        $this->versionGroups = $service->getVersionGroups($this->project)->toArray();
    }
    
    public function getVersionAProperty()
    {
        if (!$this->comparisonVersionA) return null;
        return FileVersion::with('projectFile')->find($this->comparisonVersionA);
    }
    
    public function getVersionBProperty()
    {
        if (!$this->comparisonVersionB) return null;
        return FileVersion::with('projectFile')->find($this->comparisonVersionB);
    }
    
    public function render()
    {
        return view('livewire.project.version-timeline');
    }
}
```

### Enhanced Upload Handler

```php
<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Services\FileManagementService;
use Livewire\Component;
use Livewire\WithFileUploads;

class VersionedFileUploader extends Component
{
    use WithFileUploads;
    
    public Project $project;
    public $uploadNotes = '';
    public $files = [];
    
    public function mount(Project $project)
    {
        $this->project = $project;
    }
    
    public function updatedFiles()
    {
        $this->validate([
            'files.*' => 'file|max:512000', // 500MB max
        ]);
    }
    
    public function uploadFiles(FileManagementService $fileService)
    {
        $this->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'file|max:512000',
            'uploadNotes' => 'nullable|string|max:1000',
        ]);
        
        try {
            foreach ($this->files as $file) {
                $fileService->storeProjectFile(
                    $this->project,
                    $file->getRealPath(),
                    $file->getClientOriginalName(),
                    auth()->user(),
                    [
                        'upload_notes' => $this->uploadNotes,
                        'upload_source' => 'manual',
                    ]
                );
            }
            
            $this->reset(['files', 'uploadNotes']);
            $this->dispatch('refreshFiles');
            
            session()->flash('success', 'Files uploaded and versioned successfully!');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Upload failed: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        return view('livewire.project.versioned-file-uploader');
    }
}
```

## Audio Player Enhancement

### Synchronized A/B Comparison Player

```javascript
// Enhanced audio player for A/B comparison
class ABComparisonPlayer {
    constructor() {
        this.players = new Map();
        this.syncGroup = null;
        this.abMode = false;
        this.currentPlayer = 'A';
    }
    
    registerPlayer(playerId, audioElement, syncGroup) {
        this.players.set(playerId, {
            audio: audioElement,
            syncGroup: syncGroup
        });
        
        // Add event listeners for sync
        audioElement.addEventListener('timeupdate', (e) => {
            if (this.syncGroup === syncGroup && !this.abMode) {
                this.syncOtherPlayers(playerId, e.target.currentTime);
            }
        });
        
        audioElement.addEventListener('play', (e) => {
            if (this.syncGroup === syncGroup) {
                this.pauseOtherPlayers(playerId, syncGroup);
            }
        });
    }
    
    syncPlayback(syncGroup) {
        this.syncGroup = syncGroup;
        const players = Array.from(this.players.values())
            .filter(p => p.syncGroup === syncGroup);
            
        if (players.length < 2) return;
        
        // Sync to the first player's position
        const referenceTime = players[0].audio.currentTime;
        players.forEach(player => {
            player.audio.currentTime = referenceTime;
        });
    }
    
    toggleABMode(active) {
        this.abMode = active;
        
        if (active) {
            // Start A/B mode - set up rapid switching
            this.setupABSwitching();
        } else {
            // Stop A/B mode
            this.clearABSwitching();
        }
    }
    
    setupABSwitching() {
        // Switch between players every 2 seconds
        this.abInterval = setInterval(() => {
            this.switchABPlayer();
        }, 2000);
    }
    
    switchABPlayer() {
        const players = Array.from(this.players.values())
            .filter(p => p.syncGroup === this.syncGroup);
            
        if (players.length !== 2) return;
        
        const [playerA, playerB] = players;
        const currentTime = playerA.audio.currentTime;
        
        if (this.currentPlayer === 'A') {
            playerA.audio.pause();
            playerB.audio.currentTime = currentTime;
            playerB.audio.play();
            this.currentPlayer = 'B';
        } else {
            playerB.audio.pause();
            playerA.audio.currentTime = currentTime;
            playerA.audio.play();
            this.currentPlayer = 'A';
        }
    }
    
    syncOtherPlayers(sourcePlayerId, currentTime) {
        this.players.forEach((player, playerId) => {
            if (playerId !== sourcePlayerId && 
                player.syncGroup === this.syncGroup) {
                player.audio.currentTime = currentTime;
            }
        });
    }
    
    pauseOtherPlayers(sourcePlayerId, syncGroup) {
        this.players.forEach((player, playerId) => {
            if (playerId !== sourcePlayerId && 
                player.syncGroup === syncGroup) {
                player.audio.pause();
            }
        });
    }
}

// Initialize global comparison player
window.abPlayer = new ABComparisonPlayer();
```

## Testing Strategy

### Feature Tests

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\ProjectFile;
use App\Services\VersioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VersioningTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_creates_versions_for_similar_filenames()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        // Create first file
        $file1 = ProjectFile::factory()->create([
            'project_id' => $project->id,
            'original_file_name' => 'Track.wav',
        ]);
        
        $service = app(VersioningService::class);
        $version1 = $service->processFileForVersioning($file1);
        
        // Create second file with version pattern
        $file2 = ProjectFile::factory()->create([
            'project_id' => $project->id,
            'original_file_name' => 'Track_V02.wav',
        ]);
        
        $version2 = $service->processFileForVersioning($file2);
        
        // Both should be in the same version group
        $this->assertEquals($version1->version_group_id, $version2->version_group_id);
        $this->assertEquals(1, $version1->version_number);
        $this->assertEquals(2, $version2->version_number);
        $this->assertEquals('Track', $version1->base_name);
        $this->assertEquals('Track', $version2->base_name);
    }
    
    public function test_extracts_base_name_correctly()
    {
        $service = app(VersioningService::class);
        
        $testCases = [
            'Track.wav' => 'Track',
            'Track_V01.wav' => 'Track',
            'Track_v2.wav' => 'Track',
            'Track-V03.wav' => 'Track',
            'Track (V01).wav' => 'Track',
            'Track_final.wav' => 'Track',
            'Track_FINAL.wav' => 'Track',
            'Track_mix.wav' => 'Track',
            'Track_master.wav' => 'Track',
            'My Song V1.mp3' => 'My Song',
            'Complex_File_Name_V05.flac' => 'Complex_File_Name',
        ];
        
        foreach ($testCases as $filename => $expectedBaseName) {
            $baseName = $this->invokeMethod($service, 'extractBaseName', [$filename]);
            $this->assertEquals($expectedBaseName, $baseName, "Failed for filename: {$filename}");
        }
    }
    
    public function test_generates_version_display_names()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        $file = ProjectFile::factory()->create([
            'project_id' => $project->id,
            'original_file_name' => 'MyTrack.wav',
        ]);
        
        $service = app(VersioningService::class);
        $version = $service->processFileForVersioning($file, 'Initial version');
        
        $this->assertEquals('MyTrack_V01.wav', $version->display_name);
        $this->assertEquals('Initial version', $version->upload_notes);
    }
    
    public function test_version_timeline_component()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        // Create multiple versions
        $files = collect(['Track.wav', 'Track_V02.wav', 'Track_V03.wav'])
            ->map(fn($name) => ProjectFile::factory()->create([
                'project_id' => $project->id,
                'original_file_name' => $name,
            ]));
        
        $service = app(VersioningService::class);
        $files->each(fn($file) => $service->processFileForVersioning($file));
        
        $this->actingAs($user);
        
        Livewire::test(\App\Livewire\Project\VersionTimeline::class, ['project' => $project])
            ->assertStatus(200)
            ->assertSee('Track')
            ->assertSee('V01')
            ->assertSee('V02')
            ->assertSee('V03')
            ->assertSee('3 versions');
    }
    
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }
}
```

### Unit Tests for Audio Metadata

```php
<?php

namespace Tests\Unit;

use App\Services\AudioMetadataService;
use App\Models\ProjectFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AudioMetadataServiceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_extracts_audio_metadata()
    {
        $service = app(AudioMetadataService::class);
        
        // Create a test audio file
        $file = ProjectFile::factory()->create([
            'mime_type' => 'audio/wav',
            'storage_path' => 'test-files/sample.wav', // Assumes test file exists
        ]);
        
        $metadata = $service->extractMetadata($file);
        
        $this->assertNotNull($metadata);
        $this->assertArrayHasKey('duration', $metadata);
        $this->assertArrayHasKey('sample_rate', $metadata);
        $this->assertArrayHasKey('bit_rate', $metadata);
        $this->assertArrayHasKey('duration_formatted', $metadata);
    }
    
    public function test_returns_null_for_non_audio_files()
    {
        $service = app(AudioMetadataService::class);
        
        $file = ProjectFile::factory()->create([
            'mime_type' => 'application/pdf',
        ]);
        
        $metadata = $service->extractMetadata($file);
        
        $this->assertNull($metadata);
    }
}
```

## Implementation Steps

### Phase 1: Core Versioning Logic (Week 1)
1. Create database migrations for versioning tables
2. Implement `VersioningService` with filename parsing and version detection
3. Integrate versioning into existing `FileManagementService`
4. Create basic version models and relationships

### Phase 2: Audio Metadata Processing (Week 2)
1. Implement `AudioMetadataService` with FFmpeg integration
2. Add LUFS calculation and audio analysis
3. Create background jobs for metadata extraction
4. Integrate metadata display into existing file listings

### Phase 3: Version Timeline UI (Week 3)
1. Create `VersionTimeline` Livewire component
2. Implement version grouping and display logic
3. Add download functionality with versioned filenames
4. Style with Flux UI components following UX guidelines

### Phase 4: A/B Comparison System (Week 4)
1. Build comparison modal and interface
2. Implement synchronized audio playback
3. Create A/B switching functionality
4. Add LUFS difference calculations and display

### Phase 5: Enhanced Features (Week 5)
1. Add version notes and upload source tracking
2. Implement batch version operations
3. Create version comparison analytics
4. Add export functionality for version reports

## Migration Strategy

### Existing File Processing
- Process existing project files to create version records
- Detect and group similar filenames automatically
- Preserve original upload dates and metadata
- Handle edge cases and duplicates gracefully

### Backwards Compatibility
- Maintain existing file download functionality
- Support both versioned and non-versioned files
- Preserve existing file URLs and access patterns
- Gradual rollout with feature flags

This implementation creates a professional versioning system that maintains file organization while providing powerful comparison tools for audio professionals to make informed decisions about their work.