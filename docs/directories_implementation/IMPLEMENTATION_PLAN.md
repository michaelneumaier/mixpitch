# Directory Support Implementation Plan

## Requirements

| Requirement | Detail |
|-------------|--------|
| Scope | Both ProjectFiles and PitchFiles |
| Depth | Up to 10 levels of nesting |
| Operations | Full CRUD + move files/folders + folder upload with structure preservation |
| Client Portal | Full browsing, default expanded to show all files |

---

## Phase 0: FileList Component Refactoring

### Objective
Split the monolithic FileList component (~2400 lines total) into 4 smaller, maintainable components BEFORE adding folder functionality. This creates a clean foundation for folder support.

### Current State Analysis
| Area | PHP Lines | Blade Lines | Total |
|------|-----------|-------------|-------|
| Core/Properties | ~190 | ~60 | ~250 |
| Single File Actions | ~175 | ~185 | ~360 |
| Bulk Actions | ~210 | ~170 | ~380 |
| Event Handlers | ~90 | - | ~90 |
| Comments | ~290 | ~320 | ~610 |
| Version Management | ~200 | ~140 | ~340 |
| Modals | - | ~230 | ~230 |
| **Total** | **~1300** | **~1140** | **~2440** |

### Target Component Architecture

```
┌─────────────────────────────────────────────────────────────┐
│ FileList (Main Coordinator) - ~500 lines                    │
│ - Properties, mount(), color scheme                         │
│ - File collection management                                │
│ - Event handlers (refresh, uploads)                         │
│ - Coordinates child components                              │
├─────────────────────────────────────────────────────────────┤
│ FileBulkActions - ~380 lines                                │
│ - Selection state & UI                                      │
│ - Bulk action buttons (download, delete, move)              │
│ - Confirmation modals                                       │
├─────────────────────────────────────────────────────────────┤
│ FileItem - ~360 lines (per file)                            │
│ - File icon, name, metadata, badges                         │
│ - Selection checkbox                                        │
│ - Actions dropdown (play, download, delete, versions)       │
│ - Version dropdown integration                              │
├─────────────────────────────────────────────────────────────┤
│ FileComments - ~600 lines (per file)                        │
│ - Comment list with replies                                 │
│ - Add/respond/resolve/delete comments                       │
│ - Comment-related modals                                    │
└─────────────────────────────────────────────────────────────┘
```

### Tasks

#### 0.1 Create FileItem Component
Extract individual file row rendering into its own component:
```php
// app/Livewire/Components/FileItem.php
class FileItem extends Component
{
    public $file;
    public bool $canPlay = true;
    public bool $canDownload = true;
    public bool $canDelete = true;
    public bool $isSelected = false;
    public bool $enableSelection = false;
    public bool $enableVersionSwitching = false;
    public string $modelType = 'project';
    public ?int $modelId = null;
    public array $colorScheme = [];
    public bool $isClientPortal = false;
    public ?int $currentSnapshotId = null;

    // Methods: playFile, downloadFile, confirmDeleteFile, etc.
    // Dispatches events to parent FileList
}
```

**Files:**
- `app/Livewire/Components/FileItem.php` (NEW)
- `resources/views/livewire/components/file-item.blade.php` (NEW)

#### 0.2 Create FileComments Component
Extract the entire comments system:
```php
// app/Livewire/Components/FileComments.php
class FileComments extends Component
{
    public $file;
    public ?Collection $commentsData = null;
    public bool $enableCommentCreation = false;
    public string $modelType = 'project';
    public ?int $modelId = null;
    public bool $isClientPortal = false;

    // Properties for comment state
    public array $fileCommentResponse = [];
    public array $newFileComment = [];
    public ?int $commentToDelete = null;
    public ?int $commentToUnresolve = null;

    // Methods: getFileComments, createFileComment, markFileCommentResolved, etc.
}
```

**Files:**
- `app/Livewire/Components/FileComments.php` (NEW)
- `resources/views/livewire/components/file-comments.blade.php` (NEW)

#### 0.3 Create FileBulkActions Component
Extract bulk selection and actions:
```php
// app/Livewire/Components/FileBulkActions.php
class FileBulkActions extends Component
{
    public array $selectedFileIds = [];
    public Collection $files;
    public array $bulkActions = ['delete', 'download'];
    public bool $canDelete = true;
    public bool $canDownload = true;
    public string $modelType = 'project';
    public ?int $modelId = null;
    public array $colorScheme = [];

    // Methods: selectAllFiles, clearSelection, bulkDeleteSelected, etc.
}
```

**Files:**
- `app/Livewire/Components/FileBulkActions.php` (NEW)
- `resources/views/livewire/components/file-bulk-actions.blade.php` (NEW)

#### 0.4 Refactor FileList as Coordinator
Slim down FileList to coordinate the child components:
```php
// app/Livewire/Components/FileList.php (REFACTORED)
class FileList extends Component
{
    // Core properties
    public Collection $files;
    public string $modelType = 'project';
    public ?int $modelId = null;
    public array|string $colorScheme = [];

    // Feature flags passed to children
    public bool $canPlay = true;
    public bool $canDownload = true;
    public bool $canDelete = true;
    public bool $enableBulkActions = false;
    public bool $showComments = false;
    public bool $enableVersionSwitching = false;

    // Selection state (shared with FileBulkActions)
    public array $selectedFileIds = [];

    // Event handlers for child component communication
    #[On('fileAction')]
    public function handleFileAction($data) { ... }

    #[On('bulkFileAction')]
    public function handleBulkFileAction($data) { ... }

    #[On('commentAction')]
    public function handleCommentAction($data) { ... }
}
```

#### 0.5 Update Blade View Structure
```blade
{{-- resources/views/livewire/components/file-list.blade.php --}}
<div>
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-2">
        ...
    </div>

    {{-- Bulk Actions Toolbar --}}
    @if($enableBulkActions)
        <livewire:components.file-bulk-actions
            :files="$files"
            :selectedFileIds="$selectedFileIds"
            :bulkActions="$bulkActions"
            :canDelete="$canDelete"
            :canDownload="$canDownload"
            :colorScheme="$this->resolvedColorScheme"
            :key="'bulk-actions-' . $modelType . '-' . $modelId" />
    @endif

    {{-- Files List --}}
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($files as $file)
            <livewire:components.file-item
                :file="$file"
                :canPlay="$canPlay"
                :canDownload="$canDownload"
                :canDelete="$canDelete"
                :isSelected="in_array($file->id, $selectedFileIds)"
                :enableSelection="$enableBulkActions"
                :enableVersionSwitching="$enableVersionSwitching"
                :modelType="$modelType"
                :modelId="$modelId"
                :colorScheme="$this->resolvedColorScheme"
                :isClientPortal="$isClientPortal"
                :key="'file-' . $file->id" />

            @if($showComments)
                <livewire:components.file-comments
                    :file="$file"
                    :commentsData="$commentsData"
                    :enableCommentCreation="$enableCommentCreation"
                    :modelType="$modelType"
                    :modelId="$modelId"
                    :isClientPortal="$isClientPortal"
                    :key="'comments-' . $file->id" />
            @endif
        @empty
            {{-- Empty State --}}
        @endforelse
    </div>
</div>
```

#### 0.6 Write Component Tests
- `tests/Feature/Livewire/FileItemTest.php` (NEW)
- `tests/Feature/Livewire/FileCommentsTest.php` (NEW)
- `tests/Feature/Livewire/FileBulkActionsTest.php` (NEW)
- Update existing `tests/Feature/Livewire/FileListTest.php`

#### 0.7 Ensure Backwards Compatibility
- All existing FileList usages must continue to work
- Same mount() parameters
- Same event dispatching behavior
- Run full test suite to verify

### Files Created
- `app/Livewire/Components/FileItem.php`
- `app/Livewire/Components/FileComments.php`
- `app/Livewire/Components/FileBulkActions.php`
- `resources/views/livewire/components/file-item.blade.php`
- `resources/views/livewire/components/file-comments.blade.php`
- `resources/views/livewire/components/file-bulk-actions.blade.php`
- `tests/Feature/Livewire/FileItemTest.php`
- `tests/Feature/Livewire/FileCommentsTest.php`
- `tests/Feature/Livewire/FileBulkActionsTest.php`

### Files Modified
- `app/Livewire/Components/FileList.php` (MAJOR refactor)
- `resources/views/livewire/components/file-list.blade.php` (MAJOR refactor)

### Success Criteria
- [ ] All existing FileList functionality works unchanged
- [ ] Full test suite passes
- [ ] Each new component has >80% test coverage
- [ ] No regression in ManageProject, ManagePitch, ManageClientProject, or Client Portal
- [ ] Component sizes are manageable (<600 lines each)

---

## Phase 1: Database & Model Foundation

### Objective
Create the database schema and Eloquent models for folder support.

### Tasks

#### 1.1 Create Folders Migration
```bash
php artisan make:migration create_folders_table
```

```php
Schema::create('folders', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->foreignId('parent_id')->nullable()->constrained('folders')->cascadeOnDelete();
    $table->morphs('folderable'); // folderable_type, folderable_id
    $table->string('path', 500); // Materialized path: "/1/2/3/"
    $table->unsignedTinyInteger('depth')->default(1);
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['folderable_type', 'folderable_id', 'parent_id']);
    $table->index('path');
    $table->unique(['folderable_type', 'folderable_id', 'parent_id', 'name']);
});
```

#### 1.2 Add folder_id to project_files
```bash
php artisan make:migration add_folder_id_to_project_files_table
```

```php
Schema::table('project_files', function (Blueprint $table) {
    $table->foreignId('folder_id')
        ->nullable()
        ->after('project_id')
        ->constrained('folders')
        ->nullOnDelete();
});
```

#### 1.3 Add folder_id to pitch_files
```bash
php artisan make:migration add_folder_id_to_pitch_files_table
```

```php
Schema::table('pitch_files', function (Blueprint $table) {
    $table->foreignId('folder_id')
        ->nullable()
        ->after('pitch_id')
        ->constrained('folders')
        ->nullOnDelete();
});
```

#### 1.4 Create Folder Model
File: `app/Models/Folder.php`

See [TECHNICAL_DETAILS.md](./TECHNICAL_DETAILS.md#folder-model) for complete code.

#### 1.5 Update ProjectFile Model
Add to `app/Models/ProjectFile.php`:
```php
public function folder(): BelongsTo
{
    return $this->belongsTo(Folder::class);
}

public function isInFolder(): bool
{
    return $this->folder_id !== null;
}

public function getFolderPath(): ?string
{
    return $this->folder?->getFullPath();
}
```

#### 1.6 Update PitchFile Model
Add to `app/Models/PitchFile.php`:
```php
public function folder(): BelongsTo
{
    return $this->belongsTo(Folder::class);
}

public function isInFolder(): bool
{
    return $this->folder_id !== null;
}

public function getFolderPath(): ?string
{
    return $this->folder?->getFullPath();
}
```

**Note**: This is SEPARATE from existing `parent()` relationship which is for file versioning.

#### 1.7 Update Project Model
Add to `app/Models/Project.php`:
```php
public function folders(): MorphMany
{
    return $this->morphMany(Folder::class, 'folderable');
}

public function rootFolders(): MorphMany
{
    return $this->folders()->whereNull('parent_id')->orderBy('name');
}

public function rootFiles(): HasMany
{
    return $this->files()->whereNull('folder_id');
}
```

#### 1.8 Update Pitch Model
Add same methods as Project to `app/Models/Pitch.php`.

#### 1.9 Create FolderService
File: `app/Services/FolderService.php`

See [TECHNICAL_DETAILS.md](./TECHNICAL_DETAILS.md#folder-service) for complete code.

#### 1.10 Create FolderPolicy
File: `app/Policies/FolderPolicy.php`

See [TECHNICAL_DETAILS.md](./TECHNICAL_DETAILS.md#folder-policy) for complete code.

#### 1.11 Create FolderFactory
File: `database/factories/FolderFactory.php`

#### 1.12 Write Unit Tests
- `tests/Unit/Models/FolderTest.php`
- `tests/Feature/FolderServiceTest.php`

### Files Modified/Created
- `database/migrations/XXXX_create_folders_table.php` (NEW)
- `database/migrations/XXXX_add_folder_id_to_project_files.php` (NEW)
- `database/migrations/XXXX_add_folder_id_to_pitch_files.php` (NEW)
- `app/Models/Folder.php` (NEW)
- `app/Models/ProjectFile.php` (UPDATE)
- `app/Models/PitchFile.php` (UPDATE)
- `app/Models/Project.php` (UPDATE)
- `app/Models/Pitch.php` (UPDATE)
- `app/Services/FolderService.php` (NEW)
- `app/Policies/FolderPolicy.php` (NEW)
- `database/factories/FolderFactory.php` (NEW)
- `tests/Unit/Models/FolderTest.php` (NEW)
- `tests/Feature/FolderServiceTest.php` (NEW)

---

## Phase 2: FileManagementService Integration

### Objective
Update the core file management service to be folder-aware.

### Tasks

#### 2.1 Update createProjectFileFromS3()
Add optional `$folderId` parameter:
```php
public function createProjectFileFromS3(
    Project $project,
    string $s3Key,
    string $fileName,
    int $fileSize,
    string $mimeType,
    ?Folder $folder = null,  // NEW
    ?User $uploader = null,
    array $metadata = []
): ProjectFile {
    // Validate folder belongs to project
    if ($folder && ($folder->folderable_type !== Project::class ||
                    $folder->folderable_id !== $project->id)) {
        throw new FileUploadException("Folder does not belong to this project.");
    }

    $projectFile = ProjectFile::create([
        // ... existing fields ...
        'folder_id' => $folder?->id,  // NEW
    ]);

    return $projectFile;
}
```

#### 2.2 Update createPitchFileFromS3()
Same pattern as above.

#### 2.3 Add handleFolderUpload() method
```php
public function handleFolderUpload(
    Model $parent,
    array $filesWithPaths,
    ?User $uploader = null
): array {
    $folderService = app(FolderService::class);
    $relativePaths = array_column($filesWithPaths, 'relativePath');
    $folderMap = $folderService->createFolderStructure($parent, $relativePaths, $uploader);

    $createdFiles = [];
    foreach ($filesWithPaths as $fileData) {
        $folder = $this->getFolderForPath($folderMap, $fileData['relativePath']);
        // Create file with folder assignment
        $createdFiles[] = $this->createFileWithFolder(...);
    }

    return $createdFiles;
}
```

#### 2.4 Update BulkDownloadService
Preserve folder hierarchy in ZIP files:
```php
// Instead of flat:
// file1.mp3, file2.mp3

// Generate paths:
// Stems/Drums/kick.mp3
// Stems/Drums/snare.mp3
// Vocals/lead.mp3
```

### Files Modified
- `app/Services/FileManagementService.php`
- `app/Services/BulkDownloadService.php`
- `tests/Feature/FileManagementServiceFolderTest.php` (NEW)

---

## Phase 3: Expand FileList Component

### Objective
Add folder navigation and management to the existing FileList component.

### Tasks

#### 3.1 Add New Properties to FileList.php
```php
// Folder navigation
public ?int $currentFolderId = null;
public bool $showFolderNavigation = true;
public bool $enableFolderOperations = false;
public Collection $folders;
public array $breadcrumbs = [];
public bool $expandAllFolders = false;

// Folder CRUD state
public bool $showCreateFolderModal = false;
public bool $showRenameFolderModal = false;
public string $newFolderName = '';
public ?int $folderToRename = null;
public ?int $folderToDelete = null;

// Move operations
public bool $showMoveModal = false;
public array $itemsToMove = [];
```

#### 3.2 Add New Methods to FileList.php
See [FILELIST_EXPANSION.md](./FILELIST_EXPANSION.md) for complete implementation.

#### 3.3 Update mount() Method
```php
public function mount(
    // ... existing params ...
    ?int $currentFolderId = null,
    bool $showFolderNavigation = true,
    bool $enableFolderOperations = false,
    bool $expandAllFolders = false
) {
    // ... existing code ...
    $this->currentFolderId = $currentFolderId;
    $this->showFolderNavigation = $showFolderNavigation;
    $this->enableFolderOperations = $enableFolderOperations;
    $this->expandAllFolders = $expandAllFolders;
    $this->loadFolderContents();
    $this->loadBreadcrumbs();
}
```

#### 3.4 Update Blade View
See [FILELIST_EXPANSION.md](./FILELIST_EXPANSION.md) for Blade changes.

### Files Modified
- `app/Livewire/Components/FileList.php` (MAJOR)
- `resources/views/livewire/components/file-list.blade.php` (MAJOR)
- `tests/Feature/Livewire/FileListFolderTest.php` (NEW)

---

## Phase 4: Parent Component Integration

### Objective
Update all parent components that use FileList to support folder context.

### Tasks

#### 4.1 Update ManageProject.php
- Pass `currentFolderId` to FileList
- Listen for `folderChanged` event
- Update file loading to respect folder context

#### 4.2 Update ManageClientProject.php
- Update complex file queries for folder support
- Handle folder context in working version logic

#### 4.3 Update ManagePitch.php
- Pass folder context to FileList
- Update file operations

#### 4.4 Update PitchSnapshot.php
Capture folder_id in snapshot_data:
```php
// Before:
'file_ids' => [1, 2, 3]

// After:
'files' => [
    ['id' => 1, 'folder_id' => null],
    ['id' => 2, 'folder_id' => 5],
    ['id' => 3, 'folder_id' => 5],
]
```

#### 4.5 Update PitchWorkflowService.php
Include folder_id when creating snapshots.

### Files Modified
- `app/Livewire/ManageProject.php`
- `app/Livewire/Project/ManageClientProject.php`
- `app/Livewire/Pitch/Component/ManagePitch.php`
- `app/Models/PitchSnapshot.php`
- `app/Services/PitchWorkflowService.php`

---

## Phase 5: Folder Upload Support

### Objective
Allow users to upload entire folders, preserving structure.

### Tasks

#### 5.1 Update Uppy Configuration
Add webkitdirectory support to `resources/js/uppy-config.js`:
```javascript
// Add folder upload button
const folderInput = document.createElement('input');
folderInput.type = 'file';
folderInput.webkitdirectory = true;
folderInput.directory = true;

// Capture relative paths
folderInput.addEventListener('change', (e) => {
    Array.from(e.target.files).forEach(file => {
        uppy.addFile({
            name: file.name,
            type: file.type,
            data: file,
            meta: {
                relativePath: file.webkitRelativePath
            }
        });
    });
});
```

#### 5.2 Update UppyFileUploader.php
Add `handleFolderUploadSuccess()` method:
```php
public function handleFolderUploadSuccess($uploadData)
{
    $fileManagementService = app(FileManagementService::class);
    $files = $fileManagementService->handleFolderUpload(
        $this->model,
        $uploadData,
        auth()->user()
    );
    // ... handle success ...
}
```

#### 5.3 Cross-Browser Testing
Test webkitdirectory in:
- Chrome
- Firefox
- Edge
- Safari

### Files Modified
- `resources/js/uppy-config.js`
- `app/Livewire/UppyFileUploader.php`
- `resources/views/livewire/uppy-file-uploader.blade.php`
- `app/Http/Controllers/CustomUppyS3MultipartController.php`

---

## Phase 6: Client Portal Integration

### Objective
Enable folder browsing in the client portal with expanded default view.

### Tasks

#### 6.1 Update ProducerDeliverables.php
- Add folder display logic
- Default to expanded view
- Read-only folder browsing

#### 6.2 Update Client Portal Views
- Show folder structure in file listings
- Add breadcrumb navigation
- Expand all folders by default

#### 6.3 Handle Signed URL Routes
Ensure folder context works with signed URLs for unauthenticated access.

### Files Modified
- `app/Livewire/ClientPortal/ProducerDeliverables.php`
- `app/Livewire/ClientPortal/FileManager.php`
- `resources/views/client_portal/components/*.blade.php`

---

## Phase 7: Polish, Testing & Edge Cases

### Objective
Ensure complete test coverage and handle edge cases.

### Tasks

#### 7.1 Update All File-Related Tests
Add `folder_id` to file creation in 30+ test files.

#### 7.2 Update Factories
```php
// ProjectFileFactory
'folder_id' => null,

// PitchFileFactory
'folder_id' => null,
```

#### 7.3 Performance Testing
Test with:
- 10 levels of nested folders
- 100+ files per folder
- Complex folder moves

#### 7.4 Mobile/Responsive Testing
- Folder navigation on mobile
- Touch interactions for folder operations

#### 7.5 Filament Admin
Update admin resources if they display files.

### Files Modified
- 30+ test files
- `database/factories/ProjectFileFactory.php`
- `database/factories/PitchFileFactory.php`
- `app/Filament/Resources/ProjectFileResource.php` (if exists)
