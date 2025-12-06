# Impact Analysis: Directory Support

This document details all files that need modification to add folder/directory support, organized by impact level.

---

## CRITICAL Impact

These files MUST be modified for basic folder functionality to work.

### app/Services/FileManagementService.php
**Current Behavior**: Creates files flat, generates S3 keys without folder context
**Required Changes**:
- Add `folder_id` parameter to `createProjectFileFromS3()`
- Add `folder_id` parameter to `createPitchFileFromS3()`
- Add `handleFolderUpload()` method for folder structure creation
- Validate folder belongs to same project/pitch

**Key Methods to Modify**:
- `createProjectFileFromS3()` (~line varies)
- `createPitchFileFromS3()` (~line varies)
- `generateS3Key()` - NO CHANGE needed (S3 stays flat)

---

### app/Models/Project.php
**Current Behavior**: `hasMany(ProjectFile)` returns all files
**Required Changes**:
```php
// ADD these methods:
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

---

### app/Models/Pitch.php
**Current Behavior**: `hasMany(PitchFile)` returns all files
**Required Changes**: Same as Project.php

---

### app/Models/PitchSnapshot.php
**Current Behavior**: Stores `file_ids` array in `snapshot_data`
**Location**: `getFilesAttribute()` method (~line 67-98)
**Required Changes**:
- Capture `folder_id` per file in snapshot_data
- Update `getFilesAttribute()` to restore folder context
- Backwards compatible with existing snapshots (no folder_id)

**Recommended snapshot_data structure (backwards compatible):**
```php
'snapshot_data' => [
    // KEEP existing key for backwards compatibility
    'file_ids' => [1, 2, 3],

    // NEW: Per-file metadata including folder
    'file_metadata' => [
        1 => ['folder_id' => null],
        2 => ['folder_id' => 5],
        3 => ['folder_id' => 5],
    ],

    // NEW: Folder structure snapshot
    'folders' => [
        ['id' => 5, 'name' => 'Stems', 'parent_id' => null, 'path' => '/5/', 'depth' => 1],
    ],

    'version' => 1,
    'response_to_feedback' => '...',
]
```

**Key Points:**
- Existing snapshots with only `file_ids` continue to work
- New snapshots capture folder context for future restoration
- Folder structure is preserved at snapshot time (folders may change later)
- See [TECHNICAL_DETAILS.md](./TECHNICAL_DETAILS.md#snapshot-data-structure) for implementation

---

### app/Models/ProjectFile.php
**Current Behavior**: No folder awareness
**Required Changes**:
```php
// ADD to $fillable:
'folder_id',

// ADD relationship:
public function folder(): BelongsTo
{
    return $this->belongsTo(Folder::class);
}

// ADD helper methods:
public function isInFolder(): bool
{
    return $this->folder_id !== null;
}

public function getFolderPath(): ?string
{
    return $this->folder?->getFullPath();
}
```

---

### app/Models/PitchFile.php
**Current Behavior**: `parent_file_id` used for versioning only
**Required Changes**: Same as ProjectFile.php

> **Complexity Note:** PitchFile is a complex model (943 lines, 35 columns) with:
> - File versioning (`parent_file_id`, `file_version_number`)
> - Working version management (`included_in_working_version`)
> - Revision tracking (`revision_round`, `superseded_by_revision`)
> - Audio processing states
> - Watermarking logic
>
> Adding `folder_id` must integrate carefully without disrupting these systems.

**IMPORTANT**: The existing `parent()` relationship is for FILE VERSIONING, not folders. Keep these completely separate:
- `parent_file_id` / `parent()` = File version parent
- `folder_id` / `folder()` = Folder organization (NEW)

**Working Version + Folders Interaction:**
- When a file is excluded from working version, it KEEPS its folder assignment
- Folder structure is organizational; working version is workflow state
- These are orthogonal concerns and should not affect each other

---

### app/Livewire/Components/FileList.php
**Current Behavior**: Displays flat file collection
**Required Changes**: MAJOR - See [FILELIST_EXPANSION.md](./FILELIST_EXPANSION.md)

Summary:
- Add folder navigation properties
- Add folder CRUD methods
- Add move operations
- Update file loading to filter by folder
- Add breadcrumb support

---

## HIGH Impact

These files are important for complete functionality.

### app/Http/Controllers/FileDownloadController.php
**Current Behavior**: No folder context in authorization
**Required Changes**: Verify folder ownership in download authorization (if folder-level permissions needed)

---

### app/Policies/ProjectFilePolicy.php
**Current Behavior**: Checks project ownership only
**Required Changes**: May need folder-level permission checks if implementing folder-specific access

---

### app/Policies/PitchFilePolicy.php
**Current Behavior**: Checks pitch ownership only
**Required Changes**: Same as ProjectFilePolicy

---

### app/Services/BulkDownloadService.php
**Current Behavior**: ZIP files have flat structure; uses Cloudflare Queue Worker for ZIP creation
**Required Changes**:
- Preserve folder hierarchy in generated ZIP
- Include folder paths in the message sent to Cloudflare Queue

```php
// Current message format:
'files' => $files->map(fn ($file) => [
    'storage_path' => $file->storage_path ?? $file->file_path,
    'filename' => $file->original_file_name,
    'size' => $file->size,
])->toArray(),

// Updated message format (add zip_path):
'files' => $files->map(fn ($file) => [
    'storage_path' => $file->storage_path ?? $file->file_path,
    'filename' => $file->original_file_name,
    'zip_path' => $file->getFolderPath()
        ? $file->getFolderPath() . '/' . $file->original_file_name
        : $file->original_file_name,
    'size' => $file->size,
])->toArray(),
```

> **Important**: The Cloudflare Worker that processes bulk downloads also needs to be updated to use the `zip_path` field when adding files to the archive. This is external to this repository.

---

### app/Livewire/ManageProject.php
**Current Behavior**: Loads `$project->files` flat
**Required Changes**:
- Pass `currentFolderId` to FileList
- Listen for `folderChanged` event
- Update file loading for folder context

---

### app/Livewire/Project/ManageClientProject.php
**Current Behavior**: Complex file queries (working version, excluded files)
**Required Changes**:
- Update all `files()->` queries to handle folder context
- Pass folder state to FileList
- Handle folder in snapshot navigation

---

### app/Livewire/Pitch/Component/ManagePitch.php
**Current Behavior**: File list without folder context
**Required Changes**:
- Pass folder context to FileList
- Update file operations for folder awareness

---

### app/Http/Controllers/PitchFileController.php
**Current Behavior**: Upload without folder selection
**Required Changes**:
- Accept `folder_id` in upload requests
- Pass to FileManagementService

---

### app/Livewire/ClientPortal/ProducerDeliverables.php
**Current Behavior**: Flat file display
**Required Changes**:
- Show folder structure
- Default to expanded view (all folders open)
- Read-only folder browsing

---

### app/Services/PitchWorkflowService.php
**Current Behavior**: `'file_ids' => $pitch->files()->pluck('id')`
**Location**: Snapshot creation (~line 574, 698, 738, etc.)
**Required Changes**:
- Capture folder_id along with file_id in snapshot creation
- Update snapshot restoration to respect folder structure

---

## MEDIUM Impact

Should handle for complete implementation.

| File | Changes |
|------|---------|
| `app/Livewire/UppyFileUploader.php` | Add folder upload support, webkitdirectory |
| `app/Livewire/FileUploader.php` | Add current folder context |
| `app/Livewire/BulkVersionUploadModal.php` | Update file matching for folder context |
| `app/Livewire/Project/Component/ClientSubmitSection.php` | Folder filtering in queries |
| `app/Livewire/ProjectSetupChecklist.php` | File count logic that may need folder awareness |
| `app/Observers/ProjectObserver.php` | Cascade delete to handle folders |
| Audio players (PitchFilePlayer, etc.) | URL generation - likely no changes needed |

### Investigate: order_files Table
The database has an `order_files` table. Investigate if this needs folder support:
- If it's related to file management, may need `folder_id`
- If it's for order/purchase tracking only, likely no changes needed
- Check `app/Models/OrderFile.php` if it exists

---

## LOW Impact

For completeness, not critical for basic functionality.

### Test Files (30+)
All tests that create ProjectFile or PitchFile need to include `folder_id`:
- `tests/Feature/FileManagementTest.php`
- `tests/Feature/FileVersioningTest.php`
- `tests/Feature/ClientPortalFileAccessTest.php`
- And ~27 more...

### Factories
- `database/factories/ProjectFileFactory.php` - Add `'folder_id' => null`
- `database/factories/PitchFileFactory.php` - Add `'folder_id' => null`

### Filament Admin
- `app/Filament/Resources/ProjectFileResource.php` (if exists) - Add folder column/filter

### Blade Views
Various views that display files may need updates for folder structure display.

---

## Version Files and Folders

### Critical Rule

**File versions do NOT have independent folder assignments.** They inherit from their root file.

### Why This Matters

PitchFile uses `parent_file_id` for versioning:
- Root file: `parent_file_id = null`, `folder_id = 5` (in "Stems" folder)
- Version 2: `parent_file_id = root_id`, `folder_id = null` (no folder)
- Version 3: `parent_file_id = root_id`, `folder_id = null` (no folder)

When displaying a version, its folder is determined by:
```php
$version->getRootFile()->folder_id
```

### Implementation Rules

1. **Never set folder_id on version files** - always leave as null
2. **When moving a root file**, versions automatically "move" (they follow root)
3. **When querying folder contents**, exclude versions:
   ```php
   $pitch->files()
       ->whereNull('parent_file_id')  // Root files only
       ->inFolder($folderId)
       ->get();
   ```

---

## Query Pattern Changes

### Common Patterns to Update

**Get all files (unchanged)**:
```php
$project->files()->get();
$project->files()->count();
```

**Get root-level files only (NEW) - using scope**:
```php
$project->files()->inFolder(null)->get();
// OR
$project->rootFiles()->get();
// OR
$project->files()->whereNull('folder_id')->get();
```

**Get files in specific folder (NEW) - using scope**:
```php
$project->files()->inFolder($folderId)->get();
// OR
$project->files()->where('folder_id', $folderId)->get();
```

**Get all files in folder tree recursively (NEW)**:
```php
$project->files()->inFolderRecursive($folder)->get();
```

**Get folder contents (NEW)**:
```php
// Folders at a level
$project->folders()->where('parent_id', $folderId)->get();
// OR for root folders
$project->folders()->roots()->get();

// Files at a level
$project->files()->inFolder($folderId)->get();
```

**Combined: Get all items in a folder**:
```php
$folderService->getContents($project, $folder);
// Returns: ['folders' => Collection, 'files' => Collection]
```

### PitchFile-Specific Patterns

**Get root files in folder (exclude versions)**:
```php
$pitch->files()
    ->whereNull('parent_file_id')
    ->inFolder($folderId)
    ->get();
```

**Get working version files in folder**:
```php
$pitch->files()
    ->whereNull('parent_file_id')
    ->where('included_in_working_version', true)
    ->inFolder($folderId)
    ->get();
```

---

## Backwards Compatibility Notes

1. **Existing files** will have `folder_id = null` - treated as root directory
2. **Existing snapshots** won't have folder data - must handle gracefully
3. **All existing functionality** must continue to work unchanged
4. **folder_id = null** is a valid state meaning "root of project/pitch"
5. **File versions** always have `folder_id = null` - they inherit from root file
