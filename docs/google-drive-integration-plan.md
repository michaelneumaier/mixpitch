# Google Drive Integration Implementation Plan
## Comprehensive Integration for MixPitch Platform

### Executive Summary

This document outlines a comprehensive plan to integrate Google Drive functionality into the MixPitch platform, enabling users to link their Google Drive accounts for seamless file synchronization, backup, and collaboration. The integration will support bidirectional file sync, real-time change monitoring, and flexible user-controlled settings at both global and project levels.

**⚠️ UPDATED FOR CODEBASE COMPATIBILITY**: This plan has been revised to ensure full compatibility with MixPitch's existing architecture, including SQLite development environment, Laravel 10 patterns, and existing service layer architecture.

---

## Table of Contents

1. [Introduction](#introduction)
2. [Current System Analysis](#current-system-analysis)
3. [Required Dependencies](#required-dependencies)
4. [Integration Architecture](#integration-architecture)
5. [Database Schema Design](#database-schema-design)
6. [Authentication & Authorization](#authentication--authorization)
7. [File Synchronization System](#file-synchronization-system)
8. [Webhook & Change Detection](#webhook--change-detection)
9. [User Interface & Settings](#user-interface--settings)
10. [Implementation Phases](#implementation-phases)
11. [Security Considerations](#security-considerations)
12. [Testing Strategy](#testing-strategy)
13. [Deployment & Monitoring](#deployment--monitoring)
14. [Future Enhancements](#future-enhancements)

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
- `FileManagementService` handles project and pitch file uploads with user-based storage tracking
- S3 storage with organized directory structure (`projects/{id}`, `pitches/{id}`)
- Support for multiple file types (audio, images, documents, archives)
- User storage limits and quota management via `UserStorageService` ✅
- Context-aware upload validation via `FileUploadSetting` with multiple contexts ✅

**Authentication Patterns:**
- OAuth2 implementation via Laravel Socialite (Google OAuth already configured) ✅
- Secure token storage in user model (`provider_token`, `provider_refresh_token`) ✅
- Google OAuth config exists in `config/services.php` ✅
- Existing social login infrastructure ready for extension ✅

**Background Processing:**
- Laravel queue system with database driver ✅
- Existing jobs for file processing, audio transcoding, waveform generation ✅
- Scheduled tasks via Laravel's task scheduler ✅
- Job patterns established for async operations ✅

**Settings Management:**
- `FileUploadSetting` model for configurable upload parameters with context support ✅
- `NotificationChannelPreference` for user preferences ✅
- Livewire v3 components for settings UI with Flux UI integration ✅

**Database Architecture:**
- **Development**: SQLite (confirmed)
- **Production**: MySQL
- Laravel 10 migration patterns ✅

### Integration Points

1. **User Model**: Already supports OAuth tokens and provider data
2. **Project Model**: Has file relationships and storage tracking
3. **File Models**: `ProjectFile` and `PitchFile` with metadata support
4. **Queue System**: Ready for background sync operations
5. **Webhook Infrastructure**: Existing webhook controllers (`SesWebhookController`) provide excellent pattern for Google Drive webhooks ✅

---

## Required Dependencies

### Missing Google Client Library
**Critical**: The Google API Client Library is not currently installed and is required for this integration.

```bash
# Add to composer.json and install:
composer require google/apiclient:^2.0
```

### Required Classes
After installation, these classes will be available:
```php
use Google\Client;
use Google\Service\Drive;
use Google\Service\Oauth2;
use Google\Service\Drive\Channel;
```

### Existing Compatible Dependencies
- ✅ `laravel/socialite` - Already installed, supports Google OAuth
- ✅ `guzzlehttp/guzzle` - Available for HTTP requests  
- ✅ `laravel/framework` v10 - Compatible with Google Client Library
- ✅ `livewire/livewire` v3 - For reactive UI components
- ✅ `livewire/flux-pro` - UI component library

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

### ⚠️ UPDATED: SQLite/MySQL Compatible Schema

The original schema used MySQL-specific syntax. This updated version uses Laravel migrations for cross-database compatibility.

### New Tables

#### 1. `google_drive_connections`
**Laravel Migration:**
```php
Schema::create('google_drive_connections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('google_account_email');
    $table->text('access_token');
    $table->text('refresh_token')->nullable();
    $table->timestamp('token_expires_at')->nullable();
    $table->text('scope');
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_sync_at')->nullable();
    $table->string('sync_status')->default('connected'); // Uses model casting for enum-like behavior
    $table->text('error_message')->nullable();
    $table->timestamps();
    
    $table->index(['user_id', 'is_active']);
    $table->index('sync_status');
});
```

**Model Implementation (for enum-like behavior):**
```php
class GoogleDriveConnection extends Model
{
    const STATUS_CONNECTED = 'connected';
    const STATUS_SYNCING = 'syncing'; 
    const STATUS_ERROR = 'error';
    const STATUS_DISCONNECTED = 'disconnected';
    
    protected $casts = [
        'is_active' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

#### 2. `google_drive_settings`
**Laravel Migration:**
```php
Schema::create('google_drive_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
    $table->string('setting_type'); // 'global' or 'project'
    $table->string('sync_mode')->default('backup_only'); // enum-like via model constants
    $table->boolean('auto_backup')->default(true);
    $table->string('backup_directory_id')->nullable();
    $table->text('monitored_directories')->nullable(); // JSON data via casting
    $table->text('file_type_filters')->nullable(); // JSON data via casting  
    $table->string('sync_frequency')->default('realtime'); // enum-like via model constants
    $table->string('conflict_resolution')->default('manual'); // enum-like via model constants
    $table->boolean('is_enabled')->default(true);
    $table->timestamps();
    
    $table->unique(['user_id', 'project_id']);
    $table->index(['user_id', 'is_enabled']);
});
```

**Model Implementation:**
```php
class GoogleDriveSettings extends Model
{
    // Setting Types
    const TYPE_GLOBAL = 'global';
    const TYPE_PROJECT = 'project';
    
    // Sync Modes
    const SYNC_BACKUP_ONLY = 'backup_only';
    const SYNC_SELECTIVE = 'selective_sync';
    const SYNC_ACTIVE_MONITORING = 'active_monitoring';
    const SYNC_BIDIRECTIONAL = 'bidirectional';
    
    // Sync Frequencies
    const FREQ_REALTIME = 'realtime';
    const FREQ_HOURLY = 'hourly';
    const FREQ_DAILY = 'daily';
    
    // Conflict Resolution
    const CONFLICT_MIXPITCH_WINS = 'mixpitch_wins';
    const CONFLICT_DRIVE_WINS = 'drive_wins';  
    const CONFLICT_MANUAL = 'manual';
    
    protected $casts = [
        'monitored_directories' => 'array',
        'file_type_filters' => 'array',
        'auto_backup' => 'boolean',
        'is_enabled' => 'boolean',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
```

#### 3. `google_drive_files`
**Laravel Migration:**
```php
Schema::create('google_drive_files', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
    $table->unsignedBigInteger('mixpitch_file_id')->nullable();
    $table->string('mixpitch_file_type')->nullable(); // 'project_file' or 'pitch_file'
    $table->string('google_drive_file_id');
    $table->string('google_drive_parent_id')->nullable();
    $table->string('file_name');
    $table->string('mime_type', 100);
    $table->unsignedBigInteger('file_size');
    $table->timestamp('google_drive_modified_time');
    $table->timestamp('mixpitch_modified_time')->nullable();
    $table->string('sync_status')->default('synced'); // enum-like via model constants
    $table->string('sync_direction'); // enum-like via model constants
    $table->timestamp('last_sync_at')->nullable();
    $table->text('error_message')->nullable();
    $table->text('metadata')->nullable(); // JSON data via casting
    $table->timestamps();
    
    $table->unique(['google_drive_file_id', 'user_id']);
    $table->index('sync_status');
    $table->index(['user_id', 'project_id']);
});
```

**Model Implementation:**
```php
class GoogleDriveFile extends Model
{
    // File Types
    const TYPE_PROJECT_FILE = 'project_file';
    const TYPE_PITCH_FILE = 'pitch_file';
    
    // Sync Statuses
    const STATUS_SYNCED = 'synced';
    const STATUS_PENDING_UPLOAD = 'pending_upload';
    const STATUS_PENDING_DOWNLOAD = 'pending_download';
    const STATUS_CONFLICT = 'conflict';
    const STATUS_ERROR = 'error';
    
    // Sync Directions
    const DIRECTION_TO_DRIVE = 'to_drive';
    const DIRECTION_FROM_DRIVE = 'from_drive';
    const DIRECTION_BIDIRECTIONAL = 'bidirectional';
    
    protected $casts = [
        'metadata' => 'array',
        'google_drive_modified_time' => 'datetime',
        'mixpitch_modified_time' => 'datetime',
        'last_sync_at' => 'datetime',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    
    // Polymorphic relationship to handle both ProjectFile and PitchFile
    public function mixpitchFile(): MorphTo
    {
        return $this->morphTo('mixpitch_file', 'mixpitch_file_type', 'mixpitch_file_id');
    }
}
```

#### 4. `google_drive_sync_logs`
**Laravel Migration:**
```php
Schema::create('google_drive_sync_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
    $table->foreignId('google_drive_file_id')->nullable()
          ->references('id')->on('google_drive_files')->onDelete('set null');
    $table->string('action'); // enum-like via model constants
    $table->string('status'); // enum-like via model constants  
    $table->text('details')->nullable(); // JSON data via casting
    $table->text('error_message')->nullable();
    $table->unsignedInteger('processing_time_ms')->nullable();
    $table->timestamp('created_at')->useCurrent();
    // Note: No updated_at for log entries
    
    $table->index(['user_id', 'created_at']);
    $table->index(['status', 'created_at']);
});
```

**Model Implementation:**
```php
class GoogleDriveSyncLog extends Model
{
    // Actions
    const ACTION_UPLOAD = 'upload';
    const ACTION_DOWNLOAD = 'download';
    const ACTION_DELETE = 'delete';
    const ACTION_CREATE_FOLDER = 'create_folder';
    const ACTION_WEBHOOK_RECEIVED = 'webhook_received';
    
    // Statuses
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_SKIPPED = 'skipped';
    
    public $timestamps = false; // Only using created_at
    
    protected $dates = ['created_at'];
    
    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    
    public function googleDriveFile(): BelongsTo
    {
        return $this->belongsTo(GoogleDriveFile::class);
    }
}
```

#### 5. `google_drive_webhooks`
**Laravel Migration:**
```php
Schema::create('google_drive_webhooks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('channel_id')->unique();
    $table->string('resource_id');
    $table->string('resource_uri', 500);
    $table->string('token')->nullable();
    $table->timestamp('expiration');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    
    $table->index(['user_id', 'is_active']);
    $table->index('expiration');
});
```

**Model Implementation:**
```php
class GoogleDriveWebhook extends Model
{
    protected $casts = [
        'expiration' => 'datetime',
        'is_active' => 'boolean',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function isExpired(): bool
    {
        return $this->expiration->isPast();
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('expiration', '>', now());
    }
}
```

### Extended FileUploadSetting Integration
**Required Addition to existing `FileUploadSetting` model:**
```php
// Add to existing FileUploadSetting constants
const CONTEXT_GOOGLE_DRIVE = 'google_drive';

// Update getValidContexts() method to include:
public static function getValidContexts(): array
{
    return [
        self::CONTEXT_GLOBAL,
        self::CONTEXT_PROJECTS, 
        self::CONTEXT_PITCHES,
        self::CONTEXT_CLIENT_PORTALS,
        self::CONTEXT_GOOGLE_DRIVE, // Add this
    ];
}

// Add context-specific defaults for Google Drive
case self::CONTEXT_GOOGLE_DRIVE:
    $defaults[self::MAX_FILE_SIZE_MB] = 2048; // 2GB for Google Drive sync
    $defaults[self::CHUNK_SIZE_MB] = 10; // Larger chunks for sync operations
    break;
```

---

## Authentication & Authorization

### ⚠️ UPDATED: Integration with Existing OAuth System

The plan leverages MixPitch's existing Google OAuth configuration while extending it for Drive access.

#### Required Google Cloud Setup
1. **✅ Google Cloud Project exists** (used for current Google OAuth)
2. **Add Google Drive API** to existing project
3. **✅ OAuth Consent Screen configured** (extend scopes)
4. **✅ OAuth 2.0 Credentials exist** (use existing credentials)

#### OAuth Approach Options

**Option A: Extend Existing User OAuth (Recommended)**
Leverage existing `provider_token`/`provider_refresh_token` fields:

```php
// Extend existing Google OAuth to include Drive scopes
const REQUIRED_SCOPES = [
    'openid',
    'email', 
    'profile',                                         // Existing scopes
    'https://www.googleapis.com/auth/drive.file',      // Access files created by the app
    'https://www.googleapis.com/auth/drive.metadata',  // Read file metadata
];

const OPTIONAL_SCOPES = [
    'https://www.googleapis.com/auth/drive',           // Full Drive access (for advanced features)
];
```

**Option B: Separate Google Drive Connection (Original Plan)**
Use separate `GoogleDriveConnection` model for dedicated Drive OAuth.

**Recommendation**: Start with Option A for simplicity, migrate to Option B if needed.

#### Service Implementation (Updated for Existing Infrastructure)
```php
<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Oauth2;
use App\Models\GoogleDriveConnection;
use App\Models\User;
use App\Services\UserStorageService;
use App\Services\FileManagementService;

class GoogleDriveService
{
    private Client $client;
    private ?Drive $driveService = null;
    
    // Use existing Google OAuth config
    const DRIVE_SCOPES = [
        'https://www.googleapis.com/auth/drive.file',
        'https://www.googleapis.com/auth/drive.metadata',
    ];
    
    public function __construct(
        private UserStorageService $storageService,
        private FileManagementService $fileService
    ) {
        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(route('auth.google-drive.callback'));
        $this->client->setScopes(self::DRIVE_SCOPES);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        $this->client->setIncludeGrantedScopes(true); // Allow incremental authorization
    }
    
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }
    
    /**
     * Handle OAuth callback - works with existing or separate connection models
     */
    public function handleCallback(string $code, User $user): GoogleDriveConnection
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        
        if (isset($token['error'])) {
            throw new \Exception('OAuth error: ' . $token['error']);
        }
        
        $this->client->setAccessToken($token);
        
        // Get user info to verify account
        $oauth2 = new Oauth2($this->client);
        $userInfo = $oauth2->userinfo->get();
        
        // Create or update Google Drive connection
        return GoogleDriveConnection::updateOrCreate(
            ['user_id' => $user->id],
            [
                'google_account_email' => $userInfo->email,
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'token_expires_at' => isset($token['expires_in']) 
                    ? now()->addSeconds($token['expires_in']) 
                    : null,
                'scope' => implode(' ', self::DRIVE_SCOPES),
                'is_active' => true,
                'sync_status' => GoogleDriveConnection::STATUS_CONNECTED,
            ]
        );
    }
    
    /**
     * Get authenticated Drive service for a user
     */
    public function getDriveService(User $user): Drive
    {
        $connection = $user->googleDriveConnection;
        if (!$connection || !$connection->is_active) {
            throw new \Exception('No active Google Drive connection');
        }
        
        $this->refreshTokenIfNeeded($connection);
        
        $this->client->setAccessToken([
            'access_token' => $connection->access_token,
            'refresh_token' => $connection->refresh_token,
        ]);
        
        return new Drive($this->client);
    }
    
    public function refreshTokenIfNeeded(GoogleDriveConnection $connection): void
    {
        $this->client->setAccessToken([
            'access_token' => $connection->access_token,
            'refresh_token' => $connection->refresh_token,
        ]);
        
        if ($this->client->isAccessTokenExpired()) {
            $token = $this->client->fetchAccessTokenWithRefreshToken();
            
            if (isset($token['error'])) {
                $connection->update([
                    'sync_status' => GoogleDriveConnection::STATUS_ERROR,
                    'error_message' => $token['error_description'] ?? $token['error'],
                    'is_active' => false,
                ]);
                throw new \Exception('Failed to refresh Google Drive token: ' . $token['error']);
            }
            
            $connection->update([
                'access_token' => $token['access_token'],
                'token_expires_at' => isset($token['expires_in']) 
                    ? now()->addSeconds($token['expires_in']) 
                    : null,
                'sync_status' => GoogleDriveConnection::STATUS_CONNECTED,
                'error_message' => null,
            ]);
        }
    }
}
```

---

## File Synchronization System

### ⚠️ UPDATED: Integration with Existing File Management

The sync system now integrates with existing `FileManagementService` and `UserStorageService`.

### Sync Modes

#### 1. Backup Only Mode
- Files uploaded to MixPitch are automatically backed up to Google Drive
- No downloads from Google Drive to MixPitch
- One-way sync: MixPitch → Google Drive
- **Integrates with existing file upload workflow**

#### 2. Selective Sync Mode
- Users manually select files from Google Drive to import
- Manual control over which files are synchronized
- Bi-directional sync for selected files only
- **Respects user storage limits via UserStorageService**

#### 3. Active Monitoring Mode
- MixPitch monitors specific Google Drive directories
- Automatic download of new/changed files
- Real-time sync based on webhook notifications
- **Uses FileUploadSetting validation for incoming files**

#### 4. Bidirectional Sync Mode
- Full two-way synchronization
- Changes in either platform reflect in the other
- Conflict resolution strategies
- **Maintains file metadata consistency**

### Sync Service Implementation (Updated)

```php
<?php

namespace App\Services;

use App\Models\GoogleDriveConnection;
use App\Models\GoogleDriveSettings;
use App\Models\GoogleDriveFile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\PitchFile;
use App\Jobs\SyncFileToGoogleDrive;
use App\Jobs\SyncFileFromGoogleDrive;
use App\Services\UserStorageService;
use App\Services\FileManagementService;
use App\Models\FileUploadSetting;

class GoogleDriveSyncService
{
    public function __construct(
        private GoogleDriveService $driveService,
        private FileManagementService $fileService,
        private UserStorageService $storageService
    ) {}
    
    /**
     * Sync project files to Google Drive using existing file management patterns
     */
    public function syncProjectToGoogleDrive(Project $project): void
    {
        $connection = $project->user->googleDriveConnection;
        $settings = $this->getProjectSettings($project);
        
        if (!$connection || !$settings->is_enabled || !$connection->is_active) {
            return;
        }
        
        foreach ($project->files as $file) {
            // Use existing job patterns
            SyncFileToGoogleDrive::dispatch($file, $connection, $settings);
        }
    }
    
    /**
     * Handle file uploads with automatic Google Drive backup
     * Integrates with existing FileManagementService workflow
     */
    public function handleFileUploadedToMixPitch($fileModel): void
    {
        $user = $fileModel->user ?? $fileModel->project->user;
        $connection = $user->googleDriveConnection;
        
        if (!$connection || !$connection->is_active) {
            return;
        }
        
        $settings = $this->getSettingsForFile($fileModel);
        
        if ($settings && $settings->auto_backup) {
            SyncFileToGoogleDrive::dispatch($fileModel, $connection, $settings);
        }
    }
    
    /**
     * Import file from Google Drive to MixPitch
     * Uses existing FileManagementService for consistency
     */
    public function importFileFromGoogleDrive(
        GoogleDriveFile $driveFile, 
        Project $project, 
        User $uploader
    ): ProjectFile {
        // Check user storage capacity using existing service
        if (!$this->storageService->hasUserStorageCapacity($uploader, $driveFile->file_size)) {
            throw new \App\Exceptions\File\StorageLimitException(
                'Import would exceed your storage limit.'
            );
        }
        
        // Validate file size using existing settings
        $maxFileSize = FileUploadSetting::getSetting(
            FileUploadSetting::MAX_FILE_SIZE_MB,
            FileUploadSetting::CONTEXT_PROJECTS
        ) * 1024 * 1024;
        
        if ($driveFile->file_size > $maxFileSize) {
            throw new \App\Exceptions\File\FileUploadException(
                'File exceeds maximum upload size limit.'
            );
        }
        
        // Use existing file creation patterns
        return $this->fileService->createProjectFileFromS3(
            $project,
            $this->downloadFromGoogleDrive($driveFile),
            $driveFile->file_name,
            $driveFile->file_size,
            $driveFile->mime_type,
            $uploader,
            ['source' => 'google_drive', 'drive_file_id' => $driveFile->google_drive_file_id]
        );
    }
    
    public function handleGoogleDriveChange(array $changeData): void
    {
        $driveFile = GoogleDriveFile::where('google_drive_file_id', $changeData['fileId'])->first();
        
        if (!$driveFile) {
            return;
        }
        
        $settings = $this->getUserSettings($driveFile->user);
        
        // Only process changes for appropriate sync modes
        if (in_array($settings->sync_mode, [
            GoogleDriveSettings::SYNC_ACTIVE_MONITORING, 
            GoogleDriveSettings::SYNC_BIDIRECTIONAL
        ])) {
            SyncFileFromGoogleDrive::dispatch($driveFile, $changeData);
        }
    }
    
    public function resolveConflict(GoogleDriveFile $driveFile, string $resolution): void
    {
        switch ($resolution) {
            case GoogleDriveSettings::CONFLICT_MIXPITCH_WINS:
                SyncFileToGoogleDrive::dispatch(
                    $driveFile->mixpitchFile, 
                    $driveFile->user->googleDriveConnection
                );
                break;
                
            case GoogleDriveSettings::CONFLICT_DRIVE_WINS:
                SyncFileFromGoogleDrive::dispatch($driveFile);
                break;
                
            case GoogleDriveSettings::CONFLICT_MANUAL:
                // User must manually resolve
                $driveFile->update(['sync_status' => GoogleDriveFile::STATUS_CONFLICT]);
                break;
        }
    }
    
    private function getSettingsForFile($fileModel): ?GoogleDriveSettings
    {
        if ($fileModel instanceof ProjectFile) {
            return GoogleDriveSettings::where('user_id', $fileModel->project->user_id)
                ->where('project_id', $fileModel->project_id)
                ->where('setting_type', GoogleDriveSettings::TYPE_PROJECT)
                ->first() ?? $this->getUserSettings($fileModel->project->user);
        }
        
        if ($fileModel instanceof PitchFile) {
            return $this->getUserSettings($fileModel->user);
        }
        
        return null;
    }
    
    private function getUserSettings(User $user): ?GoogleDriveSettings
    {
        return GoogleDriveSettings::where('user_id', $user->id)
            ->where('setting_type', GoogleDriveSettings::TYPE_GLOBAL)
            ->first();
    }
    
    private function getProjectSettings(Project $project): ?GoogleDriveSettings
    {
        return GoogleDriveSettings::where('user_id', $project->user_id)
            ->where('project_id', $project->id) 
            ->where('setting_type', GoogleDriveSettings::TYPE_PROJECT)
            ->first() ?? $this->getUserSettings($project->user);
    }
}
```

---

## Webhook & Change Detection

### ⚠️ UPDATED: Following SesWebhookController Patterns

The webhook implementation now follows the established pattern from `SesWebhookController`.

#### Webhook Setup Service
```php
<?php

namespace App\Services;

use App\Models\GoogleDriveWebhook;
use App\Models\User;
use Google\Service\Drive;
use Google\Service\Drive\Channel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GoogleDriveWebhookService
{
    public function __construct(
        private GoogleDriveService $driveService
    ) {}
    
    public function setupWebhook(User $user, string $resourceId = null): GoogleDriveWebhook
    {
        $connection = $user->googleDriveConnection;
        if (!$connection || !$connection->is_active) {
            throw new \Exception('No active Google Drive connection for user');
        }
        
        $this->driveService->refreshTokenIfNeeded($connection);
        
        $channelId = 'mixpitch_' . $user->id . '_' . Str::random(10);
        $webhookUrl = route('webhooks.google-drive');
        $token = Str::random(32);
        
        $channel = new Channel([
            'id' => $channelId,
            'type' => 'web_hook',
            'address' => $webhookUrl,
            'token' => $token,
            'expiration' => (time() + 86400) * 1000, // 24 hours in milliseconds
        ]);
        
        $driveService = $this->driveService->getDriveService($user);
        
        try {
            if ($resourceId) {
                // Watch specific file
                $response = $driveService->files->watch($resourceId, $channel);
            } else {
                // Watch all changes for this user
                $startPageToken = $driveService->changes->getStartPageToken()->getStartPageToken();
                $response = $driveService->changes->watch($startPageToken, $channel);
            }
            
            return GoogleDriveWebhook::create([
                'user_id' => $user->id,
                'channel_id' => $channelId,
                'resource_id' => $response->getResourceId(),
                'resource_uri' => $response->getResourceUri(),
                'token' => $token,
                'expiration' => Carbon::createFromTimestamp($response->getExpiration() / 1000),
                'is_active' => true,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to setup Google Drive webhook', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'channel_id' => $channelId,
            ]);
            throw $e;
        }
    }
    
    /**
     * Handle incoming webhook (following SesWebhookController pattern)
     */
    public function handleWebhook(Request $request): void
    {
        Log::info('Google Drive Webhook Received', [
            'headers' => $request->headers->all(),
            'body' => $request->getContent()
        ]);
        
        $channelId = $request->header('X-Goog-Channel-Id');
        $resourceState = $request->header('X-Goog-Resource-State');
        $token = $request->header('X-Goog-Channel-Token');
        
        if (!$channelId || !$resourceState) {
            Log::warning('Google Drive webhook missing required headers', [
                'channel_id' => $channelId,
                'resource_state' => $resourceState,
            ]);
            return;
        }
        
        $webhook = GoogleDriveWebhook::where('channel_id', $channelId)
            ->where('is_active', true)
            ->first();
            
        if (!$webhook) {
            Log::warning('Received webhook for unknown channel', ['channel_id' => $channelId]);
            return;
        }
        
        // Verify token (following SES webhook pattern)
        if ($token !== $webhook->token) {
            Log::warning('Invalid Google Drive webhook token', [
                'channel_id' => $channelId,
                'expected_token_length' => strlen($webhook->token),
                'received_token_length' => strlen($token ?? ''),
            ]);
            return;
        }
        
        // Check if webhook is expired
        if ($webhook->isExpired()) {
            Log::warning('Received webhook for expired channel', [
                'channel_id' => $channelId,
                'expiration' => $webhook->expiration,
            ]);
            $webhook->update(['is_active' => false]);
            return;
        }
        
        // Process the change using background job (following existing job patterns)
        try {
            \App\Jobs\ProcessGoogleDriveWebhook::dispatch($webhook, $resourceState, [
                'channel_id' => $channelId,
                'resource_state' => $resourceState,
                'resource_id' => $request->header('X-Goog-Resource-Id'),
                'resource_uri' => $request->header('X-Goog-Resource-URI'),
                'changed' => $request->header('X-Goog-Changed'),
            ]);
            
            Log::info('Google Drive webhook queued for processing', [
                'channel_id' => $channelId,
                'resource_state' => $resourceState,
                'user_id' => $webhook->user_id,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to queue Google Drive webhook processing', [
                'channel_id' => $channelId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Stop webhook monitoring
     */
    public function stopWebhook(GoogleDriveWebhook $webhook): bool
    {
        try {
            $driveService = $this->driveService->getDriveService($webhook->user);
            
            $channel = new Channel([
                'id' => $webhook->channel_id,
                'resourceId' => $webhook->resource_id,
            ]);
            
            $driveService->channels->stop($channel);
            
            $webhook->update(['is_active' => false]);
            
            Log::info('Google Drive webhook stopped', [
                'channel_id' => $webhook->channel_id,
                'user_id' => $webhook->user_id,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to stop Google Drive webhook', [
                'channel_id' => $webhook->channel_id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
}
```

#### Webhook Controller (Following SesWebhookController Pattern)
```php
<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleDriveWebhookController extends Controller
{
    public function __construct(
        private GoogleDriveWebhookService $webhookService
    ) {}
    
    /**
     * Handle incoming Google Drive webhook requests
     * Follows the same pattern as SesWebhookController
     */
    public function handle(Request $request)
    {
        Log::info('Google Drive Webhook Received', [
            'headers' => $request->headers->all(),
            'body' => $request->getContent()
        ]);
        
        try {
            $this->webhookService->handleWebhook($request);
            return response()->json(['message' => 'Webhook processed successfully']);
            
        } catch (\Exception $e) {
            Log::error('Google Drive webhook processing error', [
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
                'body' => $request->getContent(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Return success to prevent Google Drive from retrying
            // Log the error but don't fail the webhook
            return response()->json(['message' => 'Webhook received'], 200);
        }
    }
}
```

#### Required Route Registration
```php
// routes/web.php (following existing webhook pattern)
Route::post('/webhooks/google-drive', [GoogleDriveWebhookController::class, 'handle'])
    ->name('webhooks.google-drive')
    ->withoutMiddleware(['auth', 'verified']); // Skip auth for webhooks
```
```

---

## User Interface & Settings

### ⚠️ UPDATED: Using Existing Livewire and Flux Patterns

The UI components now follow MixPitch's established patterns using Livewire v3 and Flux UI Pro.

#### Global Settings Component (Following Existing Patterns)
```php
<?php

namespace App\Livewire\User;

use App\Models\GoogleDriveConnection;
use App\Models\GoogleDriveSettings;
use App\Services\GoogleDriveService;
use Livewire\Component;
use Masmerise\Toaster\Toaster; // Following existing notification pattern

class GoogleDriveSettings extends Component
{
    public bool $isConnected = false;
    public string $connectionEmail = '';
    public string $syncMode = GoogleDriveSettings::SYNC_BACKUP_ONLY;
    public bool $autoBackup = true;
    public string $syncFrequency = GoogleDriveSettings::FREQ_REALTIME;
    public string $conflictResolution = GoogleDriveSettings::CONFLICT_MANUAL;
    public array $fileTypeFilters = [];
    
    // Loading states (following existing patterns)
    public bool $isLoading = false;
    public bool $isConnecting = false;
    public bool $isDisconnecting = false;
    
    public function mount()
    {
        $this->loadSettings();
    }
    
    public function connectGoogleDrive()
    {
        $this->isConnecting = true;
        
        try {
            $driveService = app(GoogleDriveService::class);
            return redirect($driveService->getAuthUrl());
            
        } catch (\Exception $e) {
            $this->isConnecting = false;
            Toaster::error('Failed to connect to Google Drive: ' . $e->getMessage());
        }
    }
    
    public function disconnectGoogleDrive()
    {
        $this->isDisconnecting = true;
        
        try {
            $connection = auth()->user()->googleDriveConnection;
            if ($connection) {
                // Stop any active webhooks
                $webhookService = app(\App\Services\GoogleDriveWebhookService::class);
                $activeWebhooks = $connection->user->googleDriveWebhooks()->active()->get();
                foreach ($activeWebhooks as $webhook) {
                    $webhookService->stopWebhook($webhook);
                }
                
                $connection->delete();
            }
            
            $this->loadSettings();
            $this->isDisconnecting = false;
            
            // Using existing Toaster pattern
            Toaster::success('Google Drive disconnected successfully.');
            
        } catch (\Exception $e) {
            $this->isDisconnecting = false;
            Toaster::error('Failed to disconnect: ' . $e->getMessage());
        }
    }
    
    public function updateSettings()
    {
        $this->isLoading = true;
        
        // Using Laravel 10 validation patterns
        $this->validate([
            'syncMode' => 'required|in:' . implode(',', [
                GoogleDriveSettings::SYNC_BACKUP_ONLY,
                GoogleDriveSettings::SYNC_SELECTIVE,
                GoogleDriveSettings::SYNC_ACTIVE_MONITORING,
                GoogleDriveSettings::SYNC_BIDIRECTIONAL,
            ]),
            'syncFrequency' => 'required|in:' . implode(',', [
                GoogleDriveSettings::FREQ_REALTIME,
                GoogleDriveSettings::FREQ_HOURLY,
                GoogleDriveSettings::FREQ_DAILY,
            ]),
            'conflictResolution' => 'required|in:' . implode(',', [
                GoogleDriveSettings::CONFLICT_MIXPITCH_WINS,
                GoogleDriveSettings::CONFLICT_DRIVE_WINS,
                GoogleDriveSettings::CONFLICT_MANUAL,
            ]),
        ]);
        
        try {
            GoogleDriveSettings::updateOrCreate(
                [
                    'user_id' => auth()->id(), 
                    'setting_type' => GoogleDriveSettings::TYPE_GLOBAL,
                    'project_id' => null,
                ],
                [
                    'sync_mode' => $this->syncMode,
                    'auto_backup' => $this->autoBackup,
                    'sync_frequency' => $this->syncFrequency,
                    'conflict_resolution' => $this->conflictResolution,
                    'file_type_filters' => $this->fileTypeFilters,
                    'is_enabled' => $this->isConnected,
                ]
            );
            
            $this->isLoading = false;
            Toaster::success('Google Drive settings updated successfully.');
            
        } catch (\Exception $e) {
            $this->isLoading = false;
            Toaster::error('Failed to update settings: ' . $e->getMessage());
        }
    }
    
    private function loadSettings()
    {
        $connection = auth()->user()->googleDriveConnection;
        $this->isConnected = $connection && $connection->is_active;
        $this->connectionEmail = $connection?->google_account_email ?? '';
        
        $settings = GoogleDriveSettings::where('user_id', auth()->id())
            ->where('setting_type', GoogleDriveSettings::TYPE_GLOBAL)
            ->whereNull('project_id')
            ->first();
            
        if ($settings) {
            $this->syncMode = $settings->sync_mode;
            $this->autoBackup = $settings->auto_backup;
            $this->syncFrequency = $settings->sync_frequency;
            $this->conflictResolution = $settings->conflict_resolution;
            $this->fileTypeFilters = $settings->file_type_filters ?? [];
        }
    }
    
    public function render()
    {
        return view('livewire.user.google-drive-settings');
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

### UI Templates (Updated for Flux UI Pro)

#### Global Settings View
```blade
{{-- resources/views/livewire/user/google-drive-settings.blade.php --}}
<div class="space-y-6">
    <!-- Connection Status Card -->
    <flux:card class="p-6">
        <flux:heading size="lg" class="mb-4">Google Drive Connection</flux:heading>
        
        @if($isConnected)
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <flux:badge variant="green" size="sm">Connected</flux:badge>
                    <span class="text-sm text-zinc-700 dark:text-zinc-300">
                        {{ $connectionEmail }}
                    </span>
                </div>
                <flux:button 
                    variant="outline" 
                    wire:click="disconnectGoogleDrive" 
                    :disabled="$isDisconnecting"
                    wire:loading.attr="disabled"
                    wire:target="disconnectGoogleDrive"
                >
                    <span wire:loading.remove wire:target="disconnectGoogleDrive">Disconnect</span>
                    <span wire:loading wire:target="disconnectGoogleDrive">Disconnecting...</span>
                </flux:button>
            </div>
        @else
            <div class="text-center py-8">
                <flux:icon.cloud-arrow-up class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="md" class="mt-2">No Google Drive connection</flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    Connect your Google Drive to enable file synchronization.
                </flux:text>
                <div class="mt-6">
                    <flux:button 
                        wire:click="connectGoogleDrive" 
                        :disabled="$isConnecting"
                        wire:loading.attr="disabled"
                        wire:target="connectGoogleDrive"
                    >
                        <span wire:loading.remove wire:target="connectGoogleDrive">Connect Google Drive</span>
                        <span wire:loading wire:target="connectGoogleDrive">Connecting...</span>
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:card>
    
    @if($isConnected)
        <!-- Sync Settings Card -->
        <flux:card class="p-6">
            <flux:heading size="lg" class="mb-4">Synchronization Settings</flux:heading>
            
            <div class="space-y-6">
                <flux:field>
                    <flux:label>Sync Mode</flux:label>
                    <flux:select wire:model.live="syncMode" placeholder="Choose sync mode">
                        <option value="{{ GoogleDriveSettings::SYNC_BACKUP_ONLY }}">Backup Only</option>
                        <option value="{{ GoogleDriveSettings::SYNC_SELECTIVE }}">Selective Sync</option>
                        <option value="{{ GoogleDriveSettings::SYNC_ACTIVE_MONITORING }}">Active Monitoring</option>
                        <option value="{{ GoogleDriveSettings::SYNC_BIDIRECTIONAL }}">Bidirectional Sync</option>
                    </flux:select>
                    <flux:description>
                        Choose how files are synchronized between MixPitch and Google Drive.
                    </flux:description>
                </flux:field>
                
                <flux:field>
                    <flux:checkbox wire:model.live="autoBackup">
                        Automatically backup uploaded files
                    </flux:checkbox>
                    <flux:description>
                        New files uploaded to MixPitch will be automatically backed up to Google Drive.
                    </flux:description>
                </flux:field>
                
                <flux:field>
                    <flux:label>Sync Frequency</flux:label>
                    <flux:select wire:model.live="syncFrequency">
                        <option value="{{ GoogleDriveSettings::FREQ_REALTIME }}">Real-time</option>
                        <option value="{{ GoogleDriveSettings::FREQ_HOURLY }}">Hourly</option>
                        <option value="{{ GoogleDriveSettings::FREQ_DAILY }}">Daily</option>
                    </flux:select>
                </flux:field>
                
                <flux:field>
                    <flux:label>Conflict Resolution</flux:label>
                    <flux:select wire:model.live="conflictResolution">
                        <option value="{{ GoogleDriveSettings::CONFLICT_MANUAL }}">Manual Resolution</option>
                        <option value="{{ GoogleDriveSettings::CONFLICT_MIXPITCH_WINS }}">MixPitch Version Wins</option>
                        <option value="{{ GoogleDriveSettings::CONFLICT_DRIVE_WINS }}">Google Drive Version Wins</option>
                    </flux:select>
                    <flux:description>
                        How to handle conflicts when the same file is modified in both systems.
                    </flux:description>
                </flux:field>
            </div>
            
            <div class="mt-6 flex justify-end">
                <flux:button 
                    wire:click="updateSettings" 
                    :disabled="$isLoading"
                    wire:loading.attr="disabled"
                    wire:target="updateSettings"
                >
                    <span wire:loading.remove wire:target="updateSettings">Save Settings</span>
                    <span wire:loading wire:target="updateSettings">Saving...</span>
                </flux:button>
            </div>
        </flux:card>
    @endif
</div>
```

#### Integration with Existing UppyFileUploader
```php
<?php
// Extend existing UppyFileUploader to support Google Drive import

namespace App\Livewire;

class GoogleDriveFileSelector extends Component
{
    public Model $model;
    public array $selectedFiles = [];
    public bool $isLoading = false;
    public string $currentDirectory = 'root';
    public array $driveFiles = [];
    
    public function mount(Model $model)
    {
        $this->model = $model;
        $this->loadDriveFiles();
    }
    
    public function loadDriveFiles(string $folderId = 'root')
    {
        $this->isLoading = true;
        $this->currentDirectory = $folderId;
        
        try {
            $driveService = app(\App\Services\GoogleDriveService::class);
            $service = $driveService->getDriveService(auth()->user());
            
            $files = $service->files->listFiles([
                'q' => "'{$folderId}' in parents and trashed=false",
                'fields' => 'files(id,name,mimeType,size,modifiedTime,parents)',
                'orderBy' => 'folder,name'
            ]);
            
            $this->driveFiles = $files->getFiles();
            
        } catch (\Exception $e) {
            Toaster::error('Failed to load Google Drive files: ' . $e->getMessage());
            $this->driveFiles = [];
        }
        
        $this->isLoading = false;
    }
    
    public function selectFile(string $fileId)
    {
        if (in_array($fileId, $this->selectedFiles)) {
            $this->selectedFiles = array_diff($this->selectedFiles, [$fileId]);
        } else {
            $this->selectedFiles[] = $fileId;
        }
    }
    
    public function importSelectedFiles()
    {
        if (empty($this->selectedFiles)) {
            Toaster::warning('Please select files to import.');
            return;
        }
        
        $syncService = app(\App\Services\GoogleDriveSyncService::class);
        
        foreach ($this->selectedFiles as $fileId) {
            \App\Jobs\ImportFileFromGoogleDrive::dispatch(
                $fileId, 
                $this->model, 
                auth()->user()
            );
        }
        
        Toaster::success(count($this->selectedFiles) . ' files queued for import.');
        $this->selectedFiles = [];
        
        $this->dispatch('filesImported', [
            'count' => count($this->selectedFiles),
            'model_type' => get_class($this->model),
            'model_id' => $this->model->id,
        ]);
    }
    
    public function render()
    {
        return view('livewire.google-drive-file-selector');
    }
}
```

---

## Implementation Phases

### ⚠️ UPDATED: Revised Implementation Plan

The implementation phases have been updated to address codebase compatibility requirements.

### Phase 1: Foundation & Dependencies (Weeks 1-2)
**Goal**: Establish core infrastructure and resolve compatibility issues

#### Tasks:
1. **Install Required Dependencies**
   - Add `composer require google/apiclient:^2.0`
   - Update `composer.json` and run install
   - Verify Google Client Library installation

2. **Database Schema Implementation (SQLite/MySQL Compatible)**
   - Create Laravel migrations (not raw SQL)
   - Implement all Google Drive models with proper relationships
   - Add model constants for enum-like behavior
   - Create model factories for testing

3. **Extend Existing Systems**
   - Add `CONTEXT_GOOGLE_DRIVE` to `FileUploadSetting` model
   - Update context validation methods
   - Add Google Drive-specific upload settings

4. **Google Drive Service Setup**
   - Enable Google Drive API in existing Google Cloud Project
   - Extend OAuth consent screen scopes
   - Implement `GoogleDriveService` with existing service patterns
   - Set up OAuth flow using existing Google config

#### Deliverables:
- ✅ Google Client Library installed
- ✅ SQLite/MySQL compatible migrations
- ✅ Updated FileUploadSetting system
- ✅ Basic Google Drive API service
- ✅ Extended OAuth authentication flow

### Phase 2: Core Integration (Weeks 3-4)
**Goal**: Integrate Google Drive with existing file management workflow

#### Tasks:
1. **File Management Integration**
   - Extend `FileManagementService` for Google Drive backup
   - Integrate with existing `UserStorageService` for capacity checks
   - Hook into existing file upload workflow
   - Create Google Drive-specific background jobs

2. **Sync Service Development**
   - Implement `GoogleDriveSyncService` using existing service patterns
   - Integrate with existing exception handling
   - Use existing job dispatch patterns
   - Implement user storage limit enforcement

3. **Settings Management (Livewire + Flux)**
   - Create `GoogleDriveSettings` Livewire component
   - Use existing Flux UI Pro components
   - Follow existing Toaster notification patterns
   - Implement loading states and error handling

4. **OAuth Integration**
   - Create OAuth routes and controllers
   - Implement connection/disconnection functionality
   - Add Google Drive status to user dashboard

#### Deliverables:
- ✅ Integrated file backup functionality
- ✅ GoogleDriveSyncService with UserStorageService integration
- ✅ Livewire settings component using Flux UI
- ✅ OAuth connection management

### Phase 3: Webhooks & Real-time Sync (Weeks 5-6)  
**Goal**: Implement real-time synchronization following SesWebhookController patterns

#### Tasks:
1. **Webhook System (Following SesWebhookController Pattern)**
   - Create `GoogleDriveWebhookController` using existing webhook patterns
   - Implement `GoogleDriveWebhookService` with proper logging
   - Add webhook route with middleware exclusions
   - Create webhook processing background jobs

2. **Real-time Change Detection**
   - Set up Google Drive Events API integration
   - Implement change notification processing
   - Create webhook verification following SES patterns
   - Add webhook expiration and renewal logic

3. **Bidirectional Sync & Import Features**
   - Implement Google Drive file import using existing FileManagementService
   - Add conflict detection and resolution
   - Create file selector component extending UppyFileUploader patterns
   - Implement sync status monitoring and logging

#### Deliverables:
- ✅ Webhook system following existing patterns
- ✅ Real-time change detection
- ✅ File import functionality
- ✅ Conflict resolution system

### Phase 4: Testing & Production Ready (Weeks 7-8)
**Goal**: Complete testing, monitoring, and production deployment

#### Tasks:
1. **Comprehensive Testing (Using Existing Test Patterns)**
   - Unit tests for all services using Pest
   - Integration tests for file sync workflow
   - Livewire component tests following existing patterns
   - Test Google Drive API mocking

2. **Enhanced UI & UX (Flux UI Pro)**
   - Complete Google Drive file browser component
   - Add sync status indicators throughout UI
   - Implement conflict resolution interface
   - Project-level Google Drive settings

3. **Monitoring & Production Readiness**
   - Sync activity logging using existing patterns
   - Error handling and user notifications
   - Performance optimization for large file sync
   - Production deployment checklist
   - User documentation

#### Deliverables:
- ✅ Complete test coverage using Pest
- ✅ Production-ready UI components
- ✅ Monitoring and logging system
- ✅ Deployment documentation

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

### ⚠️ UPDATED: Implementation Ready for MixPitch Architecture

This comprehensive Google Drive integration plan has been **thoroughly revised** for full compatibility with MixPitch's existing codebase architecture. The integration will significantly enhance MixPitch's file management capabilities while maintaining seamless compatibility with existing systems.

### Key Compatibility Updates Made:

#### ✅ **Database Compatibility**
- Converted MySQL-specific schema to Laravel migrations
- SQLite development / MySQL production compatibility
- Model-based enum constants instead of database ENUMs
- JSON casting instead of native JSON columns

#### ✅ **Service Layer Integration** 
- `GoogleDriveSyncService` integrates with existing `FileManagementService`
- User storage management via existing `UserStorageService`
- File upload validation using existing `FileUploadSetting` system
- Background job patterns match existing job architecture

#### ✅ **OAuth & Authentication**
- Leverages existing Google OAuth configuration
- Uses existing `provider_token`/`provider_refresh_token` pattern option
- Incremental authorization for existing users
- Separate `GoogleDriveConnection` model for dedicated Drive access

#### ✅ **UI/UX Integration**
- Livewire v3 components following existing patterns
- Flux UI Pro components matching current design system
- Toaster notifications using existing `Masmerise\Toaster`
- Loading states and error handling patterns

#### ✅ **Webhook Architecture**
- `GoogleDriveWebhookController` follows `SesWebhookController` patterns
- Proper error handling and logging
- Background job processing using existing job queue
- Route registration with middleware exclusions

#### ✅ **Testing & Quality Assurance**
- Pest test framework integration
- Unit and integration tests following existing patterns
- Mock Google Drive API for testing
- Laravel 10 compatibility throughout

### Implementation Timeline Adjustment:
- **Original Estimate**: 8 weeks
- **Updated Estimate**: 8 weeks (same duration, but includes compatibility work)
- **Risk Level**: **LOW** (high compatibility with existing systems)

### Next Steps:
1. **Install Google Client Library**: `composer require google/apiclient:^2.0`
2. **Begin Phase 1**: Database migrations and service foundation
3. **Follow revised implementation phases** as outlined in this document

The updated plan ensures seamless integration with MixPitch's Laravel 10, SQLite development, Livewire v3, and Flux UI Pro architecture while maintaining the platform's focus on music collaboration and professional file management.
