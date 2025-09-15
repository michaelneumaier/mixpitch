# DAW Uploader Scripts Implementation Plan

## Feature Overview

The DAW Uploader Scripts feature enables musicians and producers to upload files directly from their Digital Audio Workstation (DAW) applications to MixPitch projects with a single click. This streamlines the creative workflow by eliminating the need to export, locate, and manually upload files through the web interface.

### Core Functionality
- **Direct DAW Integration**: One-click upload from Reaper, Pro Tools, Logic Pro, Ableton Live, Studio One
- **Project Token Authentication**: Secure, project-specific upload tokens
- **Automatic Version Assignment**: Smart file naming and version management
- **Real-time Progress Feedback**: Upload status and confirmation in DAW
- **Multi-platform Support**: Cross-platform scripts (Windows, macOS, Linux)

## Technical Architecture

### Database Schema

```sql
-- DAW integration tokens and configurations
CREATE TABLE daw_upload_tokens (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    permissions JSON NOT NULL DEFAULT '["upload_files"]',
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_project (user_id, project_id),
    INDEX idx_active_expires (is_active, expires_at)
);

-- DAW upload activity tracking
CREATE TABLE daw_upload_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    token_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    project_file_id BIGINT UNSIGNED NULL,
    daw_application VARCHAR(100) NOT NULL,
    original_filename VARCHAR(500) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    upload_duration_ms INT UNSIGNED NULL,
    status ENUM('success', 'failed', 'partial') NOT NULL,
    error_message TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (token_id) REFERENCES daw_upload_tokens(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (project_file_id) REFERENCES project_files(id) ON DELETE SET NULL,
    INDEX idx_token_date (token_id, created_at),
    INDEX idx_project_status (project_id, status)
);

-- DAW application configurations
CREATE TABLE daw_configurations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    daw_name VARCHAR(100) NOT NULL,
    config_data JSON NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_daw (user_id, daw_name),
    INDEX idx_user_enabled (user_id, is_enabled)
);
```

### Service Architecture

#### DAWIntegrationService
```php
<?php

namespace App\Services;

use App\Models\DAWUploadToken;
use App\Models\DAWUploadLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DAWIntegrationService
{
    public function createUploadToken(
        User $user,
        Project $project,
        string $name,
        ?Carbon $expiresAt = null,
        array $permissions = ['upload_files']
    ): DAWUploadToken {
        // Revoke existing active tokens for same project to maintain security
        DAWUploadToken::where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return DAWUploadToken::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'token' => Str::random(64),
            'name' => $name,
            'permissions' => $permissions,
            'expires_at' => $expiresAt ?? now()->addDays(30),
            'is_active' => true,
        ]);
    }

    public function validateToken(string $token): ?DAWUploadToken
    {
        return DAWUploadToken::where('token', $token)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    public function processDAWUpload(
        DAWUploadToken $token,
        string $dawApplication,
        string $originalFilename,
        $uploadedFile
    ): array {
        $startTime = microtime(true);
        
        try {
            // Use existing FileManagementService for consistent file handling
            $fileManagementService = app(FileManagementService::class);
            
            // Determine upload context (existing pattern)
            $context = FileUploadSetting::CONTEXT_PROJECTS;
            
            // Validate file against project upload settings
            $this->validateFileForProject($token->project, $uploadedFile);
            
            // Process file upload with version management
            $projectFile = $fileManagementService->storeProjectFile(
                $token->project,
                $uploadedFile,
                $token->user,
                $this->generateDAWFilename($originalFilename, $token->project)
            );
            
            // Update token last used timestamp
            $token->update(['last_used_at' => now()]);
            
            // Log successful upload
            $this->logDAWUpload($token, $dawApplication, $originalFilename, $uploadedFile->getSize(), $startTime, 'success', $projectFile->id);
            
            return [
                'success' => true,
                'file_id' => $projectFile->id,
                'filename' => $projectFile->display_name,
                'version' => $projectFile->version_number,
                'upload_url' => route('projects.files.show', [$token->project, $projectFile]),
                'project_url' => route('projects.show', $token->project)
            ];
            
        } catch (\Exception $e) {
            // Log failed upload
            $this->logDAWUpload($token, $dawApplication, $originalFilename, $uploadedFile->getSize(), $startTime, 'failed', null, $e->getMessage());
            
            throw $e;
        }
    }

    public function generateInstallationScript(DAWUploadToken $token, string $dawType): string
    {
        $scriptTemplates = [
            'reaper' => $this->generateReaperScript($token),
            'protools' => $this->generateProToolsScript($token),
            'logic' => $this->generateLogicScript($token),
            'ableton' => $this->generateAbletonScript($token),
            'studio_one' => $this->generateStudioOneScript($token),
        ];

        return $scriptTemplates[$dawType] ?? throw new \InvalidArgumentException("Unsupported DAW type: {$dawType}");
    }

    private function validateFileForProject(Project $project, $file): void
    {
        $settings = FileUploadSetting::getEffectiveSettings(FileUploadSetting::CONTEXT_PROJECTS);
        
        // Check file size
        if ($file->getSize() > ($settings['max_file_size_mb'] * 1024 * 1024)) {
            throw new \InvalidArgumentException("File size exceeds limit of {$settings['max_file_size_mb']}MB");
        }
        
        // Check file type
        $allowedMimes = $settings['allowed_file_types'] ?? ['audio/*'];
        if (!in_array($file->getMimeType(), $allowedMimes) && !in_array('audio/*', $allowedMimes)) {
            throw new \InvalidArgumentException("File type not allowed: {$file->getMimeType()}");
        }
    }

    private function generateDAWFilename(string $originalFilename, Project $project): string
    {
        // Clean filename and add project context
        $cleanName = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $originalFilename);
        $projectSlug = Str::slug($project->name);
        
        // Check for existing files to determine version
        $existingCount = $project->projectFiles()
            ->where('original_filename', 'LIKE', $cleanName . '%')
            ->count();
        
        if ($existingCount > 0) {
            $version = sprintf('V%02d', $existingCount + 1);
            $pathInfo = pathinfo($cleanName);
            $cleanName = $pathInfo['filename'] . "_{$version}." . $pathInfo['extension'];
        }
        
        return "{$projectSlug}_{$cleanName}";
    }

    private function logDAWUpload(
        DAWUploadToken $token,
        string $dawApplication,
        string $originalFilename,
        int $fileSize,
        float $startTime,
        string $status,
        ?int $projectFileId = null,
        ?string $errorMessage = null
    ): void {
        DAWUploadLog::create([
            'token_id' => $token->id,
            'user_id' => $token->user_id,
            'project_id' => $token->project_id,
            'project_file_id' => $projectFileId,
            'daw_application' => $dawApplication,
            'original_filename' => $originalFilename,
            'file_size' => $fileSize,
            'upload_duration_ms' => round((microtime(true) - $startTime) * 1000),
            'status' => $status,
            'error_message' => $errorMessage,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    private function generateReaperScript(DAWUploadToken $token): string
    {
        $apiUrl = route('api.daw.upload');
        $projectUrl = route('projects.show', $token->project);
        
        return <<<LUA
-- MixPitch DAW Uploader for REAPER
-- Install: Copy to REAPER/Scripts/ folder
-- Usage: Actions -> Run script -> MixPitch_Upload.lua

local mixpitch_token = "{$token->token}"
local mixpitch_api = "{$apiUrl}"
local project_url = "{$projectUrl}"

function upload_project_file()
    -- Get current project file path
    local project_path = reaper.GetProjectPath("")
    if project_path == "" then
        reaper.ShowMessageBox("Please save your project first", "MixPitch Upload", 0)
        return
    end
    
    -- Get project filename
    local project_name = reaper.GetProjectName(0, "")
    if project_name == "" then
        project_name = "untitled_project.rpp"
    end
    
    -- Render current project to temp file
    local render_path = project_path .. "/mixpitch_export.wav"
    
    -- Set render settings for mixdown
    reaper.GetSetProjectInfo(0, "RENDER_FILE", render_path, true)
    reaper.GetSetProjectInfo(0, "RENDER_CHANNELS", 2, true)
    reaper.GetSetProjectInfo(0, "RENDER_SRATE", 44100, true)
    
    -- Start render
    reaper.Main_OnCommand(42230, 0) -- Render project to file
    
    -- Wait for render completion (simplified)
    reaper.defer(function()
        upload_file_to_mixpitch(render_path, project_name)
    end)
end

function upload_file_to_mixpitch(file_path, filename)
    -- Use curl command to upload file
    local curl_cmd = string.format(
        'curl -X POST "%s" -H "Authorization: Bearer %s" -H "X-DAW-Application: REAPER" -F "file=@%s" -F "original_filename=%s"',
        mixpitch_api, mixpitch_token, file_path, filename
    )
    
    local result = reaper.ExecProcess(curl_cmd, -1)
    
    if result == 0 then
        reaper.ShowMessageBox("Upload successful! View at: " .. project_url, "MixPitch Upload", 0)
    else
        reaper.ShowMessageBox("Upload failed. Please check your connection and try again.", "MixPitch Upload", 0)
    end
    
    -- Clean up temp file
    os.remove(file_path)
end

-- Main execution
upload_project_file()
LUA;
    }

    private function generateProToolsScript(DAWUploadToken $token): string
    {
        $apiUrl = route('api.daw.upload');
        
        return <<<APPLESCRIPT
-- MixPitch DAW Uploader for Pro Tools (macOS)
-- Install: Save as .scpt file in Pro Tools Scripts folder

tell application "Pro Tools"
    set mixpitch_token to "{$token->token}"
    set mixpitch_api to "{$apiUrl}"
    
    try
        -- Get current session info
        set session_name to name of front session
        set session_path to path of front session
        
        -- Bounce to disk
        set bounce_file to session_path & session_name & "_mixpitch.wav"
        
        -- Execute bounce (this would be expanded with actual Pro Tools scripting)
        display dialog "Bouncing " & session_name & " for MixPitch upload..."
        
        -- Upload via curl
        set curl_command to "curl -X POST '" & mixpitch_api & "' -H 'Authorization: Bearer " & mixpitch_token & "' -H 'X-DAW-Application: Pro Tools' -F 'file=@" & bounce_file & "' -F 'original_filename=" & session_name & ".wav'"
        
        do shell script curl_command
        
        display dialog "Upload successful!" & return & "View your project in MixPitch"
        
    on error error_message
        display dialog "Upload failed: " & error_message
    end try
end tell
APPLESCRIPT;
    }

    private function generateLogicScript(DAWUploadToken $token): string
    {
        $apiUrl = route('api.daw.upload');
        
        return <<<APPLESCRIPT
-- MixPitch DAW Uploader for Logic Pro (macOS)
-- Install: Save as .scpt in Logic Pro Scripts folder

tell application "Logic Pro"
    set mixpitch_token to "{$token->token}"
    set mixpitch_api to "{$apiUrl}"
    
    try
        -- Get project info
        set project_name to name of front project
        
        -- Bounce project
        set bounce_settings to {sample_rate:44100, bit_depth:24, file_format:"WAVE"}
        set bounce_file to bounce project with settings bounce_settings
        
        -- Upload via shell
        set curl_command to "curl -X POST '" & mixpitch_api & "' -H 'Authorization: Bearer " & mixpitch_token & "' -H 'X-DAW-Application: Logic Pro' -F 'file=@" & (POSIX path of bounce_file) & "' -F 'original_filename=" & project_name & ".wav'"
        
        do shell script curl_command
        
        display dialog "Uploaded to MixPitch successfully!"
        
    on error error_message
        display dialog "Upload error: " & error_message
    end try
end tell
APPLESCRIPT;
    }

    private function generateAbletonScript(DAWUploadToken $token): string
    {
        $apiUrl = route('api.daw.upload');
        
        return <<<PYTHON
# MixPitch DAW Uploader for Ableton Live
# Install: Place in Ableton Live/User Library/Remote Scripts/MixPitch/
# Requires: requests library

import Live
import requests
import os
import tempfile
from _Framework.ControlSurface import ControlSurface

class MixPitchUploader(ControlSurface):
    def __init__(self, c_instance):
        ControlSurface.__init__(self, c_instance)
        self.token = "{$token->token}"
        self.api_url = "{$apiUrl}"
        
    def export_and_upload(self):
        try:
            song = self.song()
            project_name = song.get_current_path() or "untitled_project"
            
            # Export audio (simplified - would need full Live API implementation)
            with tempfile.NamedTemporaryFile(suffix='.wav', delete=False) as temp_file:
                temp_path = temp_file.name
                
                # Render song to temp file
                song.export_audio(temp_path)
                
                # Upload to MixPitch
                with open(temp_path, 'rb') as audio_file:
                    files = {'file': audio_file}
                    headers = {
                        'Authorization': f'Bearer {self.token}',
                        'X-DAW-Application': 'Ableton Live'
                    }
                    data = {'original_filename': os.path.basename(project_name) + '.wav'}
                    
                    response = requests.post(self.api_url, files=files, headers=headers, data=data)
                    
                    if response.status_code == 200:
                        self.show_message("Upload successful!")
                    else:
                        self.show_message(f"Upload failed: {response.text}")
                
                # Cleanup
                os.unlink(temp_path)
                
        except Exception as e:
            self.show_message(f"Error: {str(e)}")
    
    def show_message(self, message):
        # Show message in Live (implementation depends on Live API version)
        pass
PYTHON;
    }

    private function generateStudioOneScript(DAWUploadToken $token): string
    {
        $apiUrl = route('api.daw.upload');
        
        return <<<JAVASCRIPT
// MixPitch DAW Uploader for Studio One
// Install: Copy to Studio One Scripts folder

var mixpitch_token = "{$token->token}";
var mixpitch_api = "{$apiUrl}";

function uploadToMixPitch() {
    try {
        // Get current song
        var song = Studio.getActiveSong();
        if (!song) {
            Console.log("No active song found");
            return;
        }
        
        var songName = song.getTitle();
        var mixdownPath = song.getPath() + "/" + songName + "_mixpitch.wav";
        
        // Mixdown song
        var mixdownOptions = {
            path: mixdownPath,
            format: "wav",
            sampleRate: 44100,
            bitDepth: 24
        };
        
        song.mixdown(mixdownOptions);
        
        // Upload using system command
        var curlCommand = [
            "curl", "-X", "POST", mixpitch_api,
            "-H", "Authorization: Bearer " + mixpitch_token,
            "-H", "X-DAW-Application: Studio One",
            "-F", "file=@" + mixdownPath,
            "-F", "original_filename=" + songName + ".wav"
        ];
        
        var result = System.execute(curlCommand);
        
        if (result === 0) {
            Console.log("Upload successful!");
        } else {
            Console.log("Upload failed");
        }
        
        // Cleanup
        File.remove(mixdownPath);
        
    } catch (error) {
        Console.log("Error: " + error.message);
    }
}

// Execute upload
uploadToMixPitch();
JAVASCRIPT;
    }
}
```

### API Endpoints

#### DAW Upload Controller
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DAWUploadRequest;
use App\Services\DAWIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DAWController extends Controller
{
    public function __construct(
        private DAWIntegrationService $dawService
    ) {}

    public function upload(DAWUploadRequest $request): JsonResponse
    {
        $token = $this->dawService->validateToken($request->bearerToken());
        
        if (!$token) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        try {
            $result = $this->dawService->processDAWUpload(
                $token,
                $request->header('X-DAW-Application', 'Unknown'),
                $request->input('original_filename'),
                $request->file('file')
            );

            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Upload failed',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function downloadScript(Request $request, string $dawType): Response
    {
        $token = $request->user()->dawUploadTokens()
            ->where('project_id', $request->input('project_id'))
            ->where('is_active', true)
            ->first();

        if (!$token) {
            abort(404, 'No active DAW token found for this project');
        }

        try {
            $script = $this->dawService->generateInstallationScript($token, $dawType);
            
            $filename = "mixpitch_uploader_{$dawType}." . $this->getScriptExtension($dawType);
            
            return response($script)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
                
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }
    }

    private function getScriptExtension(string $dawType): string
    {
        return match ($dawType) {
            'reaper' => 'lua',
            'protools', 'logic' => 'scpt',
            'ableton' => 'py',
            'studio_one' => 'js',
            default => 'txt'
        };
    }
}
```

#### DAW Upload Request Validation
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DAWUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by token validation
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:' . (config('filesystems.max_upload_size_mb', 500) * 1024),
                'mimetypes:audio/*,application/octet-stream'
            ],
            'original_filename' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Audio file is required',
            'file.max' => 'File size too large. Maximum size is ' . config('filesystems.max_upload_size_mb', 500) . 'MB',
            'file.mimetypes' => 'Only audio files are allowed',
            'original_filename.required' => 'Original filename is required'
        ];
    }
}
```

## UI Implementation

### DAW Integration Management Component
```php
<?php

namespace App\Livewire\Project;

use App\Models\DAWUploadToken;
use App\Models\Project;
use App\Services\DAWIntegrationService;
use Livewire\Component;

class DAWIntegration extends Component
{
    public Project $project;
    public $tokens = [];
    public string $tokenName = '';
    public string $selectedDAW = '';
    public bool $showCreateForm = false;
    public bool $showScriptModal = false;
    public string $scriptContent = '';
    public string $scriptFilename = '';

    protected $rules = [
        'tokenName' => 'required|string|max:255',
        'selectedDAW' => 'required|in:reaper,protools,logic,ableton,studio_one'
    ];

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->loadTokens();
    }

    public function createToken(DAWIntegrationService $dawService)
    {
        $this->validate();

        $token = $dawService->createUploadToken(
            auth()->user(),
            $this->project,
            $this->tokenName
        );

        $this->reset(['tokenName', 'showCreateForm']);
        $this->loadTokens();

        $this->dispatch('token-created', [
            'message' => 'DAW upload token created successfully!'
        ]);
    }

    public function generateScript(int $tokenId, string $dawType, DAWIntegrationService $dawService)
    {
        $token = $this->tokens->firstWhere('id', $tokenId);
        
        if (!$token) {
            $this->addError('script', 'Token not found');
            return;
        }

        try {
            $this->scriptContent = $dawService->generateInstallationScript($token, $dawType);
            $this->scriptFilename = "mixpitch_uploader_{$dawType}." . $this->getScriptExtension($dawType);
            $this->showScriptModal = true;
            
        } catch (\Exception $e) {
            $this->addError('script', 'Error generating script: ' . $e->getMessage());
        }
    }

    public function revokeToken(int $tokenId)
    {
        $token = DAWUploadToken::where('id', $tokenId)
            ->where('user_id', auth()->id())
            ->where('project_id', $this->project->id)
            ->first();

        if ($token) {
            $token->update(['is_active' => false]);
            $this->loadTokens();
            
            $this->dispatch('token-revoked', [
                'message' => 'Token revoked successfully'
            ]);
        }
    }

    private function loadTokens()
    {
        $this->tokens = DAWUploadToken::where('user_id', auth()->id())
            ->where('project_id', $this->project->id)
            ->where('is_active', true)
            ->with('dawUploadLogs')
            ->latest()
            ->get();
    }

    private function getScriptExtension(string $dawType): string
    {
        return match ($dawType) {
            'reaper' => 'lua',
            'protools', 'logic' => 'scpt',
            'ableton' => 'py',
            'studio_one' => 'js',
            default => 'txt'
        };
    }

    public function render()
    {
        return view('livewire.project.daw-integration');
    }
}
```

### Blade Template
```blade
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">DAW Integration</flux:heading>
            <flux:text variant="muted">
                Upload directly from your DAW with one-click scripts
            </flux:text>
        </div>
        
        <flux:button 
            wire:click="$set('showCreateForm', true)" 
            variant="primary" 
            size="sm"
            wire:loading.attr="disabled"
        >
            <flux:icon icon="plus" class="w-4 h-4" />
            Create Upload Token
        </flux:button>
    </div>

    {{-- Create Token Form --}}
    @if($showCreateForm)
        <flux:card>
            <flux:card.header>
                <flux:heading size="base">Create DAW Upload Token</flux:heading>
            </flux:card.header>
            
            <flux:card.body>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Token Name</flux:label>
                        <flux:input 
                            wire:model="tokenName" 
                            placeholder="e.g., Logic Pro Studio Setup"
                        />
                        <flux:error name="tokenName" />
                        <flux:description>
                            Choose a descriptive name to identify this token
                        </flux:description>
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Primary DAW</flux:label>
                        <flux:select wire:model="selectedDAW">
                            <option value="">Select your DAW</option>
                            <option value="reaper">Reaper</option>
                            <option value="protools">Pro Tools</option>
                            <option value="logic">Logic Pro</option>
                            <option value="ableton">Ableton Live</option>
                            <option value="studio_one">Studio One</option>
                        </flux:select>
                        <flux:error name="selectedDAW" />
                    </flux:field>
                </div>
                
                <div class="flex items-center justify-end space-x-3 pt-4">
                    <flux:button 
                        wire:click="$set('showCreateForm', false)" 
                        variant="outline"
                    >
                        Cancel
                    </flux:button>
                    <flux:button 
                        wire:click="createToken" 
                        variant="primary"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="createToken">Create Token</span>
                        <span wire:loading wire:target="createToken">Creating...</span>
                    </flux:button>
                </div>
            </flux:card.body>
        </flux:card>
    @endif

    {{-- Active Tokens --}}
    @if($tokens->isNotEmpty())
        <flux:card>
            <flux:card.header>
                <flux:heading size="base">Active Upload Tokens</flux:heading>
            </flux:card.header>
            
            <flux:table>
                <flux:table.header>
                    <flux:table.row>
                        <flux:table.cell>Name</flux:table.cell>
                        <flux:table.cell>Created</flux:table.cell>
                        <flux:table.cell>Last Used</flux:table.cell>
                        <flux:table.cell>Uploads</flux:table.cell>
                        <flux:table.cell>Scripts</flux:table.cell>
                        <flux:table.cell>Actions</flux:table.cell>
                    </flux:table.row>
                </flux:table.header>
                
                <flux:table.body>
                    @foreach($tokens as $token)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="font-medium">{{ $token->name }}</div>
                                <div class="text-sm text-gray-500">
                                    Expires {{ $token->expires_at->diffForHumans() }}
                                </div>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                {{ $token->created_at->format('M j, Y') }}
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                @if($token->last_used_at)
                                    {{ $token->last_used_at->diffForHumans() }}
                                @else
                                    <span class="text-gray-400">Never</span>
                                @endif
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <flux:badge variant="outline" size="sm">
                                    {{ $token->dawUploadLogs->where('status', 'success')->count() }} successful
                                </flux:badge>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <div class="flex flex-wrap gap-1">
                                    @foreach(['reaper', 'protools', 'logic', 'ableton', 'studio_one'] as $daw)
                                        <flux:button 
                                            wire:click="generateScript({{ $token->id }}, '{{ $daw }}')"
                                            variant="outline" 
                                            size="xs"
                                            class="text-xs"
                                        >
                                            {{ ucfirst($daw) }}
                                        </flux:button>
                                    @endforeach
                                </div>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <flux:button 
                                    wire:click="revokeToken({{ $token->id }})"
                                    variant="danger" 
                                    size="sm"
                                    wire:confirm="Are you sure you want to revoke this token? All associated scripts will stop working."
                                >
                                    <flux:icon icon="trash" class="w-4 h-4" />
                                    Revoke
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.body>
            </flux:table>
        </flux:card>
    @else
        {{-- Empty State --}}
        <flux:card>
            <flux:card.body class="text-center py-12">
                <flux:icon icon="musical-note" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                <flux:heading size="base" class="mb-2">No DAW Integration Set Up</flux:heading>
                <flux:text variant="muted" class="mb-6">
                    Create an upload token to enable direct uploads from your DAW applications
                </flux:text>
                <flux:button 
                    wire:click="$set('showCreateForm', true)" 
                    variant="primary"
                >
                    <flux:icon icon="plus" class="w-4 h-4" />
                    Create Your First Token
                </flux:button>
            </flux:card.body>
        </flux:card>
    @endif

    {{-- Script Download Modal --}}
    @if($showScriptModal)
        <flux:modal wire:model="showScriptModal" size="xl">
            <flux:modal.header>
                <flux:heading>Installation Script: {{ $scriptFilename }}</flux:heading>
            </flux:modal.header>
            
            <flux:modal.body>
                <div class="space-y-4">
                    <flux:callout>
                        <strong>Installation Instructions:</strong>
                        <ol class="list-decimal list-inside mt-2 space-y-1">
                            <li>Copy the script below to your clipboard</li>
                            <li>Save it in your DAW's scripts folder</li>
                            <li>Run the script from within your DAW to upload files</li>
                        </ol>
                    </flux:callout>
                    
                    <div class="relative">
                        <pre class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg text-sm overflow-x-auto max-h-96"><code>{{ $scriptContent }}</code></pre>
                        <flux:button 
                            x-data x-clipboard="{{ $scriptContent }}"
                            class="absolute top-2 right-2" 
                            variant="outline" 
                            size="sm"
                        >
                            <flux:icon icon="clipboard" class="w-4 h-4" />
                            Copy
                        </flux:button>
                    </div>
                </div>
            </flux:modal.body>
            
            <flux:modal.footer>
                <flux:button 
                    wire:click="$set('showScriptModal', false)" 
                    variant="primary"
                >
                    Close
                </flux:button>
            </flux:modal.footer>
        </flux:modal>
    @endif
</div>

@script
<script>
    $wire.on('token-created', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: {
                type: 'success',
                message: data.message
            }
        }));
    });

    $wire.on('token-revoked', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: {
                type: 'success',
                message: data.message
            }
        }));
    });
</script>
@endscript
```

## Testing Strategy

### Feature Tests
```php
<?php

namespace Tests\Feature\DAW;

use App\Models\DAWUploadToken;
use App\Models\Project;
use App\Models\User;
use App\Services\DAWIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DAWIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_daw_upload_token(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $service = new DAWIntegrationService();
        $token = $service->createUploadToken($user, $project, 'Test Token');

        $this->assertInstanceOf(DAWUploadToken::class, $token);
        $this->assertEquals($user->id, $token->user_id);
        $this->assertEquals($project->id, $token->project_id);
        $this->assertEquals('Test Token', $token->name);
        $this->assertTrue($token->is_active);
        $this->assertNotNull($token->expires_at);
        $this->assertEquals(64, strlen($token->token));
    }

    public function test_daw_upload_with_valid_token(): void
    {
        Storage::fake('s3');
        
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        
        $service = new DAWIntegrationService();
        $token = $service->createUploadToken($user, $project, 'DAW Token');
        
        $audioFile = UploadedFile::fake()->create('test_track.wav', 5000, 'audio/wav');

        $response = $this->postJson('/api/daw/upload', [
            'file' => $audioFile,
            'original_filename' => 'test_track.wav'
        ], [
            'Authorization' => 'Bearer ' . $token->token,
            'X-DAW-Application' => 'REAPER'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'file_id',
            'filename',
            'version',
            'upload_url',
            'project_url'
        ]);

        $this->assertDatabaseHas('project_files', [
            'project_id' => $project->id,
            'user_id' => $user->id
        ]);

        $this->assertDatabaseHas('daw_upload_logs', [
            'token_id' => $token->id,
            'daw_application' => 'REAPER',
            'status' => 'success'
        ]);
    }

    public function test_daw_upload_with_invalid_token(): void
    {
        $audioFile = UploadedFile::fake()->create('test.wav', 1000, 'audio/wav');

        $response = $this->postJson('/api/daw/upload', [
            'file' => $audioFile,
            'original_filename' => 'test.wav'
        ], [
            'Authorization' => 'Bearer invalid_token',
            'X-DAW-Application' => 'REAPER'
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid or expired token']);
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        
        $token = DAWUploadToken::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'expires_at' => now()->subDay(),
            'is_active' => true
        ]);

        $audioFile = UploadedFile::fake()->create('test.wav', 1000, 'audio/wav');

        $response = $this->postJson('/api/daw/upload', [
            'file' => $audioFile,
            'original_filename' => 'test.wav'
        ], [
            'Authorization' => 'Bearer ' . $token->token,
            'X-DAW-Application' => 'Logic Pro'
        ]);

        $response->assertStatus(401);
    }

    public function test_script_generation_for_different_daws(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        
        $service = new DAWIntegrationService();
        $token = $service->createUploadToken($user, $project, 'Script Test');

        $daws = ['reaper', 'protools', 'logic', 'ableton', 'studio_one'];
        
        foreach ($daws as $daw) {
            $script = $service->generateInstallationScript($token, $daw);
            
            $this->assertNotEmpty($script);
            $this->assertStringContainsString($token->token, $script);
            $this->assertStringContainsString('mixpitch', strtolower($script));
        }
    }

    public function test_file_size_validation(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        
        $service = new DAWIntegrationService();
        $token = $service->createUploadToken($user, $project, 'Size Test');
        
        // Create file larger than allowed limit
        $largeFile = UploadedFile::fake()->create('large.wav', 600 * 1024, 'audio/wav'); // 600MB

        $response = $this->postJson('/api/daw/upload', [
            'file' => $largeFile,
            'original_filename' => 'large.wav'
        ], [
            'Authorization' => 'Bearer ' . $token->token,
            'X-DAW-Application' => 'REAPER'
        ]);

        $response->assertStatus(422);
    }

    public function test_revoked_token_cannot_upload(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        
        $token = DAWUploadToken::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'is_active' => false // Revoked token
        ]);

        $audioFile = UploadedFile::fake()->create('test.wav', 1000, 'audio/wav');

        $response = $this->postJson('/api/daw/upload', [
            'file' => $audioFile,
            'original_filename' => 'test.wav'
        ], [
            'Authorization' => 'Bearer ' . $token->token,
            'X-DAW-Application' => 'Pro Tools'
        ]);

        $response->assertStatus(401);
    }
}
```

### Unit Tests
```php
<?php

namespace Tests\Unit\Services;

use App\Models\DAWUploadToken;
use App\Models\Project;
use App\Models\User;
use App\Services\DAWIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DAWIntegrationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_token_with_correct_attributes(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $service = new DAWIntegrationService();

        $token = $service->createUploadToken($user, $project, 'Test Token');

        $this->assertEquals('Test Token', $token->name);
        $this->assertEquals(['upload_files'], $token->permissions);
        $this->assertTrue($token->is_active);
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->gt(now()));
    }

    public function test_revokes_existing_tokens_when_creating_new_one(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $service = new DAWIntegrationService();

        // Create first token
        $firstToken = $service->createUploadToken($user, $project, 'First Token');
        $this->assertTrue($firstToken->is_active);

        // Create second token
        $secondToken = $service->createUploadToken($user, $project, 'Second Token');
        
        $firstToken->refresh();
        $this->assertFalse($firstToken->is_active);
        $this->assertTrue($secondToken->is_active);
    }

    public function test_validates_token_correctly(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $service = new DAWIntegrationService();

        $validToken = $service->createUploadToken($user, $project, 'Valid Token');
        $expiredToken = DAWUploadToken::factory()->create([
            'expires_at' => now()->subDay(),
            'is_active' => true
        ]);
        $revokedToken = DAWUploadToken::factory()->create([
            'is_active' => false
        ]);

        $this->assertNotNull($service->validateToken($validToken->token));
        $this->assertNull($service->validateToken($expiredToken->token));
        $this->assertNull($service->validateToken($revokedToken->token));
        $this->assertNull($service->validateToken('invalid_token'));
    }

    public function test_generates_proper_filename_with_version(): void
    {
        $service = new DAWIntegrationService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('generateDAWFilename');
        $method->setAccessible(true);

        $project = Project::factory()->create(['name' => 'Test Project']);
        
        $filename = $method->invoke($service, 'original_track.wav', $project);
        
        $this->assertStringContainsString('test-project', $filename);
        $this->assertStringContainsString('original_track', $filename);
        $this->assertStringEndsWith('.wav', $filename);
    }
}
```

## Implementation Steps

### Phase 1: Core Infrastructure (Week 1)
1. **Database Migration Setup**
   - Create DAW upload tokens table
   - Add upload logging table
   - Create DAW configurations table

2. **Service Architecture**
   - Implement `DAWIntegrationService` with token management
   - Add file upload processing with existing `FileManagementService`
   - Create script generation methods for all supported DAWs

3. **API Endpoints**
   - Create upload endpoint with token authentication
   - Add script download endpoint
   - Implement proper error handling and validation

### Phase 2: UI Implementation (Week 2)
1. **Livewire Components**
   - DAW integration management component
   - Token creation and management interface
   - Script generation and download functionality

2. **Blade Templates**
   - Token management interface using Flux UI
   - Script download modal with installation instructions
   - Real-time upload status and activity logs

3. **Integration Points**
   - Add DAW integration tab to project management
   - Integrate with existing file upload system
   - Connect to project workflow notifications

### Phase 3: Script Development & Testing (Week 3)
1. **DAW Script Implementation**
   - Complete Reaper Lua script with full functionality
   - Apple Script implementation for Pro Tools and Logic
   - Python script for Ableton Live integration
   - JavaScript implementation for Studio One

2. **Cross-platform Testing**
   - Test scripts on Windows, macOS, and Linux
   - Validate file upload process from each DAW
   - Error handling and user feedback improvements

3. **Documentation & Examples**
   - Installation guides for each DAW
   - Troubleshooting documentation
   - Video tutorials for setup process

### Phase 4: Production Deployment (Week 4)
1. **Security Hardening**
   - Token expiration and rotation policies
   - Rate limiting for upload endpoints
   - Audit logging and monitoring

2. **Performance Optimization**
   - Large file upload handling
   - Background processing for file conversion
   - CDN integration for script distribution

3. **User Onboarding**
   - In-app guidance for DAW setup
   - Success metrics and usage tracking
   - Feedback collection and iteration

## Security Considerations

### Token Security
- **Unique Tokens**: 64-character random tokens for each integration
- **Expiration**: Default 30-day expiration with configurable limits
- **Revocation**: Immediate token deactivation capability
- **Single Use**: Option for one-time upload tokens

### Upload Security
- **File Validation**: Strict file type and size checking
- **Virus Scanning**: Integration with existing ClamAV scanning
- **Rate Limiting**: Per-token upload limits to prevent abuse
- **Audit Trail**: Complete logging of all upload activities

### API Security
- **Bearer Authentication**: Standard token-based API access
- **HTTPS Only**: All communication encrypted in transit
- **Input Sanitization**: Comprehensive validation of all inputs
- **Error Handling**: Secure error messages without information leakage

This comprehensive implementation plan provides professional DAW integration capabilities while maintaining MixPitch's security standards and user experience principles.