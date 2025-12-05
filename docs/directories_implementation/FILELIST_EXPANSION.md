# FileList Component Expansion

This document details the changes needed to expand the existing FileList component to support folders.

---

## Current FileList Overview

**File**: `app/Livewire/Components/FileList.php`
**View**: `resources/views/livewire/components/file-list.blade.php`

The FileList component is ~1400 lines and handles:
- File display with theming
- Bulk selection and actions
- Comments on files
- Version switching (for PitchFiles)
- Client portal context
- File type detection (audio/video)
- Download, delete, play actions

---

## New Properties to Add

Add these properties to `FileList.php`:

```php
// ==================
// FOLDER NAVIGATION
// ==================

/** Current folder being viewed (null = root) */
public ?int $currentFolderId = null;

/** Whether to show folder navigation UI */
public bool $showFolderNavigation = true;

/** Whether to allow folder CRUD operations */
public bool $enableFolderOperations = false;

/** Folders in the current view */
public Collection $folders;

/** Breadcrumb trail for navigation */
public array $breadcrumbs = [];

/** Whether to expand all folders (for client portal) */
public bool $expandAllFolders = false;

// ==================
// FOLDER CRUD STATE
// ==================

/** Show create folder modal */
public bool $showCreateFolderModal = false;

/** Show rename folder modal */
public bool $showRenameFolderModal = false;

/** New folder name input */
public string $newFolderName = '';

/** Folder ID being renamed */
public ?int $folderToRename = null;

/** Folder ID pending deletion */
public ?int $folderToDelete = null;

// ==================
// MOVE OPERATIONS
// ==================

/** Show move modal */
public bool $showMoveModal = false;

/** Items selected for moving */
public array $itemsToMove = []; // [{type: 'file'|'folder', id: int}]

/** Target folder for move operation */
public ?int $moveTargetFolderId = null;

// ==================
// FOLDER SELECTION (for bulk operations)
// ==================

/** Selected folder IDs */
public array $selectedFolderIds = [];
```

---

## New Methods to Add

### Mount Method Updates

```php
public function mount(
    // ... existing params ...

    // NEW params
    ?int $currentFolderId = null,
    bool $showFolderNavigation = true,
    bool $enableFolderOperations = false,
    bool $expandAllFolders = false
) {
    // ... existing mount code ...

    // NEW: Folder initialization
    $this->currentFolderId = $currentFolderId;
    $this->showFolderNavigation = $showFolderNavigation;
    $this->enableFolderOperations = $enableFolderOperations;
    $this->expandAllFolders = $expandAllFolders;
    $this->folders = collect();

    // Load folder contents
    $this->loadFolderContents();
    $this->loadBreadcrumbs();
}
```

### Navigation Methods

```php
/**
 * Navigate to a folder
 */
public function navigateToFolder(?int $folderId): void
{
    $this->currentFolderId = $folderId;
    $this->clearSelection();
    $this->loadFolderContents();
    $this->loadBreadcrumbs();

    // Notify parent component
    $this->dispatch('folderChanged', folderId: $folderId);
}

/**
 * Navigate up one level
 */
public function navigateUp(): void
{
    if ($this->currentFolderId === null) {
        return;
    }

    $currentFolder = Folder::find($this->currentFolderId);
    $this->navigateToFolder($currentFolder?->parent_id);
}

/**
 * Load breadcrumb navigation data
 */
protected function loadBreadcrumbs(): void
{
    if ($this->currentFolderId === null) {
        $this->breadcrumbs = [];
        return;
    }

    $currentFolder = Folder::find($this->currentFolderId);
    $this->breadcrumbs = $currentFolder?->getBreadcrumbs() ?? [];
}

/**
 * Load folders and files in current folder
 */
protected function loadFolderContents(): void
{
    $modelClass = match ($this->modelType) {
        'project' => \App\Models\Project::class,
        'pitch' => \App\Models\Pitch::class,
        default => null,
    };

    if (!$modelClass || !$this->modelId) {
        $this->folders = collect();
        return;
    }

    $model = $modelClass::find($this->modelId);
    if (!$model) {
        $this->folders = collect();
        return;
    }

    // Load folders
    $this->folders = $model->folders()
        ->when(
            $this->currentFolderId,
            fn($q) => $q->where('parent_id', $this->currentFolderId),
            fn($q) => $q->whereNull('parent_id')
        )
        ->orderBy('name')
        ->get();

    // Load files (update existing reloadFiles logic)
    $this->files = $model->files()
        ->when(
            $this->currentFolderId,
            fn($q) => $q->where('folder_id', $this->currentFolderId),
            fn($q) => $q->whereNull('folder_id')
        )
        ->whereNull('parent_file_id') // Exclude versions
        ->orderBy('created_at', 'desc')
        ->get();
}
```

### Folder CRUD Methods

```php
/**
 * Open create folder modal
 */
public function openCreateFolderModal(): void
{
    $this->newFolderName = '';
    $this->showCreateFolderModal = true;
}

/**
 * Create a new folder
 */
public function createFolder(): void
{
    $this->validate([
        'newFolderName' => 'required|string|max:255',
    ]);

    try {
        $folderService = app(\App\Services\FolderService::class);
        $model = $this->getParentModel();
        $parentFolder = $this->currentFolderId
            ? Folder::find($this->currentFolderId)
            : null;

        $folder = $folderService->createFolder(
            $model,
            $this->newFolderName,
            $parentFolder,
            auth()->user()
        );

        $this->showCreateFolderModal = false;
        $this->newFolderName = '';
        $this->loadFolderContents();

        Toaster::success("Folder '{$folder->name}' created");
        $this->dispatch('folderCreated', folderId: $folder->id);

    } catch (\Exception $e) {
        $this->addError('newFolderName', $e->getMessage());
    }
}

/**
 * Open rename folder modal
 */
public function openRenameFolderModal(int $folderId): void
{
    $folder = Folder::find($folderId);
    if (!$folder) return;

    $this->folderToRename = $folderId;
    $this->newFolderName = $folder->name;
    $this->showRenameFolderModal = true;
}

/**
 * Rename a folder
 */
public function renameFolder(): void
{
    $this->validate([
        'newFolderName' => 'required|string|max:255',
    ]);

    try {
        $folderService = app(\App\Services\FolderService::class);
        $folder = Folder::findOrFail($this->folderToRename);

        $this->authorize('update', $folder);

        $folderService->renameFolder($folder, $this->newFolderName);

        $this->showRenameFolderModal = false;
        $this->folderToRename = null;
        $this->newFolderName = '';
        $this->loadFolderContents();

        Toaster::success("Folder renamed");

    } catch (\Exception $e) {
        $this->addError('newFolderName', $e->getMessage());
    }
}

/**
 * Confirm folder deletion
 */
public function confirmDeleteFolder(int $folderId): void
{
    $this->folderToDelete = $folderId;
    $this->dispatch('modal-show', name: 'delete-folder');
}

/**
 * Delete a folder
 */
public function deleteFolder(bool $deleteContents = false): void
{
    if (!$this->folderToDelete) return;

    try {
        $folderService = app(\App\Services\FolderService::class);
        $folder = Folder::findOrFail($this->folderToDelete);

        $this->authorize('delete', $folder);

        $folderService->deleteFolder($folder, $deleteContents);

        $this->folderToDelete = null;
        $this->loadFolderContents();

        Toaster::success("Folder deleted");
        $this->dispatch('modal-close', name: 'delete-folder');
        $this->dispatch('folderDeleted');

    } catch (\Exception $e) {
        Toaster::error($e->getMessage());
    }
}
```

### Move Operations

```php
/**
 * Open move modal
 */
public function openMoveModal(): void
{
    // Collect selected items
    $this->itemsToMove = [];

    foreach ($this->selectedFolderIds as $folderId) {
        $this->itemsToMove[] = ['type' => 'folder', 'id' => $folderId];
    }

    foreach ($this->selectedFileIds as $fileId) {
        $this->itemsToMove[] = ['type' => 'file', 'id' => $fileId];
    }

    if (empty($this->itemsToMove)) {
        Toaster::warning('No items selected');
        return;
    }

    $this->moveTargetFolderId = null;
    $this->showMoveModal = true;
}

/**
 * Move selected items to target folder
 */
public function moveToFolder(): void
{
    if (empty($this->itemsToMove)) return;

    try {
        $folderService = app(\App\Services\FolderService::class);
        $targetFolder = $this->moveTargetFolderId
            ? Folder::find($this->moveTargetFolderId)
            : null;

        foreach ($this->itemsToMove as $item) {
            if ($item['type'] === 'folder') {
                $folder = Folder::find($item['id']);
                if ($folder) {
                    $folderService->moveFolder($folder, $targetFolder);
                }
            } else {
                $file = $this->modelType === 'project'
                    ? ProjectFile::find($item['id'])
                    : PitchFile::find($item['id']);
                if ($file) {
                    $folderService->moveFileToFolder($file, $targetFolder);
                }
            }
        }

        $this->showMoveModal = false;
        $this->itemsToMove = [];
        $this->moveTargetFolderId = null;
        $this->clearSelection();
        $this->loadFolderContents();

        Toaster::success('Items moved successfully');
        $this->dispatch('filesMoved');

    } catch (\Exception $e) {
        Toaster::error($e->getMessage());
    }
}

/**
 * Get available folders for move target selection
 */
#[Computed]
public function availableMoveTargets(): Collection
{
    $model = $this->getParentModel();
    if (!$model) return collect();

    $excludeIds = collect($this->itemsToMove)
        ->filter(fn($item) => $item['type'] === 'folder')
        ->pluck('id')
        ->toArray();

    // Get all folders except selected ones and their descendants
    return $model->folders()
        ->whereNotIn('id', $excludeIds)
        ->orderBy('path')
        ->get();
}
```

### Helper Methods

```php
/**
 * Get the parent model (Project or Pitch)
 */
protected function getParentModel(): ?Model
{
    $modelClass = match ($this->modelType) {
        'project' => \App\Models\Project::class,
        'pitch' => \App\Models\Pitch::class,
        default => null,
    };

    return $modelClass ? $modelClass::find($this->modelId) : null;
}

/**
 * Toggle folder selection
 */
public function toggleFolderSelection(int $folderId): void
{
    if (!$this->enableBulkActions) return;

    $index = array_search($folderId, $this->selectedFolderIds);

    if ($index !== false) {
        unset($this->selectedFolderIds[$index]);
        $this->selectedFolderIds = array_values($this->selectedFolderIds);
    } else {
        $this->selectedFolderIds[] = $folderId;
    }

    $this->isSelectMode = !empty($this->selectedFolderIds) || !empty($this->selectedFileIds);
}

/**
 * Clear all selections
 */
public function clearSelection(): void
{
    $this->selectedFileIds = [];
    $this->selectedFolderIds = [];
    $this->isSelectMode = false;
}

/**
 * Check if a folder is selected
 */
public function isFolderSelected(int $folderId): bool
{
    return in_array($folderId, $this->selectedFolderIds);
}
```

---

## Blade View Changes

### Add Breadcrumb Navigation

Add after the header section (after line ~65):

```blade
{{-- Folder Breadcrumb Navigation --}}
@if($showFolderNavigation && ($currentFolderId || count($breadcrumbs) > 0))
    <div class="flex items-center gap-1 px-2 py-2 text-sm border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <button
            wire:click="navigateToFolder(null)"
            class="flex items-center gap-1 px-2 py-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors {{ !$currentFolderId ? 'font-semibold text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }}">
            <flux:icon name="folder" class="w-4 h-4" />
            <span>Root</span>
        </button>

        @foreach($breadcrumbs as $index => $crumb)
            <flux:icon name="chevron-right" class="w-4 h-4 text-gray-400" />
            <button
                wire:click="navigateToFolder({{ $crumb['id'] }})"
                class="px-2 py-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors
                       {{ $index === count($breadcrumbs) - 1 ? 'font-semibold text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }}">
                {{ $crumb['name'] }}
            </button>
        @endforeach

        @if($enableFolderOperations)
            <div class="ml-auto">
                <flux:button
                    wire:click="openCreateFolderModal"
                    variant="ghost"
                    size="xs"
                    icon="folder-plus">
                    New Folder
                </flux:button>
            </div>
        @endif
    </div>
@endif
```

### Add Folder Items Before Files

Add before the `@forelse($files as $file)` loop:

```blade
{{-- Folders --}}
@if($showFolderNavigation && $folders->count() > 0)
    @foreach($folders as $folder)
        <div
            wire:key="folder-{{ $folder->id }}"
            class="flex items-center py-3 px-2 hover:bg-gray-50 dark:hover:bg-gray-800 border-b border-gray-100 dark:border-gray-800 cursor-pointer group
                   @if($enableBulkActions && $this->isFolderSelected($folder->id)) {{ $this->resolvedColorScheme['accent_bg'] }} border-l-4 {{ $this->resolvedColorScheme['accent_border'] }} @endif">

            {{-- Selection Checkbox --}}
            @if($enableBulkActions)
                <div class="flex items-center px-2 group-hover:opacity-100
                            {{ $this->isFolderSelected($folder->id) || $isSelectMode ? 'opacity-100' : 'opacity-0' }}
                            transition-opacity duration-200">
                    <label class="relative cursor-pointer">
                        <input
                            type="checkbox"
                            wire:click.stop="toggleFolderSelection({{ $folder->id }})"
                            {{ $this->isFolderSelected($folder->id) ? 'checked' : '' }}
                            class="sr-only"
                        />
                        <div class="w-4 h-4 border-2 rounded transition-all duration-200 flex items-center justify-center
                            {{ $this->isFolderSelected($folder->id) ? $this->resolvedColorScheme['accent_bg'] . ' ' . $this->resolvedColorScheme['accent_border'] : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800' }}">
                            @if($this->isFolderSelected($folder->id))
                                <flux:icon.check class="w-2.5 h-2.5 text-white" />
                            @endif
                        </div>
                    </label>
                </div>
            @endif

            {{-- Folder Icon and Name --}}
            <div
                wire:click="navigateToFolder({{ $folder->id }})"
                class="flex items-center flex-1 min-w-0">
                <div class="flex-shrink-0 mx-2">
                    <flux:icon name="folder" variant="solid" class="w-8 h-8 text-yellow-500" />
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-base font-semibold text-gray-900 dark:text-gray-100 truncate block">
                        {{ $folder->name }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $folder->children()->count() }} folders,
                        {{ $folder->getFiles()->count() }} files
                    </span>
                </div>
            </div>

            {{-- Folder Actions --}}
            @if($enableFolderOperations)
                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <flux:dropdown>
                        <flux:button variant="ghost" size="xs" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item
                                wire:click.stop="openRenameFolderModal({{ $folder->id }})"
                                icon="pencil">
                                Rename
                            </flux:menu.item>
                            <flux:menu.item
                                wire:click.stop="confirmDeleteFolder({{ $folder->id }})"
                                icon="trash"
                                variant="danger">
                                Delete
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            @endif
        </div>
    @endforeach
@endif
```

### Add "Move to Folder" Bulk Action

In the bulk actions toolbar, add:

```blade
@if(in_array('move', $bulkActions) && $enableFolderOperations)
    <flux:button
        wire:click="openMoveModal"
        variant="outline"
        size="sm"
        icon="folder-arrow-down">
        Move to Folder
    </flux:button>
@endif
```

### Add Folder Modals

Add at the end of the component (before closing `</div>`):

```blade
{{-- Create Folder Modal --}}
<flux:modal name="create-folder" :open="$showCreateFolderModal" wire:model="showCreateFolderModal" class="max-w-md">
    <div class="space-y-4">
        <flux:heading size="lg">Create New Folder</flux:heading>

        <form wire:submit.prevent="createFolder">
            <flux:field>
                <flux:label for="newFolderName">Folder Name</flux:label>
                <flux:input
                    wire:model.live="newFolderName"
                    id="newFolderName"
                    placeholder="Enter folder name"
                    autofocus />
                @error('newFolderName')
                    <flux:error>{{ $message }}</flux:error>
                @enderror
            </flux:field>

            <div class="flex justify-end gap-3 mt-6">
                <flux:button type="button" variant="ghost" wire:click="$set('showCreateFolderModal', false)">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary" icon="folder-plus">
                    Create Folder
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>

{{-- Rename Folder Modal --}}
<flux:modal name="rename-folder" :open="$showRenameFolderModal" wire:model="showRenameFolderModal" class="max-w-md">
    <div class="space-y-4">
        <flux:heading size="lg">Rename Folder</flux:heading>

        <form wire:submit.prevent="renameFolder">
            <flux:field>
                <flux:label for="renameFolderName">Folder Name</flux:label>
                <flux:input
                    wire:model.live="newFolderName"
                    id="renameFolderName"
                    placeholder="Enter new name"
                    autofocus />
                @error('newFolderName')
                    <flux:error>{{ $message }}</flux:error>
                @enderror
            </flux:field>

            <div class="flex justify-end gap-3 mt-6">
                <flux:button type="button" variant="ghost" wire:click="$set('showRenameFolderModal', false)">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary" icon="pencil">
                    Rename
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>

{{-- Delete Folder Modal --}}
<flux:modal name="delete-folder" class="max-w-md">
    <div class="space-y-4">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                <flux:icon.exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400" />
            </div>
            <flux:heading size="lg">Delete Folder</flux:heading>
        </div>

        <flux:subheading class="text-gray-600 dark:text-gray-400">
            What would you like to do with the files in this folder?
        </flux:subheading>

        <div class="flex flex-col gap-3 pt-4">
            <flux:button
                wire:click="deleteFolder(false)"
                variant="outline"
                class="justify-start">
                <flux:icon name="arrow-uturn-up" class="w-5 h-5 mr-2" />
                Move files to root folder
            </flux:button>

            <flux:button
                wire:click="deleteFolder(true)"
                variant="danger"
                class="justify-start">
                <flux:icon name="trash" class="w-5 h-5 mr-2" />
                Delete folder and all contents
            </flux:button>

            <flux:modal.close>
                <flux:button variant="ghost" class="w-full">
                    Cancel
                </flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>

{{-- Move to Folder Modal --}}
<flux:modal name="move-to-folder" :open="$showMoveModal" wire:model="showMoveModal" class="max-w-lg">
    <div class="space-y-4">
        <flux:heading size="lg">Move to Folder</flux:heading>

        <flux:subheading class="text-gray-600 dark:text-gray-400">
            Select a destination folder for {{ count($itemsToMove) }} item(s)
        </flux:subheading>

        <div class="max-h-64 overflow-y-auto border rounded-lg divide-y dark:border-gray-700 dark:divide-gray-700">
            {{-- Root option --}}
            <label class="flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                <input
                    type="radio"
                    wire:model="moveTargetFolderId"
                    value=""
                    class="text-blue-600" />
                <flux:icon name="folder" class="w-5 h-5 text-gray-400" />
                <span class="font-medium">Root (no folder)</span>
            </label>

            {{-- Available folders --}}
            @foreach($this->availableMoveTargets as $targetFolder)
                <label class="flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                       style="padding-left: {{ ($targetFolder->depth * 1.5) + 0.75 }}rem">
                    <input
                        type="radio"
                        wire:model="moveTargetFolderId"
                        value="{{ $targetFolder->id }}"
                        class="text-blue-600" />
                    <flux:icon name="folder" class="w-5 h-5 text-yellow-500" />
                    <span>{{ $targetFolder->name }}</span>
                </label>
            @endforeach
        </div>

        <div class="flex justify-end gap-3 pt-4">
            <flux:button type="button" variant="ghost" wire:click="$set('showMoveModal', false)">
                Cancel
            </flux:button>
            <flux:button type="button" wire:click="moveToFolder" variant="primary" icon="folder-arrow-down">
                Move Here
            </flux:button>
        </div>
    </div>
</flux:modal>
```

---

## Testing Considerations

### Test Cases to Add

1. **Navigation Tests**
   - Navigate into folder
   - Navigate back to root
   - Navigate via breadcrumb
   - Navigate when no folders exist

2. **CRUD Tests**
   - Create folder at root
   - Create nested folder
   - Create folder at max depth (should fail)
   - Rename folder
   - Rename to duplicate name (should fail)
   - Delete empty folder
   - Delete folder with contents (move to root)
   - Delete folder with contents (delete all)

3. **Move Tests**
   - Move file to folder
   - Move folder to folder
   - Move multiple items
   - Move into self (should fail)
   - Move into descendant (should fail)

4. **Bulk Selection Tests**
   - Select folders
   - Select files and folders together
   - Clear selection on navigation

5. **Backwards Compatibility Tests**
   - Ensure all existing FileList usages work unchanged
   - Test with `showFolderNavigation = false`
   - Test without folder parameters
