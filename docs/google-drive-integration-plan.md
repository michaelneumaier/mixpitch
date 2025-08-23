# Google Drive Integration Implementation Plan
## Comprehensive Integration for MixPitch Platform

### Executive Summary

This document outlines a comprehensive plan to integrate Google Drive functionality into the MixPitch platform, enabling users to link their Google Drive accounts for seamless file synchronization, backup, and collaboration. The integration will support bidirectional file sync, real-time change monitoring, and flexible user-controlled settings at both global and project levels.

---

## Table of Contents

1. [Introduction](#introduction)
2. [Current System Analysis](#current-system-analysis)
3. [Integration Architecture](#integration-architecture)
4. [Database Schema Design](#database-schema-design)
5. [Authentication & Authorization](#authentication--authorization)
6. [File Synchronization System](#file-synchronization-system)
7. [Webhook & Change Detection](#webhook--change-detection)
8. [User Interface & Settings](#user-interface--settings)
9. [Implementation Phases](#implementation-phases)
10. [Security Considerations](#security-considerations)
11. [Testing Strategy](#testing-strategy)
12. [Deployment & Monitoring](#deployment--monitoring)
13. [Future Enhancements](#future-enhancements)

---

## Introduction

### Integration Goals

1. **Seamless File Management**: Allow users to sync files between MixPitch and Google Drive
2. **Real-time Synchronization**: Monitor Google Drive changes and reflect them in MixPitch
3. **Flexible Backup Options**: Provide multiple backup and sync strategies
4. **Project-level Control**: Enable granular settings per project
5. **User-friendly Interface**: Maintain MixPitch's existing UX patterns

### Use Cases

1. **Backup Mode**: Files uploaded to MixPitch are automatically backed up to Google Drive
2. **Selective Sync**: Users choose specific Google Drive files to import into MixPitch
3. **Active Monitoring**: MixPitch monitors specific Google Drive directories for changes
4. **Bidirectional Sync**: Keep files synchronized in both directions
5. **Project Isolation**: Each project can have its own Google Drive directory

---

## Current System Analysis

### Existing Infrastructure

**File Management System:**
- `FileManagementService` handles project and pitch file uploads
- S3 storage with organized directory structure (`projects/{id}`, `pitches/{id}`)
- Support for multiple file types (audio, images, documents, archives)
- User storage limits and quota management via `UserStorageService`

**Authentication Patterns:**
- OAuth2 implementation via Laravel Socialite (Google, GitHub, etc.)
- Secure token storage in user model (`provider_token`, `provider_refresh_token`)
- Existing social login infrastructure

**Background Processing:**
- Laravel queue system with database driver
- Existing jobs for file processing, audio transcoding, waveform generation
- Scheduled tasks via Laravel's task scheduler

**Settings Management:**
- `FileUploadSetting` model for configurable upload parameters
- `NotificationChannelPreference` for user preferences
- Livewire components for settings UI

### Integration Points

1. **User Model**: Already supports OAuth tokens and provider data
2. **Project Model**: Has file relationships and storage tracking
3. **File Models**: `ProjectFile` and `PitchFile` with metadata support
4. **Queue System**: Ready for background sync operations
5. **Webhook Infrastructure**: Existing webhook controllers for Stripe and SES

---

## Integration Architecture

### Core Components

```
┌─────────────────────────────────────────────────────────────┐
│                    MixPitch Application                     │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │   Google Drive  │  │  Sync Engine    │  │   Webhook       │ │
│  │   Service       │  │                 │  │   Handler       │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │   Settings      │  │  File Manager   │  │   Queue Jobs    │ │
│  │   Manager       │  │                 │  │                 │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Google Drive API                         │
├─────────────────────────────────────────────────────────────┤
│  • OAuth 2.0 Authentication                                │
│  • Files API (CRUD operations)                             │
│  • Events API (Change notifications)                       │
│  • Cloud Pub/Sub (Webhook delivery)                        │
└─────────────────────────────────────────────────────────────┘
```

### Service Layer Architecture

1. **GoogleDriveService**: Core API interaction service
2. **GoogleDriveSyncService**: Handles file synchronization logic
3. **GoogleDriveWebhookService**: Processes incoming change notifications
4. **GoogleDriveSettingsService**: Manages user and project settings
5. **GoogleDriveFileService**: File operations and metadata management

---

## Database Schema Design

### New Tables

#### 1. `google_drive_connections`
```sql
CREATE TABLE google_drive_connections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    google_account_email VARCHAR(255) NOT NULL,
    access_token TEXT NOT NULL,
    refresh_token TEXT NOT NULL,
    token_expires_at TIMESTAMP NULL,
    scope TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_sync_at TIMESTAMP NULL,
    sync_status ENUM('connected', 'syncing', 'error', 'disconnected') DEFAULT 'connected',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_sync_status (sync_status)
);
```

#### 2. `google_drive_settings`
```sql
CREATE TABLE google_drive_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NULL,
    setting_type ENUM('global', 'project') NOT NULL,
    sync_mode ENUM('backup_only', 'selective_sync', 'active_monitoring', 'bidirectional') DEFAULT 'backup_only',
    auto_backup BOOLEAN DEFAULT TRUE,
    backup_directory_id VARCHAR(255) NULL,
    monitored_directories JSON NULL,
    file_type_filters JSON NULL,
    sync_frequency ENUM('realtime', 'hourly', 'daily') DEFAULT 'realtime',
    conflict_resolution ENUM('mixpitch_wins', 'drive_wins', 'manual') DEFAULT 'manual',
    is_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_project (user_id, project_id),
    INDEX idx_user_enabled (user_id, is_enabled)
);
```

#### 3. `google_drive_files`
```sql
CREATE TABLE google_drive_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NULL,
    mixpitch_file_id BIGINT UNSIGNED NULL,
    mixpitch_file_type ENUM('project_file', 'pitch_file') NULL,
    google_drive_file_id VARCHAR(255) NOT NULL,
    google_drive_parent_id VARCHAR(255) NULL,
    file_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    google_drive_modified_time TIMESTAMP NOT NULL,
    mixpitch_modified_time TIMESTAMP NULL,
    sync_status ENUM('synced', 'pending_upload', 'pending_download', 'conflict', 'error') DEFAULT 'synced',
    sync_direction ENUM('to_drive', 'from_drive', 'bidirectional') NOT NULL,
    last_sync_at TIMESTAMP NULL,
    error_message TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_drive_file (google_drive_file_id, user_id),
    INDEX idx_sync_status (sync_status),
    INDEX idx_user_project (user_id, project_id)
);
```

#### 4. `google_drive_sync_logs`
```sql
CREATE TABLE google_drive_sync_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NULL,
    google_drive_file_id BIGINT UNSIGNED NULL,
    action ENUM('upload', 'download', 'delete', 'create_folder', 'webhook_received') NOT NULL,
    status ENUM('success', 'failed', 'skipped') NOT NULL,
    details JSON NULL,
    error_message TEXT NULL,
    processing_time_ms INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (google_drive_file_id) REFERENCES google_drive_files(id) ON DELETE SET NULL,
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_status_created (status, created_at)
);
```

#### 5. `google_drive_webhooks`
```sql
CREATE TABLE google_drive_webhooks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    channel_id VARCHAR(255) NOT NULL UNIQUE,
    resource_id VARCHAR(255) NOT NULL,
    resource_uri VARCHAR(500) NOT NULL,
    token VARCHAR(255) NULL,
    expiration TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_expiration (expiration)
);
```

---

## Authentication & Authorization

### OAuth 2.0 Implementation

#### Required Google Cloud Setup
1. **Create Google Cloud Project**
2. **Enable Google Drive API**
3. **Configure OAuth Consent Screen**
4. **Create OAuth 2.0 Credentials**

#### Required Scopes
```php
const REQUIRED_SCOPES = [
    'https://www.googleapis.com/auth/drive.file',      // Access files created by the app
    'https://www.googleapis.com/auth/drive.metadata',  // Read file metadata
    'https://www.googleapis.com/auth/userinfo.email',  // User email for account linking
];

const OPTIONAL_SCOPES = [
    'https://www.googleapis.com/auth/drive',           // Full Drive access (for advanced features)
];
```

#### Service Implementation
```php
<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use App\Models\GoogleDriveConnection;
use App\Models\User;

class GoogleDriveService
{
    private Client $client;
    private ?Drive $driveService = null;
    
    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect'));
        $this->client->setScopes(self::REQUIRED_SCOPES);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }
    
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }
    
    public function handleCallback(string $code, User $user): GoogleDriveConnection
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        
        if (isset($token['error'])) {
            throw new \Exception('OAuth error: ' . $token['error']);
        }
        
        $this->client->setAccessToken($token);
        
        // Get user info to verify account
        $oauth2 = new \Google\Service\Oauth2($this->client);
        $userInfo = $oauth2->userinfo->get();
        
        return GoogleDriveConnection::updateOrCreate(
            ['user_id' => $user->id],
            [
                'google_account_email' => $userInfo->email,
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'token_expires_at' => isset($token['expires_in']) 
                    ? now()->addSeconds($token['expires_in']) 
                    : null,
                'scope' => implode(' ', self::REQUIRED_SCOPES),
                'is_active' => true,
                'sync_status' => 'connected',
            ]
        );
    }
    
    public function refreshToken(GoogleDriveConnection $connection): void
    {
        $this->client->setAccessToken([
            'access_token' => $connection->access_token,
            'refresh_token' => $connection->refresh_token,
        ]);
        
        if ($this->client->isAccessTokenExpired()) {
            $token = $this->client->fetchAccessTokenWithRefreshToken();
            
            $connection->update([
                'access_token' => $token['access_token'],
                'token_expires_at' => isset($token['expires_in']) 
                    ? now()->addSeconds($token['expires_in']) 
                    : null,
            ]);
        }
    }
}
```

---

## File Synchronization System

### Sync Modes

#### 1. Backup Only Mode
- Files uploaded to MixPitch are automatically backed up to Google Drive
- No downloads from Google Drive to MixPitch
- One-way sync: MixPitch → Google Drive

#### 2. Selective Sync Mode
- Users manually select files from Google Drive to import
- Manual control over which files are synchronized
- Bi-directional sync for selected files only

#### 3. Active Monitoring Mode
- MixPitch monitors specific Google Drive directories
- Automatic download of new/changed files
- Real-time sync based on webhook notifications

#### 4. Bidirectional Sync Mode
- Full two-way synchronization
- Changes in either platform reflect in the other
- Conflict resolution strategies

### Sync Service Implementation

```php
<?php

namespace App\Services;

use App\Models\GoogleDriveConnection;
use App\Models\GoogleDriveSettings;
use App\Models\GoogleDriveFile;
use App\Models\Project;
use App\Jobs\SyncFileToGoogleDrive;
use App\Jobs\SyncFileFromGoogleDrive;

class GoogleDriveSyncService
{
    public function __construct(
        private GoogleDriveService $driveService,
        private FileManagementService $fileService
    ) {}
    
    public function syncProjectToGoogleDrive(Project $project): void
    {
        $connection = $project->user->googleDriveConnection;
        $settings = $this->getProjectSettings($project);
        
        if (!$connection || !$settings->is_enabled) {
            return;
        }
        
        foreach ($project->files as $file) {
            SyncFileToGoogleDrive::dispatch($file, $connection, $settings);
        }
    }
    
    public function handleGoogleDriveChange(array $changeData): void
    {
        $driveFile = GoogleDriveFile::where('google_drive_file_id', $changeData['fileId'])->first();
        
        if (!$driveFile) {
            return;
        }
        
        $settings = $this->getUserSettings($driveFile->user);
        
        if ($settings->sync_mode === 'active_monitoring' || $settings->sync_mode === 'bidirectional') {
            SyncFileFromGoogleDrive::dispatch($driveFile, $changeData);
        }
    }
    
    public function resolveConflict(GoogleDriveFile $driveFile, string $resolution): void
    {
        switch ($resolution) {
            case 'mixpitch_wins':
                SyncFileToGoogleDrive::dispatch($driveFile->mixpitchFile, $driveFile->user->googleDriveConnection);
                break;
                
            case 'drive_wins':
                SyncFileFromGoogleDrive::dispatch($driveFile);
                break;
                
            case 'manual':
                // User must manually resolve - mark for attention
                $driveFile->update(['sync_status' => 'conflict']);
                break;
        }
    }
}
```

---

## Webhook & Change Detection

### Google Drive Events API Integration

#### Webhook Setup
```php
<?php

namespace App\Services;

use App\Models\GoogleDriveWebhook;
use App\Models\User;
use Google\Service\Drive;

class GoogleDriveWebhookService
{
    public function setupWebhook(User $user, string $resourceId = null): GoogleDriveWebhook
    {
        $connection = $user->googleDriveConnection;
        $this->driveService->refreshToken($connection);
        
        $channelId = 'mixpitch_' . $user->id . '_' . Str::random(10);
        $webhookUrl = route('webhooks.google-drive');
        
        $channel = new \Google\Service\Drive\Channel([
            'id' => $channelId,
            'type' => 'web_hook',
            'address' => $webhookUrl,
            'token' => Str::random(32),
            'expiration' => (time() + 86400) * 1000, // 24 hours
        ]);
        
        $driveService = new Drive($this->driveService->getClient());
        
        if ($resourceId) {
            $response = $driveService->files->watch($resourceId, $channel);
        } else {
            $response = $driveService->changes->watch('startPageToken', $channel);
        }
        
        return GoogleDriveWebhook::create([
            'user_id' => $user->id,
            'channel_id' => $channelId,
            'resource_id' => $response->resourceId,
            'resource_uri' => $response->resourceUri,
            'token' => $channel->token,
            'expiration' => Carbon::createFromTimestamp($response->expiration / 1000),
            'is_active' => true,
        ]);
    }
    
    public function handleWebhook(Request $request): void
    {
        $channelId = $request->header('X-Goog-Channel-Id');
        $resourceState = $request->header('X-Goog-Resource-State');
        
        $webhook = GoogleDriveWebhook::where('channel_id', $channelId)
            ->where('is_active', true)
            ->first();
            
        if (!$webhook) {
            Log::warning('Received webhook for unknown channel', ['channel_id' => $channelId]);
            return;
        }
        
        // Verify token
        if ($request->header('X-Goog-Channel-Token') !== $webhook->token) {
            Log::warning('Invalid webhook token', ['channel_id' => $channelId]);
            return;
        }
        
        // Process the change
        ProcessGoogleDriveWebhook::dispatch($webhook, $resourceState, $request->all());
    }
}
```

#### Webhook Controller
```php
<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveWebhookService;
use Illuminate\Http\Request;

class GoogleDriveWebhookController extends Controller
{
    public function __construct(
        private GoogleDriveWebhookService $webhookService
    ) {}
    
    public function handle(Request $request)
    {
        try {
            $this->webhookService->handleWebhook($request);
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Google Drive webhook error', [
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }
}
```

---

## User Interface & Settings

### Settings Management

#### Global Settings Component
```php
<?php

namespace App\Livewire\User;

use App\Models\GoogleDriveConnection;
use App\Models\GoogleDriveSettings;
use App\Services\GoogleDriveService;
use Livewire\Component;

class GoogleDriveSettings extends Component
{
    public $isConnected = false;
    public $connectionEmail = '';
    public $syncMode = 'backup_only';
    public $autoBackup = true;
    public $syncFrequency = 'realtime';
    public $conflictResolution = 'manual';
    public $fileTypeFilters = [];
    
    public function mount()
    {
        $this->loadSettings();
    }
    
    public function connectGoogleDrive()
    {
        $driveService = app(GoogleDriveService::class);
        return redirect($driveService->getAuthUrl());
    }
    
    public function disconnectGoogleDrive()
    {
        auth()->user()->googleDriveConnection?->delete();
        $this->loadSettings();
        $this->dispatch('toast', type: 'success', message: 'Google Drive disconnected successfully.');
    }
    
    public function updateSettings()
    {
        $this->validate([
            'syncMode' => 'required|in:backup_only,selective_sync,active_monitoring,bidirectional',
            'syncFrequency' => 'required|in:realtime,hourly,daily',
            'conflictResolution' => 'required|in:mixpitch_wins,drive_wins,manual',
        ]);
        
        GoogleDriveSettings::updateOrCreate(
            ['user_id' => auth()->id(), 'setting_type' => 'global'],
            [
                'sync_mode' => $this->syncMode,
                'auto_backup' => $this->autoBackup,
                'sync_frequency' => $this->syncFrequency,
                'conflict_resolution' => $this->conflictResolution,
                'file_type_filters' => $this->fileTypeFilters,
            ]
        );
        
        $this->dispatch('toast', type: 'success', message: 'Settings updated successfully.');
    }
    
    private function loadSettings()
    {
        $connection = auth()->user()->googleDriveConnection;
        $this->isConnected = $connection && $connection->is_active;
        $this->connectionEmail = $connection?->google_account_email ?? '';
        
        $settings = GoogleDriveSettings::where('user_id', auth()->id())
            ->where('setting_type', 'global')
            ->first();
            
        if ($settings) {
            $this->syncMode = $settings->sync_mode;
            $this->autoBackup = $settings->auto_backup;
            $this->syncFrequency = $settings->sync_frequency;
            $this->conflictResolution = $settings->conflict_resolution;
            $this->fileTypeFilters = $settings->file_type_filters ?? [];
        }
    }
}
```

#### Project-Level Settings Component
```php
<?php

namespace App\Livewire\Project;

use App\Models\GoogleDriveSettings;
use App\Models\Project;
use Livewire\Component;

class GoogleDriveProjectSettings extends Component
{
    public Project $project;
    public $isEnabled = false;
    public $syncMode = 'backup_only';
    public $backupDirectoryId = null;
    public $monitoredDirectories = [];
    
    public function mount(Project $project)
    {
        $this->project = $project;
        $this->loadSettings();
    }
    
    public function updateProjectSettings()
    {
        $this->validate([
            'syncMode' => 'required|in:backup_only,selective_sync,active_monitoring,bidirectional',
        ]);
        
        GoogleDriveSettings::updateOrCreate(
            [
                'user_id' => $this->project->user_id,
                'project_id' => $this->project->id,
                'setting_type' => 'project'
            ],
            [
                'sync_mode' => $this->syncMode,
                'is_enabled' => $this->isEnabled,
                'backup_directory_id' => $this->backupDirectoryId,
                'monitored_directories' => $this->monitoredDirectories,
            ]
        );
        
        $this->dispatch('toast', type: 'success', message: 'Project Google Drive settings updated.');
    }
    
    private function loadSettings()
    {
        $settings = GoogleDriveSettings::where('user_id', $this->project->user_id)
            ->where('project_id', $this->project->id)
            ->where('setting_type', 'project')
            ->first();
            
        if ($settings) {
            $this->isEnabled = $settings->is_enabled;
            $this->syncMode = $settings->sync_mode;
            $this->backupDirectoryId = $settings->backup_directory_id;
            $this->monitoredDirectories = $settings->monitored_directories ?? [];
        }
    }
}
```

### UI Templates

#### Global Settings View
```blade
<div class="space-y-6">
    <!-- Connection Status -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Google Drive Connection</h3>
        
        @if($isConnected)
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-700">Connected as {{ $connectionEmail }}</span>
                </div>
                <flux:button variant="outline" wire:click="disconnectGoogleDrive">
                    Disconnect
                </flux:button>
            </div>
        @else
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No Google Drive connection</h3>
                <p class="mt-1 text-sm text-gray-500">Connect your Google Drive to enable file synchronization.</p>
                <div class="mt-6">
                    <flux:button wire:click="connectGoogleDrive">
                        Connect Google Drive
                    </flux:button>
                </div>
            </div>
        @endif
    </div>
    
    @if($isConnected)
        <!-- Sync Settings -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Synchronization Settings</h3>
            
            <div class="space-y-4">
                <div>
                    <flux:field>
                        <flux:label>Sync Mode</flux:label>
                        <flux:select wire:model="syncMode">
                            <option value="backup_only">Backup Only</option>
                            <option value="selective_sync">Selective Sync</option>
                            <option value="active_monitoring">Active Monitoring</option>
                            <option value="bidirectional">Bidirectional Sync</option>
                        </flux:select>
                    </flux:field>
                </div>
                
                <div>
                    <flux:field>
                        <flux:checkbox wire:model="autoBackup">
                            Automatically backup uploaded files
                        </flux:checkbox>
                    </flux:field>
                </div>
                
                <div>
                    <flux:field>
                        <flux:label>Sync Frequency</flux:label>
                        <flux:select wire:model="syncFrequency">
                            <option value="realtime">Real-time</option>
                            <option value="hourly">Hourly</option>
                            <option value="daily">Daily</option>
                        </flux:select>
                    </flux:field>
                </div>
                
                <div>
                    <flux:field>
                        <flux:label>Conflict Resolution</flux:label>
                        <flux:select wire:model="conflictResolution">
                            <option value="manual">Manual Resolution</option>
                            <option value="mixpitch_wins">MixPitch Version Wins</option>
                            <option value="drive_wins">Google Drive Version Wins</option>
                        </flux:select>
                    </flux:field>
                </div>
            </div>
            
            <div class="mt-6">
                <flux:button wire:click="updateSettings">
                    Save Settings
                </flux:button>
            </div>
        </div>
    @endif
</div>
```

---

## Implementation Phases

### Phase 1: Foundation (Weeks 1-2)
**Goal**: Establish core infrastructure and basic Google Drive connectivity

#### Tasks:
1. **Database Schema Implementation**
   - Create migration files for all Google Drive tables
   - Set up model relationships and factories
   - Create seeders for testing

2. **Google Drive Service Setup**
   - Configure Google Cloud Project and OAuth credentials
   - Implement `GoogleDriveService` with basic API operations
   - Set up OAuth flow and token management

3. **Basic Authentication**
   - Create OAuth routes and controllers
   - Implement connection/disconnection functionality
   - Add connection status to user dashboard

#### Deliverables:
- Database migrations and models
- Basic Google Drive API service
- OAuth authentication flow
- Connection management UI

### Phase 2: File Operations (Weeks 3-4)
**Goal**: Implement core file synchronization functionality

#### Tasks:
1. **File Upload Integration**
   - Modify `FileManagementService` to support Google Drive backup
   - Implement backup-only sync mode
   - Create background jobs for file uploads

2. **Settings Management**
   - Build settings models and services
   - Create user settings UI components
   - Implement project-level settings

3. **Basic Sync Engine**
   - Develop `GoogleDriveSyncService`
   - Implement file metadata tracking
   - Create sync status monitoring

#### Deliverables:
- File backup functionality
- Settings management system
- Basic sync engine
- User settings interface

### Phase 3: Advanced Sync (Weeks 5-6)
**Goal**: Implement bidirectional sync and conflict resolution

#### Tasks:
1. **Webhook System**
   - Set up Google Drive Events API integration
   - Implement webhook handlers and processing
   - Create change detection and processing jobs

2. **Bidirectional Sync**
   - Implement download from Google Drive
   - Add conflict detection and resolution
   - Create sync monitoring and logging

3. **Advanced Settings**
   - Add selective sync functionality
   - Implement directory monitoring
   - Create file type filtering

#### Deliverables:
- Webhook system for real-time changes
- Bidirectional synchronization
- Conflict resolution system
- Advanced sync settings

### Phase 4: UI/UX & Polish (Weeks 7-8)
**Goal**: Complete user interface and add monitoring/management features

#### Tasks:
1. **Enhanced UI Components**
   - Create file browser for Google Drive
   - Add sync status indicators
   - Implement conflict resolution interface

2. **Monitoring & Logging**
   - Build sync activity dashboard
   - Add error handling and notifications
   - Create sync statistics and reporting

3. **Testing & Optimization**
   - Comprehensive testing suite
   - Performance optimization
   - Documentation and user guides

#### Deliverables:
- Complete user interface
- Monitoring and logging system
- Comprehensive test suite
- Documentation

---

## Security Considerations

### Data Protection
1. **Token Security**: Encrypt OAuth tokens at rest
2. **Scope Limitation**: Request minimal required permissions
3. **Token Rotation**: Implement automatic token refresh
4. **Webhook Verification**: Validate all incoming webhooks

### Access Control
1. **User Isolation**: Ensure users can only access their own files
2. **Project Permissions**: Respect existing project access controls
3. **Rate Limiting**: Implement API rate limiting and quotas
4. **Audit Logging**: Log all Google Drive operations

### Privacy Compliance
1. **Data Minimization**: Only sync necessary file metadata
2. **User Consent**: Clear consent for Google Drive access
3. **Data Retention**: Implement data cleanup policies
4. **Transparency**: Clear documentation of data usage

---

## Testing Strategy

### Unit Tests
- Google Drive API service methods
- Sync engine logic and conflict resolution
- Settings management and validation
- Webhook processing and verification

### Integration Tests
- OAuth flow and token management
- File upload and download operations
- Webhook delivery and processing
- Database operations and relationships

### End-to-End Tests
- Complete sync workflows
- User interface interactions
- Error handling and recovery
- Performance under load

### Test Data Management
- Mock Google Drive API responses
- Test file fixtures and scenarios
- User permission test cases
- Webhook simulation tools

---

## Deployment & Monitoring

### Infrastructure Requirements
1. **Queue Workers**: Additional workers for sync jobs
2. **Webhook Endpoint**: Secure HTTPS endpoint for Google Drive
3. **Storage**: Additional database storage for sync metadata
4. **Monitoring**: Enhanced logging and alerting

### Deployment Checklist
1. **Environment Configuration**
   - Google Cloud credentials
   - OAuth client configuration
   - Webhook URL setup
   - Queue configuration

2. **Database Migration**
   - Run new migrations
   - Verify foreign key constraints
   - Test data integrity

3. **Service Configuration**
   - Configure Google Drive API quotas
   - Set up webhook endpoints
   - Test OAuth flow

### Monitoring & Alerting
1. **Sync Performance**: Monitor sync job success rates
2. **API Quotas**: Track Google Drive API usage
3. **Error Rates**: Alert on sync failures
4. **User Adoption**: Track connection and usage metrics

---

## Future Enhancements

### Advanced Features
1. **Collaborative Editing**: Real-time collaborative file editing
2. **Version History**: Integration with Google Drive version history
3. **Shared Folders**: Support for shared Google Drive folders
4. **Advanced Permissions**: Granular file-level permissions

### Integration Expansions
1. **Multiple Accounts**: Support for multiple Google Drive accounts
2. **Team Drives**: Integration with Google Workspace Team Drives
3. **Other Cloud Providers**: Dropbox, OneDrive integration
4. **Mobile Apps**: Native mobile app integration

### Performance Optimizations
1. **Batch Operations**: Batch file operations for efficiency
2. **Smart Sync**: Intelligent sync based on file usage patterns
3. **Compression**: File compression for faster transfers
4. **CDN Integration**: CDN caching for frequently accessed files

---

## Conclusion

This comprehensive Google Drive integration will significantly enhance MixPitch's file management capabilities, providing users with flexible, powerful synchronization options while maintaining the platform's focus on music collaboration. The phased implementation approach ensures a stable rollout with continuous user feedback integration.

The integration leverages MixPitch's existing infrastructure patterns and maintains consistency with the platform's architecture, ensuring long-term maintainability and scalability.
