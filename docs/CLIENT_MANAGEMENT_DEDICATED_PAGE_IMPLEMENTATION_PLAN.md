# Client Management Dedicated Page Implementation Plan (Plan C)

## Progress Tracking

### ✅ Completed
- [x] **Research Phase**: Analyzed existing codebase structure and components
- [x] **Plan Verification**: Confirmed accuracy of file paths and component structure
- [x] **Phase 1**: Create Reusable Blade Components
  - [x] `resources/views/components/file-management/storage-indicator.blade.php`
  - [x] `resources/views/components/file-management/file-list.blade.php`
  - [x] `resources/views/components/file-management/upload-section.blade.php`
  - [x] `resources/views/components/project/header.blade.php`
  - [x] `resources/views/components/pitch/workflow-status.blade.php`
- [x] **Phase 2**: Create ManageClientProject Component
  - [x] `app/Livewire/Project/ManageClientProject.php` - Main component with all functionality
  - [x] `resources/views/livewire/project/manage-client-project.blade.php` - View template
- [x] **Phase 3**: Update Routing and Navigation
  - [x] Added new route `/manage-client-project/{project}` → `ManageClientProject` component
  - [x] Updated `ManageProject` component to redirect Client Management projects
- [x] **Phase 4**: Fix Project Deletion
  - [x] Updated `ProjectObserver::deleted()` method to cascade delete pitches and files
  - [x] Completed `ProjectController::destroy()` method with proper error handling
  - [x] Added `FileManagementService` import to ProjectObserver
- [x] **Phase 5**: Update ManageProject Component
  - [x] Removed Client Management specific eager loading logic
  - [x] Simplified workflow type handling (kept safety nets in view)
  - [x] Component now focuses on Standard, Contest, and Direct Hire workflows
- [x] **Phase 6**: Testing Strategy
- [x] **Bug Fixes & Enhancements**: 
  - [x] Created missing `ClientReviewReady` mail class (`app/Mail/ClientReviewReady.php`)
  - [x] Created email template (`resources/views/emails/client/review_ready.blade.php`)
  - [x] Fixed "Submit for Client Review" functionality
  - [x] Added development logging to all client management email classes:
    - [x] `ClientReviewReady` - Logs review ready notifications
    - [x] `ClientProjectInvite` - Logs initial project invitations  
    - [x] `ClientProjectCompleted` - Logs project completion notifications
  - [x] Email content and metadata now logged to Laravel logs for development testing
  - [x] **Fixed email logging error**: Removed view rendering from logging to prevent "No hint path defined for [mail]" errors
  - [x] **Fixed client portal 403 signature errors**: Cleaned up duplicate and conflicting route definitions
    - [x] Removed duplicate client portal routes that were causing signature validation conflicts
    - [x] Kept only the correct route patterns: `/projects/{project:id}/portal` for view, `/client-portal/project/{project:id}/*` for actions
    - [x] Cleared route cache to ensure Laravel picks up the cleaned routes
    - [x] Verified all 6 client portal routes are properly defined and accessible
  - [x] **Client Portal Preview Feature**: Added testing functionality for project owners
    - [x] Added `previewClientPortal()` method to `ManageClientProject` component
    - [x] Added "Preview Client Portal" button in client details section
    - [x] Generates same signed URL that clients receive in emails
    - [x] Logs portal access for development tracking
    - [x] Provides easy way to test client experience without sending emails
  - [x] **Recall Submission Feature**: Implemented comprehensive solution for producers to modify submissions
    - [x] Added `recallSubmission()` method to `ManageClientProject` component
    - [x] Added smart resubmission detection when files are modified after submission
    - [x] Added `recallSubmission` policy method to `PitchPolicy`
    - [x] Updated `update` policy to allow editing `READY_FOR_REVIEW` status for Client Management
    - [x] Added UI for recall submission and resubmission in client project view
    - [x] Added submission management section with clear messaging and actions
    - [x] Tracks file changes and enables resubmission when files are modified

### 🔄 In Progress
- None! Implementation is complete.

### ⏳ Pending
- None! Implementation is complete.

## Plan Accuracy Verification

### ✅ Confirmed Existing Files
- `app/Livewire/FileUploader.php` - ✅ Exists and is reusable
- `app/Livewire/ManageProject.php` - ✅ Exists with file management functionality
- `app/Livewire/Pitch/Component/ManagePitch.php` - ✅ Exists with workflow management
- `app/Models/Project.php` - ✅ Has `isClientManagement()` method
- `app/Observers/ProjectObserver.php` - ✅ Exists but `deleted()` method is empty (needs fixing)
- `app/Services/FileManagementService.php` - ✅ Exists with file operations
- `app/Services/PitchWorkflowService.php` - ✅ Exists with workflow methods
- `app/Services/NotificationService.php` - ✅ Exists
- `resources/views/components/pitch-status-badge.blade.php` - ✅ Exists and can be reused
- Current routing structure - ✅ Confirmed `/manage-project/{project}` route exists

### 📝 Plan Corrections
- **Route Structure**: Current route is `/manage-project/{project}` not `/projects/{project}/manage`
- **View Path**: ManageProject uses `livewire.project.page.manage-project` view
- **Component Structure**: Livewire components are in `app/Livewire/` with subdirectories for organization
- **Existing Components**: `pitch-status-badge` component already exists and can be reused

## Overview

This document outlines the implementation of Plan C: creating a dedicated `ManageClientProject` Livewire component and page specifically for the Client Management workflow. This approach provides the most tailored user experience by combining essential project-level actions with pitch-level workflow management in a single, purpose-built interface.

## Current State Analysis

### Existing Components to Leverage
1. **FileUploader** (`app/Livewire/FileUploader.php`) - Reusable file upload component that works with both Project and Pitch models
2. **File Management UI** - Storage display, file listing, and file actions from ManagePitch component
3. **Project Header** - Project image, details, and metadata display from ManageProject
4. **Client Portal Integration** - Resend invite functionality from ManageProject
5. **Status Management** - Pitch status workflow from ManagePitch component

### Current Routing Structure
- Standard projects: `/manage-project/{project}` → `ManageProject` component
- Pitch management: `/projects/{project}/pitches/{pitch}/edit` → `ManagePitch` component via PitchController

### Current Deletion Behavior
- Project deletion: Uses form POST to `projects.destroy` route in ProjectController
- **Issue**: ProjectObserver has empty `deleted()` method - no automatic pitch cleanup
- **Risk**: Deleting a Client Management project may leave orphaned pitch records

## Implementation Plan

### Phase 1: Create Reusable Blade Components

#### 1.1 Extract File Management Components

**Create: `resources/views/components/file-management/storage-indicator.blade.php`**
```blade
@props(['storageUsedPercentage', 'storageLimitMessage', 'storageRemaining'])

<div class="mb-4 bg-base-200/50 p-3 rounded-lg">
    <div class="flex justify-between items-center mb-2">
        <span class="text-sm font-medium">Storage Used: {{ $storageLimitMessage }}</span>
        <span class="text-xs text-gray-500">{{ $storageRemaining }} remaining</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2.5">
        <div class="bg-primary h-2.5 rounded-full transition-all duration-500 {{ $storageUsedPercentage > 90 ? 'bg-red-500' : ($storageUsedPercentage > 70 ? 'bg-amber-500' : 'bg-primary') }}"
            style="width: {{ $storageUsedPercentage }}%"></div>
    </div>
    <div class="mt-2 text-xs text-gray-500">
        <i class="fas fa-info-circle text-blue-500 mr-1"></i>
        Maximum file size: 200MB. Total storage limit: 1GB.
    </div>
</div>
```

**Create: `resources/views/components/file-management/file-list.blade.php`**
```blade
@props(['files', 'canDelete' => true, 'formatFileSize'])

<div class="bg-white rounded-lg border border-base-300 shadow-sm overflow-hidden">
    <div class="p-4 border-b border-base-200 bg-base-100/50 flex justify-between items-center">
        <h5 class="font-medium text-base">Files ({{ $files->count() }})</h5>
        @if($files->count() > 0)
        <span class="text-xs text-gray-500">Total: {{ $formatFileSize($files->sum('size')) }}</span>
        @endif
    </div>
    
    <div class="divide-y divide-base-200">
        @forelse($files as $file)
        <div class="flex items-center justify-between py-3 px-4 hover:bg-base-100/50 transition-all duration-300">
            <div class="flex items-center overflow-hidden flex-1 pr-2">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex-shrink-0 flex items-center justify-center bg-base-200 text-gray-500 mr-3">
                    @if (Str::startsWith($file->mime_type, 'audio/'))
                        <i class="fas fa-music text-sm sm:text-base"></i>
                    @elseif ($file->mime_type == 'application/pdf')
                        <i class="fas fa-file-pdf text-sm sm:text-base text-red-500"></i>
                    @elseif (Str::startsWith($file->mime_type, 'image/'))
                        <i class="fas fa-file-image text-sm sm:text-base text-blue-500"></i>
                    @else
                        <i class="fas fa-file-alt text-sm sm:text-base"></i>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-medium truncate text-sm sm:text-base">{{ $file->file_name }}</div>
                    <div class="flex items-center text-xs text-gray-500">
                        <span>{{ $file->created_at->format('M d, Y') }}</span>
                        <span class="mx-1.5">•</span>
                        <span>{{ $formatFileSize($file->size) }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-1 sm:space-x-2">
                <button wire:click="downloadFile({{ $file->id }})"
                    class="btn btn-sm btn-ghost text-gray-600 hover:text-blue-600">
                    <i class="fas fa-download"></i>
                </button>
                @if($canDelete)
                <button wire:click="confirmDeleteFile({{ $file->id }})" 
                        class="btn btn-sm btn-ghost text-gray-600 hover:text-red-600">
                    <i class="fas fa-trash"></i>
                </button>
                @endif
            </div>
        </div>
        @empty
        <div class="p-8 sm:p-10 text-center text-gray-500 italic">
            <i class="fas fa-folder-open text-4xl sm:text-5xl text-gray-300 mb-3"></i>
            <p class="text-base sm:text-lg">No files uploaded yet</p>
            <p class="text-xs sm:text-sm mt-2">Upload files to share with your client</p>
        </div>
        @endforelse
    </div>
</div>
```

**Create: `resources/views/components/file-management/upload-section.blade.php`**
```blade
@props(['model', 'title' => 'Upload New Files', 'description' => 'Upload audio, PDFs, or images'])

<div class="bg-white rounded-lg border border-base-300 shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-base-200 bg-base-100/50">
        <h5 class="font-medium text-base">{{ $title }}</h5>
        <p class="text-xs text-gray-500 mt-1">{{ $description }}</p>
    </div>
    <div class="p-4">
        <livewire:file-uploader :model="$model" wire:key="'uploader-' . $model->id" />
    </div>
</div>
```

#### 1.2 Extract Project Header Component

**Create: `resources/views/components/project/header.blade.php`**
```blade
@props(['project', 'hasPreviewTrack' => false, 'showEditButton' => true])

<div class="shadow-base-300 mb-6 flex flex-col rounded-lg border-transparent shadow-2xl sm:mb-12">
    <div class="shadow-lightGlow shadow-base-300 flex h-full flex-col md:flex-row">
        <!-- Project Image -->
        <div x-data="{ lightbox: { isOpen: false } }" class="project-header-image relative shrink-0 md:w-72">
            @if ($project->image_path)
                <img @click="lightbox.isOpen = true" src="{{ $project->imageUrl }}"
                    class="mx-auto max-h-56 w-full cursor-pointer rounded-lg object-cover shadow-lg transition-all duration-200 hover:shadow-xl md:mx-0 md:max-h-none md:w-auto"
                    alt="{{ $project->name }}">
            @else
                <div class="bg-base-200 flex h-56 w-full items-center justify-center object-cover sm:h-72 md:aspect-square md:w-72 lg:rounded-tl-lg">
                    <i class="fas fa-music text-base-300 text-5xl sm:text-6xl"></i>
                </div>
            @endif
            
            @if ($hasPreviewTrack)
                <div class="absolute -bottom-1 -left-1 right-auto top-auto z-50 flex aspect-auto h-auto w-auto text-sm">
                    @livewire('audio-player', ['audioUrl' => $project->previewTrackPath(), 'isInCard' => true])
                </div>
            @endif

            <!-- Lightbox for image -->
            @if ($project->image_path)
                <div x-cloak x-show="lightbox.isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90 transition-all duration-300">
                    <div class="relative mx-auto max-w-4xl">
                        <img class="max-h-[90vh] max-w-[90vw] rounded object-contain shadow-2xl" src="{{ $project->imageUrl }}" alt="{{ $project->name }}">
                        <button @click="lightbox.isOpen = false" class="absolute right-4 top-4 rounded-full bg-gray-900 bg-opacity-50 p-2 text-white transition-colors duration-200 hover:bg-opacity-75">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endif
        </div>

        <!-- Project Details -->
        <div class="project-header-details flex flex-1 flex-col justify-between overflow-x-auto md:h-72">
            <div class="flex flex-col p-3 sm:p-4 md:p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <a href="{{ route('projects.show', $project) }}"
                        class="hover:text-primary break-words text-center text-xl font-bold text-gray-800 transition-colors sm:text-2xl md:text-left md:text-3xl">
                        {{ $project->name }}
                    </a>
                </div>

                @if ($project->artist_name)
                    <div class="flex items-center justify-center py-1 md:justify-start">
                        <span class="mr-2 font-semibold text-gray-700">Artist:</span>
                        <span class="text-gray-900">{{ $project->artist_name }}</span>
                    </div>
                @endif

                <!-- User info -->
                <div class="mt-2 flex w-full items-center justify-center md:justify-start">
                    <img class="border-base-300 mr-3 h-10 w-10 rounded-full border-2 object-cover"
                        src="{{ $project->user->profile_photo_url }}" alt="{{ $project->user->name }}" />
                    <div class="flex flex-col">
                        <span class="max-w-xs truncate text-base font-medium">{{ $project->user->name }}</span>
                        <span class="text-xs text-gray-600">Project Owner</span>
                    </div>
                </div>
            </div>

            <div class="mt-auto">
                @if($showEditButton)
                <!-- Edit button -->
                <div class="mt-4 flex w-full">
                    <a href="{{ route('projects.edit', $project) }}"
                        class="bg-warning/80 hover:bg-warning shadow-accent hover:shadow-accent-focus block grow whitespace-nowrap px-4 py-2 text-center text-xl font-bold tracking-tight transition-colors">
                        <i class="fas fa-edit mr-2"></i> Edit Project Details
                    </a>
                </div>
                @endif

                <!-- Project metadata -->
                <div class="border-base-200 w-full overflow-x-auto border-b border-t font-sans">
                    <div class="project-metadata-row flex min-w-full flex-row">
                        <div class="bg-base-200/70 border-base-200 flex-1 border-r px-2 py-1 text-center sm:text-right md:px-4">
                            <div class="label-text whitespace-nowrap text-xs text-gray-600 sm:text-sm">Project Type</div>
                            <div class="text-sm font-bold sm:text-base">{{ Str::title($project->project_type) }}</div>
                        </div>
                        <div class="bg-base-200/30 border-base-200 flex-1 border-r px-2 py-1 pb-0 text-center md:px-4">
                            <div class="label-text text-xs text-gray-600 sm:text-sm">Budget</div>
                            <div class="text-sm font-bold sm:text-base">
                                @if (is_numeric($project->budget) && $project->budget > 0)
                                    ${{ number_format((float) $project->budget, 0) }}
                                @elseif(is_numeric($project->budget) && $project->budget == 0)
                                    Free
                                @else
                                    Price TBD
                                @endif
                            </div>
                        </div>
                        <div class="bg-base-200/70 flex-1 px-2 py-1 pb-0 text-center sm:text-left md:px-4">
                            <div class="label-text text-xs text-gray-600 sm:text-sm">Deadline</div>
                            <div class="whitespace-nowrap text-sm font-bold sm:text-base">
                                {{ \Carbon\Carbon::parse($project->deadline)->format('M d, Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

#### 1.3 Extract Workflow Status Component

**Create: `resources/views/components/pitch/workflow-status.blade.php`**
```blade
@props(['pitch', 'project'])

<div class="bg-white rounded-lg border border-base-300 shadow-sm p-4 mb-6">
    <h4 class="text-lg font-semibold mb-3 flex items-center">
        <i class="fas fa-tasks text-blue-500 mr-2"></i>Workflow Status
    </h4>
    
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
            <x-pitch-status-badge :status="$pitch->status" />
            <span class="ml-3 text-sm text-gray-600">
                Last updated: {{ $pitch->updated_at->format('M d, Y \a\t g:i A') }}
            </span>
        </div>
    </div>

    @if($pitch->status === \App\Models\Pitch::STATUS_REVISIONS_REQUESTED && !empty($pitch->latest_feedback))
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
        <h5 class="font-medium text-yellow-800 mb-2">
            <i class="fas fa-exclamation-triangle mr-1"></i>Revision Requested
        </h5>
        <p class="text-sm text-yellow-700">{{ $pitch->latest_feedback }}</p>
    </div>
    @endif

    @if($pitch->status === \App\Models\Pitch::STATUS_DENIED && !empty($pitch->latest_feedback))
    <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
        <h5 class="font-medium text-red-800 mb-2">
            <i class="fas fa-times-circle mr-1"></i>Submission Denied
        </h5>
        <p class="text-sm text-red-700">{{ $pitch->latest_feedback }}</p>
    </div>
    @endif

    <!-- Submit for Review Button -->
    @if(in_array($pitch->status, [
        \App\Models\Pitch::STATUS_IN_PROGRESS, 
        \App\Models\Pitch::STATUS_REVISIONS_REQUESTED, 
        \App\Models\Pitch::STATUS_DENIED
    ]))
    <div class="border-t pt-4">
        <button wire:click="submitForReview" 
                class="btn btn-primary w-full"
                @if($pitch->files->count() === 0) disabled @endif>
            <i class="fas fa-paper-plane mr-2"></i>
            Submit for Client Review
        </button>
        @if($pitch->files->count() === 0)
        <p class="text-xs text-gray-500 mt-2 text-center">
            <i class="fas fa-info-circle mr-1"></i>Upload at least one file before submitting
        </p>
        @endif
    </div>
    @endif
</div>
```

### Phase 2: Create ManageClientProject Component

#### 2.1 Create Livewire Component

**Create: `app/Livewire/Project/ManageClientProject.php`**
```php
<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\Pitch;
use App\Services\FileManagementService;
use App\Services\PitchWorkflowService;
use App\Services\NotificationService;
use App\Exceptions\File\FileDeletionException;
use App\Exceptions\UnauthorizedActionException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class ManageClientProject extends Component
{
    use AuthorizesRequests;

    public Project $project;
    public Pitch $pitch;
    
    // Storage tracking
    public $storageUsedPercentage = 0;
    public $storageLimitMessage = '';
    public $storageRemaining = 0;
    
    // File management
    public $showDeleteModal = false;
    public $fileIdToDelete = null;
    
    // Workflow management
    public $responseToFeedback = '';
    public $statusFeedbackMessage = null;

    protected $listeners = [
        'filesUploaded' => 'refreshData',
        'fileDeleted' => '$refresh',
    ];

    public function mount(Project $project)
    {
        // Verify this is a client management project
        if (!$project->isClientManagement()) {
            abort(404, 'This page is only available for client management projects.');
        }

        // Authorization check
        try {
            $this->authorize('update', $project);
        } catch (\Exception $e) {
            abort(403, 'You are not authorized to manage this project.');
        }

        $this->project = $project;
        
        // Load the associated pitch (should be exactly one for client management)
        $this->pitch = $project->pitches()
            ->where('user_id', $project->user_id)
            ->with(['files', 'events.user'])
            ->firstOrFail();

        $this->updateStorageInfo();
        $this->loadStatusFeedback();
    }

    public function render()
    {
        return view('livewire.project.manage-client-project')
            ->layout('components.layouts.app');
    }

    // ... (Include all the file management methods from ManagePitch)
    // ... (Include workflow methods like submitForReview)
    // ... (Include project-specific methods like resendClientInvite, deleteProject)
}
```

#### 2.2 Create View Template

**Create: `resources/views/livewire/project/manage-client-project.blade.php`**
```blade
<div class="container mx-auto px-2 sm:px-4">
    <!-- Project Header -->
    <x-project.header :project="$project" :hasPreviewTrack="false" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content Area -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Workflow Status -->
            <x-pitch.workflow-status :pitch="$pitch" :project="$project" />

            <!-- File Management -->
            <div class="bg-white rounded-lg border border-base-300 shadow-sm p-4">
                <h4 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-file-upload text-purple-500 mr-2"></i>Project Files
                </h4>

                <!-- Storage Indicator -->
                <x-file-management.storage-indicator 
                    :storageUsedPercentage="$storageUsedPercentage"
                    :storageLimitMessage="$storageLimitMessage"
                    :storageRemaining="$this->formatFileSize($storageRemaining)" />

                <!-- Upload Section -->
                <x-file-management.upload-section 
                    :model="$pitch"
                    title="Upload Files for Client"
                    description="Upload audio, PDFs, or images to share with your client" />

                <!-- File List -->
                <x-file-management.file-list 
                    :files="$pitch->files"
                    :canDelete="in_array($pitch->status, [\App\Models\Pitch::STATUS_IN_PROGRESS, \App\Models\Pitch::STATUS_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_DENIED])"
                    :formatFileSize="fn($size) => $this->formatFileSize($size)" />
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Client Management Details -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-purple-800 mb-3">
                    <i class="fas fa-briefcase mr-2"></i>Client Details
                </h3>
                <div class="space-y-2 text-sm">
                    <div><strong>Client Name:</strong> {{ $project->client_name ?? 'N/A' }}</div>
                    <div><strong>Client Email:</strong> {{ $project->client_email ?? 'N/A' }}</div>
                    @if($project->payment_amount > 0)
                    <div><strong>Payment Amount:</strong> ${{ number_format($project->payment_amount, 2) }}</div>
                    @endif
                </div>
                <button wire:click="resendClientInvite" class="btn btn-sm btn-outline btn-primary mt-3 w-full">
                    <i class="fas fa-paper-plane mr-1"></i> Resend Client Invite
                </button>
            </div>

            <!-- Project Actions -->
            <div class="bg-white rounded-lg border border-base-300 shadow-sm p-4">
                <h3 class="text-lg font-semibold mb-3">
                    <i class="fas fa-cog mr-2"></i>Project Actions
                </h3>
                <div class="space-y-3">
                    <a href="{{ route('projects.edit', $project) }}" 
                       class="btn btn-outline btn-warning w-full">
                        <i class="fas fa-edit mr-2"></i>Edit Project Details
                    </a>
                    <a href="{{ route('projects.show', $project) }}" 
                       class="btn btn-outline btn-info w-full">
                        <i class="fas fa-eye mr-2"></i>View Public Page
                    </a>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-red-800 mb-3">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Danger Zone
                </h3>
                <p class="text-sm text-red-700 mb-3">
                    Permanently delete this project and all associated files. This action cannot be undone.
                </p>
                <button wire:click="confirmDeleteProject" 
                        class="btn btn-error btn-sm w-full">
                    <i class="fas fa-trash-alt mr-2"></i>Delete Project
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modals -->
    @if($showDeleteModal)
        <!-- File Delete Modal -->
    @endif
    
    @if($showProjectDeleteModal)
        <!-- Project Delete Modal -->
    @endif
</div>
```

### Phase 3: Update Routing and Navigation

#### 3.1 Add New Route

**Update: `routes/web.php`**
```php
// Add after existing project routes
Route::get('/manage-client-project/{project}', \App\Livewire\Project\ManageClientProject::class)
    ->name('projects.manage-client')
    ->middleware('auth');
```

#### 3.2 Update Route Logic

**Update: `app/Http/Controllers/ProjectController.php`** (or create middleware)
```php
// Add method to redirect client management projects
public function manage(Project $project)
{
    if ($project->isClientManagement()) {
        return redirect()->route('projects.manage-client', $project);
    }
    
    // Continue with standard ManageProject component
    return app(\App\Livewire\ManageProject::class, ['project' => $project]);
}
```

### Phase 4: Fix Project Deletion

#### 4.1 Update ProjectObserver

**Update: `app/Observers/ProjectObserver.php`**
```php
/**
 * Handle the Project "deleted" event.
 */
public function deleted(Project $project): void
{
    // Delete associated pitches and their files
    $project->pitches()->each(function ($pitch) {
        // Delete pitch files first
        $pitch->files()->each(function ($file) {
            try {
                app(FileManagementService::class)->deletePitchFile($file);
            } catch (\Exception $e) {
                Log::error('Failed to delete pitch file during project deletion', [
                    'file_id' => $file->id,
                    'error' => $e->getMessage()
                ]);
            }
        });
        
        // Delete the pitch
        $pitch->delete();
    });
    
    // Delete project files
    $project->files()->each(function ($file) {
        try {
            app(FileManagementService::class)->deleteProjectFile($file);
        } catch (\Exception $e) {
            Log::error('Failed to delete project file during project deletion', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);
        }
    });
}
```

#### 4.2 Complete ProjectController Destroy Method

**Update: `app/Http/Controllers/ProjectController.php`**
```php
public function destroy(Project $project)
{
    $this->authorize('delete', $project);
    
    try {
        $project->delete(); // Observer will handle cascading deletes
        
        return redirect()->route('dashboard')
            ->with('success', 'Project deleted successfully.');
    } catch (\Exception $e) {
        Log::error('Error deleting project', [
            'project_id' => $project->id,
            'error' => $e->getMessage()
        ]);
        
        return redirect()->back()
            ->with('error', 'An error occurred while deleting the project.');
    }
}
```

### Phase 5: Update ManageProject Component

#### 5.1 Hide Irrelevant Sections for Client Management

**Update: `app/Livewire/ManageProject.php`**
```php
public function mount(Project $project)
{
    // Redirect client management projects to dedicated page
    if ($project->isClientManagement()) {
        return redirect()->route('projects.manage-client', $project);
    }
    
    // Continue with existing logic for other workflow types
    // ...
}
```

### Phase 6: Testing Strategy

#### 6.1 Manual Testing Checklist

#### ✅ Component Functionality Tests
- [ ] **ManageClientProject Component**
  - [ ] Verify component loads correctly for Client Management projects
  - [ ] Test file upload functionality
  - [ ] Test file download functionality  
  - [ ] Test file deletion with confirmation modal
  - [ ] Test storage indicator updates correctly
  - [ ] Test workflow status display and submission
  - [ ] Test client invite resend functionality
  - [ ] Test project deletion with confirmation modal
  - [ ] Test responsive design on mobile/tablet

#### ✅ Routing Tests
- [ ] **Route Redirection**
  - [ ] Verify `/manage-project/{client-management-project}` redirects to `/manage-client-project/{project}`
  - [ ] Verify `/manage-client-project/{project}` loads correctly for Client Management projects
  - [ ] Verify `/manage-client-project/{non-client-project}` returns 404
  - [ ] Test authorization - non-owners cannot access the page

#### ✅ Component Integration Tests
- [ ] **Reusable Components**
  - [ ] Test `<x-project.header>` displays correctly
  - [ ] Test `<x-pitch.workflow-status>` shows correct status and actions
  - [ ] Test `<x-file-management.storage-indicator>` updates dynamically
  - [ ] Test `<x-file-management.file-list>` displays files correctly
  - [ ] Test `<x-file-management.upload-section>` integrates with FileUploader

#### ✅ Data Integrity Tests
- [ ] **Project Deletion**
  - [ ] Create a Client Management project with files
  - [ ] Delete the project and verify all associated data is removed:
    - [ ] Project record deleted
    - [ ] Associated pitch deleted
    - [ ] All pitch files deleted from storage
    - [ ] All project files deleted from storage
    - [ ] No orphaned records remain

#### ✅ User Experience Tests
- [ ] **Navigation Flow**
  - [ ] Test creating a Client Management project
  - [ ] Test navigating to manage page
  - [ ] Test all workflow actions (upload, submit, respond to feedback)
  - [ ] Test error handling and user feedback

### 6.2 Automated Testing

#### Unit Tests
```php
// tests/Unit/ManageClientProjectTest.php
class ManageClientProjectTest extends TestCase
{
    /** @test */
    public function it_redirects_non_client_management_projects()
    {
        // Test that non-Client Management projects return 404
    }
    
    /** @test */
    public function it_loads_correctly_for_client_management_projects()
    {
        // Test component mounts successfully
    }
    
    /** @test */
    public function it_requires_authorization()
    {
        // Test unauthorized users cannot access
    }
}
```

#### Feature Tests
```php
// tests/Feature/ClientManagementWorkflowTest.php
class ClientManagementWorkflowTest extends TestCase
{
    /** @test */
    public function complete_client_management_workflow()
    {
        // Test entire workflow from creation to completion
    }
    
    /** @test */
    public function project_deletion_cascades_correctly()
    {
        // Test ProjectObserver deletion logic
    }
}
```

#### Browser Tests
```php
// tests/Browser/ClientManagementTest.php
class ClientManagementTest extends DuskTestCase
{
    /** @test */
    public function user_can_manage_client_project()
    {
        // Test UI interactions
    }
    
    /** @test */
    public function file_upload_works_correctly()
    {
        // Test file upload UI
    }
}
```

### 6.3 Performance Testing

#### Load Testing
- [ ] Test component performance with large file lists
- [ ] Test storage calculation performance
- [ ] Verify caching works correctly

#### Memory Testing
- [ ] Monitor memory usage during file operations
- [ ] Test with maximum file sizes (200MB)
- [ ] Verify no memory leaks in long sessions

### 6.4 Security Testing

#### Authorization Tests
- [ ] Verify only project owners can access management page
- [ ] Test file access permissions
- [ ] Verify signed URL generation for client invites

#### Input Validation
- [ ] Test file upload validation (size, type)
- [ ] Test form input sanitization
- [ ] Verify CSRF protection

### 6.5 Compatibility Testing

#### Browser Testing
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile browsers (iOS Safari, Chrome Mobile)

#### Device Testing
- [ ] Desktop (1920x1080, 1366x768)
- [ ] Tablet (768x1024)
- [ ] Mobile (375x667, 414x896)

### 6.6 Regression Testing

#### Existing Functionality
- [ ] Verify Standard projects still work correctly
- [ ] Verify Contest projects still work correctly
- [ ] Verify Direct Hire projects still work correctly
- [ ] Test that existing ManageProject component works for non-Client Management projects

### 6.7 Error Handling Tests

#### Edge Cases
- [ ] Test with corrupted files
- [ ] Test with network interruptions during upload
- [ ] Test with storage limit exceeded
- [ ] Test with invalid project states

#### Error Recovery
- [ ] Test graceful degradation when services are unavailable
- [ ] Verify user-friendly error messages
- [ ] Test retry mechanisms

### 6.8 Documentation Testing

#### Code Documentation
- [ ] Verify all methods have proper docblocks
- [ ] Check component prop documentation
- [ ] Validate inline comments

#### User Documentation
- [ ] Test that UI is self-explanatory
- [ ] Verify help text and tooltips
- [ ] Check error message clarity

## Benefits of This Approach

1. **Separation of Concerns**: Client management workflow is completely isolated
2. **Reusable Components**: File management and project header components can be reused
3. **Tailored UX**: Interface is specifically designed for client management needs
4. **Maintainable**: Clear separation makes future changes easier
5. **DRY Principle**: Common functionality is extracted into reusable components

## Migration Strategy

1. **Phase 1**: Create reusable components (can be done incrementally)
2. **Phase 2**: Create ManageClientProject component (parallel development)
3. **Phase 3**: Update routing (minimal disruption)
4. **Phase 4**: Fix deletion logic (critical for data integrity)
5. **Phase 5**: Update existing components (cleanup)
6. **Phase 6**: Comprehensive testing

## Estimated Timeline

- **Phase 1**: 2-3 days (component extraction)
- **Phase 2**: 3-4 days (main component development)
- **Phase 3**: 1 day (routing updates)
- **Phase 4**: 1-2 days (deletion fixes)
- **Phase 5**: 1 day (cleanup)
- **Phase 6**: 2-3 days (testing)

**Total**: 10-14 days

## Risk Mitigation

1. **Data Loss**: Implement proper deletion cascading before deployment
2. **Routing Conflicts**: Test all existing routes after changes
3. **Component Dependencies**: Ensure reusable components are truly independent
4. **User Confusion**: Provide clear navigation and feedback
5. **Performance**: Monitor file upload/management performance with new structure 