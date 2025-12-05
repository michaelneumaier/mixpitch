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

**Before**:
```php
'file_ids' => [1, 2, 3]
```

**After**:
```php
'files' => [
    ['id' => 1, 'folder_id' => null],
    ['id' => 2, 'folder_id' => 5],
]
// OR maintain backwards compatibility:
'file_ids' => [1, 2, 3],  // Legacy
'file_folder_map' => [1 => null, 2 => 5, 3 => 5]  // New
```

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

**IMPORTANT**: The existing `parent()` relationship is for FILE VERSIONING, not folders. Keep these completely separate:
- `parent_file_id` / `parent()` = File version parent
- `folder_id` / `folder()` = Folder organization (NEW)

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
**Current Behavior**: ZIP files have flat structure
**Required Changes**:
- Preserve folder hierarchy in generated ZIP
- Include folder paths when adding files to archive

```php
// Before:
$zip->addFile($path, $file->file_name);

// After:
$folderPath = $file->getFolderPath();
$zipPath = $folderPath ? $folderPath . '/' . $file->file_name : $file->file_name;
$zip->addFile($path, $zipPath);
```

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

## Query Pattern Changes

### Common Patterns to Update

**Get all files (unchanged)**:
```php
$project->files()->get();
$project->files()->count();
```

**Get root-level files only (NEW)**:
```php
$project->rootFiles()->get();
// OR
$project->files()->whereNull('folder_id')->get();
```

**Get files in specific folder (NEW)**:
```php
$project->files()->where('folder_id', $folderId)->get();
```

**Get folder contents (NEW)**:
```php
// Folders
$project->folders()->where('parent_id', $folderId)->get();
// Files
$project->files()->where('folder_id', $folderId)->get();
```

---

## Backwards Compatibility Notes

1. **Existing files** will have `folder_id = null` - treated as root directory
2. **Existing snapshots** won't have folder data - must handle gracefully
3. **All existing functionality** must continue to work unchanged
4. **folder_id = null** is a valid state meaning "root of project/pitch"
