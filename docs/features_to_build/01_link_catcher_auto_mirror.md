# Link Catcher & Auto-Mirror Implementation Plan

## Overview

Transform the client portal upload experience by adding URL-based file import functionality. This feature allows clients to paste sharing links (WeTransfer, Google Drive, Dropbox, etc.) instead of re-uploading files, solving the common problem of expired links and providing automatic mirroring into MixPitch's storage.

Key technical direction:
- Direct-to-S3 multipart uploads for imports (no buffering full files on app servers)
- WeTransfer-first support (no official API), with other providers leveraging official APIs in later phases
- Robust security (SSRF protections, redirect validation), private broadcasting for progress, and DB portability
## UX/UI Implementation

### Client Portal Integration

**Location**: Extend existing client portal upload functionality  
**Current**: Single "Upload" interface in client portal  
**New**: Tabbed interface with "Upload" | "Import From Link"

```blade
{{-- Enhanced client portal upload component --}}
<div class="bg-white rounded-lg border border-slate-200">
    <flux:tabs wire:model="activeTab" class="w-full">
        <flux:tab name="upload" label="Upload Files">
            {{-- Existing UppyFileUploader component --}}
            <livewire:uppy-file-uploader :model="$project" :config="$uploadConfig" />
        </flux:tab>
        
        <flux:tab name="import" label="Import From Link">
            <livewire:link-importer :model="$project" />
        </flux:tab>
    </flux:tabs>
</div>
```

### Microcopy & Messaging

**Upload Tab Messaging**:
```html
<flux:callout variant="info" class="mb-4">
    <flux:icon name="upload" size="sm" />
    <div class="ml-3">
        <h3 class="text-sm font-medium">Best: Upload files here</h3>
        <p class="text-sm text-slate-600">Fastest transfers and automatic versioning</p>
    </div>
</flux:callout>
```

**Import Tab Messaging**:
```html
<flux:callout variant="primary" class="mb-4">
    <flux:icon name="link" size="sm" />
    <div class="ml-3">
        <h3 class="text-sm font-medium">Already sent a link?</h3>
        <p class="text-sm text-slate-600">Paste a WeTransfer/Drive/Dropbox link and we'll mirror it safely into your project (no more expired links)</p>
    </div>
</flux:callout>
```

### Link Import Interface

```blade
{{-- Link import form --}}
<flux:field>
    <flux:label>Share Link URL</flux:label>
    <flux:input 
        wire:model.defer="importUrl" 
        placeholder="https://wetransfer.com/downloads/..." 
        type="url"
        class="font-mono text-sm"
    />
    <flux:error name="importUrl" />
    <flux:text size="sm" class="text-slate-500 mt-1">
        Supported: WeTransfer, Google Drive, Dropbox, OneDrive
    </flux:text>
</flux:field>

<flux:button 
    wire:click="importFromLink" 
    wire:loading.attr="disabled"
    variant="primary"
    class="mt-4"
>
    <span wire:loading.remove>Import Files</span>
    <span wire:loading>Analyzing Link...</span>
</flux:button>

{{-- Progress display --}}
<div wire:loading.remove wire:target="importFromLink" class="mt-4" x-show="$wire.importProgress.active">
    <div class="bg-slate-50 rounded-lg p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium">Importing files...</span>
            <span class="text-sm text-slate-600" x-text="$wire.importProgress.completed + '/' + $wire.importProgress.total"></span>
        </div>
        <div class="w-full bg-slate-200 rounded-full h-2">
            <div class="bg-indigo-600 h-2 rounded-full transition-all duration-300" 
                 :style="'width: ' + ($wire.importProgress.completed / $wire.importProgress.total * 100) + '%'">
            </div>
        </div>
        <div class="mt-2" x-show="$wire.importProgress.currentFile">
            <flux:text size="sm" class="text-slate-600" x-text="'Importing: ' + $wire.importProgress.currentFile"></flux:text>
        </div>
    </div>
</div>
```

## Database Schema

### New Table: `link_imports`

```php
Schema::create('link_imports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('source_url', 2000);
    $table->string('source_domain');
    $table->json('detected_files'); // Array of file metadata from link analysis
    $table->json('imported_files')->nullable(); // Optional cache of successfully imported ProjectFile IDs (can be derived)
    $table->string('status'); // Use string for portability across DB drivers
    $table->text('error_message')->nullable();
    $table->json('metadata')->nullable(); // Additional info like file counts, sizes, etc.
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
    
    $table->index(['project_id', 'status']);
    $table->index(['user_id', 'created_at']);
    $table->index('source_domain');
});
```

### New Table: `imported_files`

```php
Schema::create('imported_files', function (Blueprint $table) {
    $table->id();
    $table->foreignId('link_import_id')->constrained()->onDelete('cascade');
    $table->foreignId('project_file_id')->constrained()->onDelete('cascade');
    $table->string('source_filename');
    $table->string('source_url', 2000);
    $table->bigInteger('size_bytes');
    $table->string('mime_type');
    $table->timestamp('imported_at');
    $table->timestamps();
    
    $table->index(['link_import_id', 'imported_at']);
});
```

### Extend `project_files` table

```php
Schema::table('project_files', function (Blueprint $table) {
    $table->string('import_source')->nullable()->after('metadata'); // 'upload', 'link_import', 'email_attachment'
    // No checksum tracking needed when uploading directly to S3
});
```

## Service Layer Architecture

### New Service: `LinkImportService`

```php
<?php

namespace App\Services;

use App\Models\LinkImport;
use App\Models\Project;
use App\Jobs\ProcessLinkImport;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LinkImportService
{
    protected FileManagementService $fileManagementService;
    protected FileSecurityService $fileSecurityService;
    
    public function __construct(
        FileManagementService $fileManagementService,
        FileSecurityService $fileSecurityService
    ) {
        $this->fileManagementService = $fileManagementService;
        $this->fileSecurityService = $fileSecurityService;
    }
    
    public function createImport(Project $project, string $url, $user): LinkImport
    {
        // Authorization
        abort_unless($user->can('update', $project), 403);

        $this->validateUrl($url);
        $this->checkRateLimits($project, $user);
        
        $domain = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        
        $import = LinkImport::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'source_url' => $url,
            'source_domain' => $domain,
            'status' => 'pending',
            'detected_files' => [],
        ]);
        
        // Dispatch background job
        ProcessLinkImport::dispatch($import);
        
        return $import;
    }
    
    protected function validateUrl(string $url): void
    {
        $validator = Validator::make(['url' => $url], [
            'url' => ['required', 'url', 'max:2000'],
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $allowedDomains = (array) config('linkimport.allowed_domains', []);
        $isAllowed = collect($allowedDomains)->contains(fn ($d) => str_ends_with($host, $d));
        if (!$isAllowed) {
            throw ValidationException::withMessages([
                'url' => ['Domain not supported. Supported: ' . implode(', ', $allowedDomains)],
            ]);
        }
    }
    
    protected function checkRateLimits(Project $project, $user): void
    {
        $limits = config('linkimport.rate_limits');

        // Check per-project rate limits
        $recentImports = LinkImport::where('project_id', $project->id)
            ->where('created_at', '>', now()->subHour())
            ->count();
            
        if ($recentImports >= ($limits['per_project_per_hour'] ?? 5)) {
            throw ValidationException::withMessages([
                'url' => ['Too many imports for this project. Please wait before importing more links.']],
            );
        }
        
        // Check per-user rate limits
        $userImports = LinkImport::where('user_id', $user->id)
            ->where('created_at', '>', now()->subHour())
            ->count();
            
        if ($userImports >= ($limits['per_user_per_hour'] ?? 10)) {
            throw ValidationException::withMessages([
                'url' => ['Too many imports. Please wait before importing more links.']],
            );
        }
    }
}
```

### New Service: `LinkAnalysisService`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LinkAnalysisService
{
    public function analyzeLink(string $url): array
    {
        $domain = parse_url($url, PHP_URL_HOST);
        
        return match($domain) {
            'wetransfer.com', 'we.tl' => $this->analyzeWeTransfer($url),
            'drive.google.com' => $this->analyzeGoogleDrive($url),
            'dropbox.com', 'db.tt' => $this->analyzeDropbox($url),
            '1drv.ms', 'onedrive.live.com' => $this->analyzeOneDrive($url),
            default => throw new \InvalidArgumentException("Unsupported domain: {$domain}")
        };
    }
    
    protected function analyzeWeTransfer(string $url): array
    {
        // WeTransfer API or web scraping approach
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'MixPitch-LinkImporter/1.0',
                ])
                ->get($url);
                
            if (!$response->successful()) {
                throw new \Exception('Failed to access WeTransfer link');
            }
            
            // Parse HTML to extract file information
            $html = $response->body();
            return $this->parseWeTransferHtml($html);
            
        } catch (\Exception $e) {
            Log::error('WeTransfer analysis failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    protected function parseWeTransferHtml(string $html): array
    {
        // Implementation would extract file metadata from WeTransfer's HTML
        // This is a simplified example - real implementation would be more robust
        
        preg_match_all('/data-filename="([^"]+)"/', $html, $filenames);
        preg_match_all('/data-filesize="(\d+)"/', $html, $filesizes);
        
        $files = [];
        for ($i = 0; $i < count($filenames[1]); $i++) {
            $files[] = [
                'filename' => $filenames[1][$i] ?? 'unknown',
                'size' => (int) ($filesizes[1][$i] ?? 0),
                'mime_type' => $this->guessMimeType($filenames[1][$i] ?? ''),
            ];
        }
        
        return $files;
    }
    
    protected function guessMimeType(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        return match($extension) {
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'flac' => 'audio/flac',
            'aiff' => 'audio/aiff',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream'
        };
    }
}
```

## Queue Jobs

### Background Processing Job

```php
<?php

namespace App\Jobs;

use App\Models\LinkImport;
use App\Services\LinkAnalysisService;
use App\Services\FileManagementService;
use App\Services\FileSecurityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessLinkImport implements ShouldQueue, \Illuminate\Contracts\Queue\ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $timeout = 1800; // 30 minutes
    public $tries = 3;
    
    public function __construct(
        protected LinkImport $linkImport
    ) {}
    
    public function uniqueId(): string
    {
        return 'link-import:' . $this->linkImport->id;
    }

    public function handle(
        LinkAnalysisService $analysisService,
        FileManagementService $fileService,
        FileSecurityService $securityService
    ): void {
        try {
            $this->linkImport->update([
                'status' => 'analyzing',
                'started_at' => now(),
            ]);
            
            // Step 1: Analyze the link to get file metadata
            $files = $analysisService->analyzeLink($this->linkImport->source_url);
            
            $meta = $this->linkImport->metadata ?? [];
            $meta['progress'] = ['completed' => 0, 'total' => count($files), 'current_file' => ''];
            $this->linkImport->update([
                'detected_files' => $files,
                'metadata' => $meta,
                'status' => 'importing',
            ]);
            event(new \App\Events\LinkImportUpdated($this->linkImport->id, $this->linkImport->project_id));
            
            // Step 2: Download and import each file
            $importedFiles = [];
            
            foreach ($files as $index => $fileInfo) {
                try {
                    $projectFile = $this->importSingleFile($fileInfo, $fileService, $securityService);
                    $importedFiles[] = $projectFile->id;
                    
                    // Update progress
                    $meta = $this->linkImport->metadata ?? [];
                    $meta['progress'] = [
                        'completed' => count($importedFiles),
                        'total' => count($files),
                        'current_file' => $fileInfo['filename'] ?? 'Unknown',
                    ];
                    $this->linkImport->update(['metadata' => $meta]);
                    event(new \App\Events\LinkImportUpdated($this->linkImport->id, $this->linkImport->project_id));
                    
                } catch (\Exception $e) {
                    Log::error('Failed to import file from link', [
                        'link_import_id' => $this->linkImport->id,
                        'file_info' => $fileInfo,
                        'error' => $e->getMessage()
                    ]);
                    // Continue with other files
                }
            }
            
            $this->linkImport->update([
                'status' => 'completed',
                'imported_files' => $importedFiles,
                'completed_at' => now(),
            ]);
            
            // Dispatch event for UI updates
            event(new \App\Events\LinkImportCompleted($this->linkImport->id, $this->linkImport->project_id));
            
        } catch (\Exception $e) {
            $this->linkImport->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            
            Log::error('Link import failed', [
                'link_import_id' => $this->linkImport->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    protected function importSingleFile(
        array $fileInfo, 
        FileManagementService $fileService,
        FileSecurityService $securityService
    ): \App\Models\ProjectFile {
        // 1) Generate a direct download URL from provider (follows redirects with validation)
        $downloadUrl = $this->generateDownloadUrl($fileInfo);

        // 2) Preflight: try to get size via HEAD
        $head = Http::timeout(15)->withHeaders([
            'User-Agent' => 'MixPitch-LinkImporter/1.0',
        ])->head($downloadUrl);
        $size = (int) $head->header('Content-Length', 0);

        // 3) Initialize multipart upload and generate presigned part URLs
        $multipart = $fileService->initiateMultipartUpload(
            project: $this->linkImport->project,
            filename: (string) ($fileInfo['filename'] ?? 'file'),
            mimeType: (string) ($fileInfo['mime_type'] ?? 'application/octet-stream'),
            sizeBytes: $size,
            metadata: [
                'import_source' => 'link_import',
                'source_url' => $this->linkImport->source_url,
            ],
        );

        // 4) Ask edge worker to fetch remote and PUT parts to S3 using presigned URLs
        $workerResponse = Http::timeout(1800)->asJson()->post(
            config('linkimport.remote_fetch_worker.endpoint'),
            [
                'sourceUrl' => $downloadUrl,
                'parts' => $multipart['parts'], // [{url, number, size}]
                'chunkSize' => $multipart['chunk_size'],
                'maxRedirects' => config('linkimport.security.max_redirects', 3),
                'allowedHosts' => config('linkimport.allowed_domains'),
            ]
        );

        if (!$workerResponse->successful()) {
            throw new \RuntimeException('Remote fetch worker failed to upload parts.');
        }

        $result = $workerResponse->json(); // { etags: [{part, etag}] }

        // 5) Complete multipart upload
        $completed = $fileService->completeMultipartUpload(
            $multipart['upload_id'],
            $result['etags'] ?? []
        );

        // 6) Create project file record and imported file record
        $projectFile = $fileService->finalizeStoredObject(
            project: $this->linkImport->project,
            s3Key: $completed['key'],
            originalFilename: (string) ($fileInfo['filename'] ?? 'file'),
            user: $this->linkImport->user,
            metadata: [
                'import_source' => 'link_import',
                'source_url' => $this->linkImport->source_url,
            ],
        );

        \App\Models\ImportedFile::create([
            'link_import_id' => $this->linkImport->id,
            'project_file_id' => $projectFile->id,
            'source_filename' => (string) ($fileInfo['filename'] ?? 'file'),
            'source_url' => $downloadUrl,
            'size_bytes' => (int) ($size ?: ($completed['size'] ?? 0)),
            'mime_type' => (string) ($fileInfo['mime_type'] ?? 'application/octet-stream'),
            'imported_at' => now(),
        ]);

        // 7) Trigger async scan of the S3 object
        $securityService->scanS3ObjectAsync($completed['key']);

        return $projectFile;
    }
    
    protected function generateDownloadUrl(array $fileInfo): string
    {
        // Implementation depends on the service. For WeTransfer, resolve final redirected URL
        // and validate the final host is still in the allowed list.
        throw new \Exception('Download URL generation not implemented for this service');
    }
}
```

### Remote Fetch Worker (Edge)

We will offload the remote file download and multipart part uploads to an edge worker (e.g., Cloudflare Worker) to avoid routing large payloads through the app servers. The backend prepares presigned part URLs and the worker streams the source to S3 directly.

High-level worker flow:

```javascript
// Cloudflare Worker (pseudo)
export default {
  async fetch(req) {
    const { sourceUrl, parts, chunkSize, maxRedirects, allowedHosts } = await req.json();
    // 1) Validate source host matches allowedHosts (suffix check)
    // 2) Fetch source with streaming, limited redirects
    // 3) Slice stream into chunks, PUT to parts[i].url, collect ETags
    // 4) Return { etags: [{part, etag}] }
  }
}
```

## Livewire Components

### Main Import Component

```php
<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\LinkImport;
use App\Services\LinkImportService;
use Livewire\Component;
use Livewire\Attributes\On;
use Masmerise\Toaster\Toaster;

class LinkImporter extends Component
{
    public Project $project;
    public string $importUrl = '';
    public ?LinkImport $activeImport = null;
    public array $importProgress = [
        'active' => false,
        'completed' => 0,
        'total' => 0,
        'currentFile' => ''
    ];
    
    protected $rules = [
        'importUrl' => 'required|url|max:2000',
    ];
    
    public function mount(Project $project)
    {
        $this->project = $project;
        $this->checkActiveImport();
    }
    
    public function importFromLink(LinkImportService $service)
    {
        $this->validate();
        
        try {
            $this->activeImport = $service->createImport(
                $this->project, 
                $this->importUrl, 
                auth()->user()
            );
            
            $this->importUrl = '';
            $this->importProgress['active'] = true;
            
            Toaster::success('Import started! We\'ll process your files in the background.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('importUrl', $e->validator->errors()->first('url'));
        } catch (\Exception $e) {
            Toaster::error('Failed to start import: ' . $e->getMessage());
        }
    }
    
    #[On('echo:project.{project.id},LinkImportUpdated')]
    public function handleImportUpdate($data)
    {
        if ($this->activeImport && $this->activeImport->id == $data['import_id']) {
            $this->activeImport->refresh();
            $this->updateProgress();
        }
    }
    
    #[On('echo:project.{project.id},LinkImportCompleted')]
    public function handleImportCompleted($data)
    {
        if ($this->activeImport && $this->activeImport->id == $data['import_id']) {
            $this->activeImport->refresh();
            $this->importProgress['active'] = false;
            
            $count = count($this->activeImport->imported_files ?? []);
            Toaster::success("Import completed! {$count} files added to your project.");
            
            $this->activeImport = null;
            $this->dispatch('refreshFiles');
        }
    }
    
    protected function checkActiveImport()
    {
        $this->activeImport = LinkImport::where('project_id', $this->project->id)
            ->whereIn('status', ['pending', 'analyzing', 'importing'])
            ->latest()
            ->first();
            
        if ($this->activeImport) {
            $this->importProgress['active'] = true;
            $this->updateProgress();
        }
    }
    
    protected function updateProgress()
    {
        if (!$this->activeImport) return;
        
        $metadata = $this->activeImport->metadata ?? [];
        $progress = $metadata['progress'] ?? [];
        
        $this->importProgress = [
            'active' => in_array($this->activeImport->status, ['pending', 'analyzing', 'importing']),
            'completed' => $progress['completed'] ?? 0,
            'total' => $progress['total'] ?? count($this->activeImport->detected_files ?? []),
            'currentFile' => $progress['current_file'] ?? ''
        ];
    }
    
    public function render()
    {
        return view('livewire.link-importer');
    }
}
```

## Broadcasting & Authorization

```php
// routes/channels.php
Broadcast::channel('project.{projectId}', function ($user, $projectId) {
    $project = \App\Models\Project::find($projectId);
    return $project && $user->can('view', $project);
});

// App\Events\LinkImportUpdated implements ShouldBroadcast
public function broadcastOn(): array
{
    return [new PrivateChannel('project.' . $this->projectId)];
}

// Event payload example
public function broadcastWith(): array
{
    return ['import_id' => $this->importId];
}
```

## Security & Abuse Prevention

### File Security Service Integration

```php
// Extend existing FileSecurityService to support scanning by S3 object key

public function scanS3ObjectAsync(string $s3Key): void
{
    // Option A: dispatch a queued job that downloads and scans in an isolated worker
    // Option B: trigger Lambda / external scanner subscribed to S3 events
}

public function detectMimeTypeFromObject(string $s3Key): string
{
    // Use S3 headObject or stream + finfo to detect
}
```

Additional protections:
- Enforce strict allowlist and re-validate the final host after redirects.
- Limit redirects and timeouts, set explicit User-Agent.
- Avoid logging sensitive tokens in source URLs.

### Rate Limiting Configuration

```php
// config/linkimport.php

return [
    'allowed_domains' => [
        'wetransfer.com',
        'we.tl',
        'drive.google.com',
        'dropbox.com',
        'db.tt',
        '1drv.ms',
        'onedrive.live.com',
    ],
    
    'rate_limits' => [
        'per_project_per_hour' => 5,
        'per_user_per_hour' => 10,
        'per_user_per_day' => 50,
    ],
    
    'security' => [
        'max_file_size' => 500 * 1024 * 1024, // 500MB
        'scan_with_clamav' => env('LINK_IMPORT_SCAN_ENABLED', true),
        'max_redirects' => 3,
        'allowed_mime_types' => [
            'audio/mpeg',
            'audio/wav',
            'audio/flac',
            'audio/aiff',
            'application/pdf',
            'application/zip',
            'image/jpeg',
            'image/png',
        ],
    ],

    // Remote fetch worker (edge) config
    'remote_fetch_worker' => [
        'endpoint' => env('LINK_IMPORT_WORKER_URL', 'https://worker.example.com/fetch'),
        'chunk_size' => 5 * 1024 * 1024, // 5MB parts by default
        'presign_ttl' => 900, // seconds
    ],
];
```

## Testing Strategy

### Feature Tests

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\LinkImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinkImportTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_create_link_import()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        Http::fake([
            'wetransfer.com/*' => Http::response('mock html content', 200),
        ]);
        
        \Illuminate\Support\Facades\Queue::fake();

        $service = app(LinkImportService::class);
        $import = $service->createImport(
            $project,
            'https://wetransfer.com/downloads/test',
            $user
        );
        
        $this->assertDatabaseHas('link_imports', [
            'id' => $import->id,
            'project_id' => $project->id,
            'user_id' => $user->id,
            'source_url' => 'https://wetransfer.com/downloads/test',
            'source_domain' => 'wetransfer.com',
            'status' => 'pending',
        ]);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ProcessLinkImport::class);
    }
    
    public function test_rejects_unsupported_domains()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        $service = app(LinkImportService::class);
        
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        $service->createImport(
            $project,
            'https://malicious-site.com/file',
            $user
        );
    }
    
    public function test_enforces_rate_limits()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        $service = app(LinkImportService::class);
        
        // Create 5 imports (hitting the limit)
        for ($i = 0; $i < 5; $i++) {
            $service->createImport(
                $project,
                "https://wetransfer.com/downloads/test{$i}",
                $user
            );
        }
        
        // 6th should fail
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->createImport(
            $project,
            'https://wetransfer.com/downloads/test6',
            $user
        );
    }
}
```

### Livewire Component Tests

```php
<?php

namespace Tests\Feature\Livewire;

use App\Livewire\LinkImporter;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LinkImporterTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_renders_successfully()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        $this->actingAs($user);
        
        Livewire::test(LinkImporter::class, ['project' => $project])
            ->assertStatus(200)
            ->assertSee('Share Link URL')
            ->assertSee('Supported: WeTransfer, Google Drive');
    }
    
    public function test_can_submit_valid_url()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        $this->actingAs($user);
        
        Livewire::test(LinkImporter::class, ['project' => $project])
            ->set('importUrl', 'https://wetransfer.com/downloads/test')
            ->call('importFromLink')
            ->assertHasNoErrors()
            ->assertDispatched('refreshFiles');
            
        $this->assertDatabaseHas('link_imports', [
            'project_id' => $project->id,
            'source_url' => 'https://wetransfer.com/downloads/test',
        ]);
    }
    
    public function test_validates_url_format()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        $this->actingAs($user);
        
        Livewire::test(LinkImporter::class, ['project' => $project])
            ->set('importUrl', 'not-a-url')
            ->call('importFromLink')
            ->assertHasErrors(['importUrl']);
    }
}
```

## Implementation Steps

### Phase 1: Foundation (Week 1)
1. Create database migrations for `link_imports` and `imported_files` tables (portable types, indexes)
2. Implement basic `LinkImportService` with validation, authorization, and rate limiting (from config)
3. Create `LinkAnalysisService` with WeTransfer-first link analysis (public links only)
4. Add `config/linkimport.php` with security, worker, and rate-limit settings
5. Add private broadcast channel auth in `routes/channels.php`

### Phase 2: Core Functionality (Week 2)  
1. Implement `ProcessLinkImport` job with unique guard and progress broadcasting
2. Implement edge Remote Fetch Worker to stream source → S3 via presigned multipart
3. Update `FileManagementService` to init/complete multipart and finalize objects
4. Finalize S3 multipart uploads and persist `ImportedFile` records
5. Create audit logging for import activities

### Phase 3: UI Implementation (Week 3)
1. Create `LinkImporter` Livewire component
2. Implement tabbed interface in client portal
3. Add real-time progress tracking with WebSocket events (`LinkImportUpdated`/`Completed`)
4. Style with Flux UI components following UX guidelines

### Phase 4: Additional Services (Week 4)
1. Add Google Drive support via API (with OAuth for private files)
2. Add Dropbox support via API  
3. Add OneDrive support via API
4. Implement comprehensive error handling and retry logic (per-file)

### Phase 5: Testing & Security (Week 5)
1. Write comprehensive test suite (Feature, Unit, Livewire), including broadcasting and worker coordination
2. Security audit and SSRF validation; confirm provider ToS compliance
3. Performance optimization (part size tuning, worker concurrency)
4. Documentation and deployment preparation for worker and config

## Monitoring & Analytics

### Import Success Tracking
- Track success rates by domain
- Monitor average import times
- Alert on failed imports

### Usage Analytics
- Most popular sharing services
- Peak import times
- User adoption rates

This comprehensive implementation provides a secure, user-friendly way for clients to import files from popular sharing services while maintaining MixPitch's high standards for file management and security.