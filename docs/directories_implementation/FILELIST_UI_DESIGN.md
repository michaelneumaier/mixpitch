# FileList Component UI Design

This document provides a concrete, implementable UI design for the folder-enabled FileList component with height constraints, scroll indicators, navigation, and folder operations.

---

## Design Decisions Summary

| Aspect | Decision |
|--------|----------|
| **Height** | Configurable: min 56px (1 file), max ~588px (10.5 files), or unbounded |
| **Scroll indicator** | Inset shadow at bottom when scrollable |
| **Navigation** | Breadcrumb bar + back button |
| **Folder actions** | Header Actions dropdown + selection toolbar + per-item dropdowns |

---

## Component Layout Structure

```
+---------------------------------------------------------------------+
| HEADER SECTION                                                       |
| +-[X]-+-[F]+-+ Files (12) . 45.2 MB +---------+--[ Actions v ]-----+ |
| | All | Icon | Count + Size                    |  - New Folder     | |
| |     |      |                                 |  - Bulk Upload    | |
| +-----+------+---------------------------------+-------------------+ |
+---------------------------------------------------------------------+
| BREADCRUMB + NAVIGATION (when in subfolder)                         |
| +-[<- Back]-+-+ Root / Stems / Drums +-----------------------------+|
| +----------+-----------------------------------------------------------+
+---------------------------------------------------------------------+
| SELECTION TOOLBAR (when items selected)                             |
| +- 3 selected . 12.4 MB ---------------------+--[ Actions v ]+[ X ]-+ |
| |                                            |  - Move to... | Clear | |
| |                                            |  - Download   |       | |
| |                                            |  - Delete     |       | |
| +--------------------------------------------+--------------+-------+ |
+---------------------------------------------------------------------+
| SCROLLABLE CONTENT AREA (max-height with overflow-y-auto)           |
| +-------------------------------------------------------------------+
| | +-[X]-[F]- Vocals ---------------------------- 3 files ----[...]+ |
| | |          <- subfolder row, click to navigate                  | |
| | +---------------------------------------------------------------+ |
| | +-[X]-[F]- Stems ----------------------------- 8 files ----[...]+ |
| | |                                                               | |
| | +---------------------------------------------------------------+ |
| | +-[X]-[>]- final_mix.mp3 ------------- 4:32 . 8.2MB -------[...]+ |
| | |          <- file row with audio controls                      | |
| | +---------------------------------------------------------------+ |
| | +-[X]-[>]- rough_mix.mp3 ------------- 4:28 . 7.9MB -------[...]+ |
| | |                                                               | |
| | + - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - + |
| | |                    (partial 11th item visible)                | |
| | +---------------------------------------------------------------+ |
| +------------------------- shadow indicator -----------------------+ |
+---------------------------------------------------------------------+
```

---

## 1. Height Constraint System

### Props
```php
// FileList.php
public string $heightMode = 'constrained'; // 'constrained' | 'auto'
public int $maxVisibleItems = 10; // Used when heightMode = 'constrained'
```

### CSS Implementation
```blade
{{-- Wrapper for scrollable content --}}
@php
    $itemHeight = 56; // px - height of single file/folder row
    $maxHeight = $heightMode === 'constrained'
        ? ($maxVisibleItems + 0.5) * $itemHeight
        : null;
@endphp

<div
    class="relative"
    x-data="{ hasOverflow: false }"
    x-init="$nextTick(() => hasOverflow = $refs.scrollContainer.scrollHeight > $refs.scrollContainer.clientHeight)"
    x-on:resize.window="hasOverflow = $refs.scrollContainer.scrollHeight > $refs.scrollContainer.clientHeight"
>
    <div
        x-ref="scrollContainer"
        class="divide-y divide-gray-200 dark:divide-gray-700 overflow-y-auto"
        @if($maxHeight)
            style="max-height: {{ $maxHeight }}px; min-height: {{ $itemHeight }}px;"
        @endif
        x-on:scroll="hasOverflow = $el.scrollHeight > $el.clientHeight && $el.scrollTop < $el.scrollHeight - $el.clientHeight - 10"
    >
        {{-- Folder items --}}
        {{-- File items --}}
    </div>

    {{-- Shadow indicator when more content below --}}
    <div
        x-show="hasOverflow"
        x-transition:enter="transition-opacity duration-200"
        class="absolute bottom-0 left-0 right-0 h-8 pointer-events-none bg-gradient-to-t from-white/80 dark:from-gray-900/80 to-transparent"
        style="box-shadow: inset 0 -12px 12px -12px rgba(0,0,0,0.15);"
    ></div>
</div>
```

### Height Calculation
| Item Count | Max Height | Behavior |
|------------|------------|----------|
| 1-10 | Auto (grows) | No scroll needed |
| 11+ | 588px (10.5 x 56) | Scroll, partial 11th visible |
| Auto mode | None | Full height, page scrolls |

---

## 2. Header Section Redesign

### Current -> New
```
BEFORE:
+-[X]-[F]- Files (12) . 45.2 MB --------[ Bulk Upload Versions ]-+

AFTER:
+-[X]-[F]- Files (12) . 45.2 MB ----------------[ Actions v ]---+
                                                   +-- Dropdown --+
```

### Actions Dropdown Contents
```php
// Context-dependent actions
$headerActions = collect([
    [
        'key' => 'new_folder',
        'label' => 'New Folder',
        'icon' => 'folder-plus',
        'show' => $enableFolderOperations,
        'method' => 'openCreateFolderModal',
    ],
    [
        'key' => 'bulk_upload',
        'label' => 'Bulk Upload Versions',
        'icon' => 'arrow-up-tray',
        'show' => $showBulkUpload && $modelType === 'pitch',
        'method' => 'openBulkUploadModal',
    ],
    [
        'key' => 'upload_files',
        'label' => 'Upload Files',
        'icon' => 'document-plus',
        'show' => $canUpload,
        'emit' => 'open-uploader',
    ],
])->filter(fn($a) => $a['show']);
```

### Blade Implementation
```blade
<div class="flex items-center gap-3 mb-2">
    {{-- Selection checkbox (if bulk enabled) --}}
    @if($enableBulkActions)
        <input type="checkbox" ... />
    @endif

    {{-- Icon --}}
    <flux:icon name="folder" class="w-6 h-6" variant="solid" />

    {{-- Title + stats --}}
    <div class="flex-1 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <flux:heading size="base" class="!mb-0">
                {{ $title ?? 'Files' }}
            </flux:heading>
            <span class="text-sm text-gray-500">
                ({{ $this->totalItemCount }}) . {{ $this->formattedTotalSize }}
            </span>
        </div>

        {{-- Actions Dropdown --}}
        @if($headerActions->isNotEmpty())
            <flux:dropdown align="end">
                <flux:button variant="ghost" size="sm" icon-trailing="chevron-down">
                    Actions
                </flux:button>
                <flux:menu>
                    @foreach($headerActions as $action)
                        <flux:menu.item
                            wire:click="{{ $action['method'] ?? '' }}"
                            icon="{{ $action['icon'] }}"
                        >
                            {{ $action['label'] }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>
        @endif
    </div>
</div>
```

---

## 3. Breadcrumb + Navigation Bar

### When Visible
- Only when `$currentFolderId !== null` (user is inside a folder)
- Positioned between header and selection toolbar

### Layout
```blade
@if($currentFolderId)
<div class="flex items-center gap-2 px-2 py-2 mb-2 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
    {{-- Back Button --}}
    <flux:button
        variant="ghost"
        size="sm"
        icon="arrow-left"
        wire:click="navigateUp"
        class="flex-shrink-0"
    >
        <span class="sr-only sm:not-sr-only">Back</span>
    </flux:button>

    <div class="h-4 w-px bg-gray-300 dark:bg-gray-600"></div>

    {{-- Breadcrumbs --}}
    <nav class="flex items-center gap-1 text-sm overflow-x-auto min-w-0">
        {{-- Root --}}
        <button
            wire:click="navigateToFolder(null)"
            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex-shrink-0"
        >
            <flux:icon name="home" class="w-4 h-4" />
        </button>

        @foreach($this->breadcrumbs as $crumb)
            <flux:icon name="chevron-right" class="w-3 h-3 text-gray-400 flex-shrink-0" />

            @if($loop->last)
                <span class="font-medium text-gray-900 dark:text-gray-100 truncate">
                    {{ $crumb['name'] }}
                </span>
            @else
                <button
                    wire:click="navigateToFolder({{ $crumb['id'] }})"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 truncate"
                >
                    {{ $crumb['name'] }}
                </button>
            @endif
        @endforeach
    </nav>
</div>
@endif
```

### Breadcrumb Computation
```php
#[Computed]
public function breadcrumbs(): array
{
    if (!$this->currentFolderId) {
        return [];
    }

    $folder = Folder::find($this->currentFolderId);
    $crumbs = [];

    while ($folder) {
        array_unshift($crumbs, [
            'id' => $folder->id,
            'name' => $folder->name,
        ]);
        $folder = $folder->parent;
    }

    return $crumbs;
}
```

---

## 4. Selection Toolbar Enhancement

### Current Behavior
- Shows when files selected
- Displays count + size
- Has Actions dropdown

### New Behavior
- Supports folders AND files
- Actions adapt based on selection type(s)
- Single item = all applicable actions
- Multiple items = only bulk-appropriate actions

### Action Logic
```php
#[Computed]
public function selectionActions(): array
{
    $selectedFolders = $this->selectedFolderIds;
    $selectedFiles = $this->selectedFileIds;
    $totalSelected = count($selectedFolders) + count($selectedFiles);
    $isSingleItem = $totalSelected === 1;
    $hasOnlyFolders = count($selectedFiles) === 0;
    $hasOnlyFiles = count($selectedFolders) === 0;

    return collect([
        // Always available
        [
            'key' => 'move',
            'label' => 'Move to...',
            'icon' => 'folder-arrow-down',
            'show' => $this->enableFolderOperations,
        ],
        [
            'key' => 'download',
            'label' => $totalSelected > 1 ? "Download ({$totalSelected})" : 'Download',
            'icon' => 'arrow-down-tray',
            'show' => $hasOnlyFiles || ($hasOnlyFolders && $isSingleItem), // Can download folder as ZIP
        ],
        [
            'key' => 'download_zip',
            'label' => 'Download as ZIP',
            'icon' => 'archive-box',
            'show' => $totalSelected > 1,
        ],
        // Single item only
        [
            'key' => 'rename',
            'label' => 'Rename',
            'icon' => 'pencil',
            'show' => $isSingleItem && $hasOnlyFolders && $this->enableFolderOperations,
        ],
        // Destructive - always last
        [
            'key' => 'delete',
            'label' => $totalSelected > 1 ? "Delete ({$totalSelected})" : 'Delete',
            'icon' => 'trash',
            'show' => $this->canDelete,
            'variant' => 'danger',
        ],
    ])->filter(fn($a) => $a['show'])->values()->all();
}
```

### Blade Implementation
```blade
@if(count($selectedFileIds) + count($selectedFolderIds) > 0)
<div class="mb-4 p-3 {{ $resolvedColorScheme['accent_bg'] }} {{ $resolvedColorScheme['accent_border'] }} border rounded-lg animate-in slide-in-from-top-2 duration-300">
    <div class="flex items-center justify-between gap-2">
        {{-- Selection info --}}
        <div class="flex items-center gap-3">
            <span class="font-medium {{ $resolvedColorScheme['text_primary'] }}">
                {{ $this->selectionCount }} selected
            </span>
            @if($this->selectedTotalSize > 0)
                <span class="text-sm {{ $resolvedColorScheme['text_muted'] }}">
                    . {{ $this->formattedSelectedSize }}
                </span>
            @endif
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-2">
            <flux:dropdown align="end">
                <flux:button size="sm" variant="ghost" icon-trailing="chevron-down">
                    Actions
                </flux:button>
                <flux:menu>
                    @foreach($this->selectionActions as $action)
                        <flux:menu.item
                            wire:click="executeSelectionAction('{{ $action['key'] }}')"
                            icon="{{ $action['icon'] }}"
                            @if(($action['variant'] ?? '') === 'danger')
                                variant="danger"
                            @endif
                        >
                            {{ $action['label'] }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>

            <flux:button
                size="sm"
                variant="ghost"
                icon="x-mark"
                wire:click="clearSelection"
            />
        </div>
    </div>
</div>
@endif
```

---

## 5. Folder Item Row Design

### Visual Comparison: Folder vs File

```
FOLDER ROW:
+-[X]-[F solid yellow]- Stems --------------- 8 files -----[...]-+
|      h-10 w-10           name                  metadata  actions|

FILE ROW (existing):
+-[X]-[> play button]- track.mp3 ------ 4:32 . 8.2MB ----[...]-+
|      h-10 w-10           name          duration   size  actions|
```

### Key Differences
| Aspect | Folder | File |
|--------|--------|------|
| Icon | Solid folder (yellow/amber) | Play button or file type icon |
| Click behavior | Navigate into folder | Play/preview file |
| Metadata | "X files, Y folders" | Duration, size, date |
| Actions dropdown | Rename, Move, Delete | Play, Download, Version, Delete |

### Folder Item Blade
```blade
{{-- FolderItem row --}}
<div
    wire:key="folder-{{ $folder->id }}"
    class="flex items-center py-2 px-2 hover:bg-gray-50 dark:hover:bg-gray-800
           {{ in_array($folder->id, $selectedFolderIds) ? $resolvedColorScheme['accent_bg'] . ' border-l-4 ' . $resolvedColorScheme['accent_border'] : '' }}"
>
    {{-- Selection checkbox --}}
    @if($enableBulkActions)
    <div class="flex items-center px-2">
        <input
            type="checkbox"
            wire:click="toggleFolderSelection({{ $folder->id }})"
            @checked(in_array($folder->id, $selectedFolderIds))
            class="w-4 h-4 rounded border-gray-300 ..."
        />
    </div>
    @endif

    {{-- Folder icon (clickable to navigate) --}}
    <button
        wire:click="navigateToFolder({{ $folder->id }})"
        class="h-10 w-10 mx-2 flex-shrink-0 rounded-lg bg-amber-100 dark:bg-amber-900/30
               flex items-center justify-center hover:bg-amber-200 dark:hover:bg-amber-900/50
               transition-colors"
    >
        <flux:icon name="folder" variant="solid" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
    </button>

    {{-- Folder info (also clickable) --}}
    <button
        wire:click="navigateToFolder({{ $folder->id }})"
        class="flex-1 min-w-0 text-left"
    >
        <div class="flex items-center gap-2">
            <span class="text-base font-semibold text-gray-900 dark:text-gray-100 truncate">
                {{ $folder->name }}
            </span>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">
            {{ $folder->children_count }} folders, {{ $folder->files_count }} files
        </div>
    </button>

    {{-- Actions dropdown --}}
    @if($enableFolderOperations)
    <flux:dropdown align="end">
        <flux:button variant="ghost" size="xs" icon="ellipsis-vertical" />
        <flux:menu>
            <flux:menu.item wire:click="openRenameFolderModal({{ $folder->id }})" icon="pencil">
                Rename
            </flux:menu.item>
            <flux:menu.item wire:click="openMoveModal('folder', {{ $folder->id }})" icon="folder-arrow-down">
                Move to...
            </flux:menu.item>
            <flux:menu.separator />
            <flux:menu.item wire:click="confirmDeleteFolder({{ $folder->id }})" icon="trash" variant="danger">
                Delete Folder
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>
    @endif
</div>
```

---

## 6. Scroll Content Ordering

### Order within scrollable area
1. **Folders first** (sorted by name)
2. **Files second** (sorted by existing sort logic)

```blade
<div x-ref="scrollContainer" class="divide-y divide-gray-200 dark:divide-gray-700 overflow-y-auto" ...>
    {{-- Folders --}}
    @foreach($this->currentFolders as $folder)
        @include('livewire.components.partials.folder-item', ['folder' => $folder])
    @endforeach

    {{-- Files --}}
    @foreach($this->currentFiles as $file)
        @include('livewire.components.partials.file-item', ['file' => $file])
    @endforeach

    {{-- Empty state --}}
    @if($this->currentFolders->isEmpty() && $this->currentFiles->isEmpty())
        @include('livewire.components.partials.empty-state')
    @endif
</div>
```

---

## 7. Mobile Considerations

### Current Approach
- Same max-height behavior on mobile
- Breadcrumbs may truncate (overflow-x-auto allows horizontal scroll if needed)
- Back button icon-only on small screens (`sr-only sm:not-sr-only` for text)

### Future Enhancement Ideas (Not for initial implementation)
- Touch-friendly folder navigation (swipe to go back?)
- Collapsible file metadata on mobile
- Full-screen folder view on small devices

---

## 8. New Properties for FileList.php

```php
// Folder navigation
public ?int $currentFolderId = null;
public bool $showFolderNavigation = true;
public bool $enableFolderOperations = false;

// Height control
public string $heightMode = 'constrained'; // 'constrained' | 'auto'
public int $maxVisibleItems = 10;

// Selection (existing + new)
public array $selectedFileIds = [];
public array $selectedFolderIds = []; // NEW

// Folder CRUD state
public bool $showCreateFolderModal = false;
public bool $showRenameFolderModal = false;
public bool $showMoveFolderModal = false;
public string $newFolderName = '';
public ?int $folderToRename = null;
public ?int $folderToDelete = null;
```

---

## 9. Computed Properties

```php
#[Computed]
public function currentFolders(): Collection
{
    $folderable = $this->getParentModel();
    return $folderable->folders()
        ->where('parent_id', $this->currentFolderId)
        ->withCount(['children', 'files'])
        ->orderBy('name')
        ->get();
}

#[Computed]
public function currentFiles(): Collection
{
    return $this->files->filter(function ($file) {
        return $file->folder_id === $this->currentFolderId;
    });
}

#[Computed]
public function totalItemCount(): int
{
    return $this->currentFolders->count() + $this->currentFiles->count();
}

#[Computed]
public function selectionCount(): int
{
    return count($this->selectedFileIds) + count($this->selectedFolderIds);
}
```

---

## 10. Files to Modify

### Primary Files
| File | Changes |
|------|---------|
| `app/Livewire/Components/FileList.php` | Add folder props, computed properties, navigation methods, selection handling |
| `resources/views/livewire/components/file-list.blade.php` | Restructure layout, add breadcrumbs, height constraints, folder items |

### New Partials (Extract from main blade)
| File | Purpose |
|------|---------|
| `resources/views/livewire/components/partials/folder-item.blade.php` | Folder row template |
| `resources/views/livewire/components/partials/file-item.blade.php` | File row template (extract existing) |
| `resources/views/livewire/components/partials/breadcrumb-nav.blade.php` | Navigation bar |
| `resources/views/livewire/components/partials/selection-toolbar.blade.php` | Selection actions bar |

### Modals to Add
| Modal | Purpose |
|-------|---------|
| Create Folder | Name input for new folder |
| Rename Folder | Edit folder name |
| Delete Folder | Confirm with contents handling |
| Move Items | Select target folder |

---

## 11. Implementation Phases

### Phase 0 (Already planned)
- Extract FileItem, FileComments, FileBulkActions components
- Create FolderItem placeholder

### New UI Work (After Phase 0)
1. Add height constraint system with scroll shadow
2. Restructure header with Actions dropdown
3. Add breadcrumb navigation bar
4. Enhance selection toolbar for folders
5. Implement folder item rows
6. Add folder CRUD modals
7. Wire up navigation methods
8. Test across all parent component contexts

---

## 12. Visual Reference: Complete Component

```
+---------------------------------------------------------------------+
| [X] F Files (15) . 67.8 MB                        [ Actions v ]     |
+---------------------------------------------------------------------+
| [<- Back]  |  Home > Stems > Drums                                  |
+---------------------------------------------------------------------+
| +- 2 selected . 8.4 MB ------------------- [ Actions v ] [ X ] -+   |
+---------------------------------------------------------------------+
| | [X] F Kicks                              3 files, 1 folder  [...] | <- Folder
| +-------------------------------------------------------------------+
| | [*] F Snares                             5 files             [...] | <- Selected
| +-------------------------------------------------------------------+
| | [X] >  kick_main.wav                     0:02 . 1.2 MB       [...] | <- File
| +-------------------------------------------------------------------+
| | [*] >  kick_alt.wav                      0:02 . 1.1 MB       [...] | <- Selected
| +-------------------------------------------------------------------+
| | [X] >  kick_sub.wav                      0:01 . 0.8 MB       [...] |
| + - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - +
| | [X] >  kick_click.wav                    0:01 . 0.6 M...     |     | <- Partial
| +------------------------- shadow indicator -----------------------+ |
+---------------------------------------------------------------------+
```

---

## 13. Empty Folder State

When navigated into an empty folder (no files or subfolders):

```blade
@if($this->currentFolders->isEmpty() && $this->currentFiles->isEmpty())
<div class="p-8 text-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
    <flux:icon name="folder-open" class="w-16 h-16 text-gray-400 dark:text-gray-500 mx-auto mb-4" />
    <flux:heading size="lg" class="text-gray-800 dark:text-gray-200 mb-2">
        This folder is empty
    </flux:heading>
    <flux:subheading class="text-gray-600 dark:text-gray-400 mb-4">
        Drop files here or click to upload
    </flux:subheading>

    @if($canUpload)
    <flux:button
        wire:click="$dispatch('open-uploader')"
        icon="arrow-up-tray"
        variant="primary"
    >
        Upload Files
    </flux:button>
    @endif
</div>
@endif
```

---

## 14. Delete Folder Confirmation Modal

When deleting a folder with contents, show detailed confirmation:

```blade
<flux:modal wire:model="showDeleteFolderModal" max-w-md>
    <div class="space-y-6">
        {{-- Warning header --}}
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                <flux:icon name="exclamation-triangle" class="w-6 h-6 text-red-600 dark:text-red-400" />
            </div>
            <div>
                <flux:heading size="lg">Delete "{{ $folderToDeleteName }}"?</flux:heading>
                <flux:subheading class="mt-1">
                    This action cannot be undone.
                </flux:subheading>
            </div>
        </div>

        {{-- Contents summary --}}
        @if($folderToDeleteStats['total_files'] > 0 || $folderToDeleteStats['total_folders'] > 0)
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
            <div class="flex items-center gap-2 text-amber-800 dark:text-amber-200 font-medium mb-2">
                <flux:icon name="folder" class="w-5 h-5" />
                This folder contains:
            </div>
            <ul class="text-sm text-amber-700 dark:text-amber-300 space-y-1 ml-7">
                @if($folderToDeleteStats['total_folders'] > 0)
                <li>{{ $folderToDeleteStats['total_folders'] }} subfolder(s)</li>
                @endif
                @if($folderToDeleteStats['total_files'] > 0)
                <li>{{ $folderToDeleteStats['total_files'] }} file(s)</li>
                @endif
                <li class="font-medium">{{ $folderToDeleteStats['formatted_size'] }} total</li>
            </ul>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            All files and subfolders will be permanently deleted.
        </p>
        @endif

        {{-- Confirmation actions --}}
        <div class="flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="$set('showDeleteFolderModal', false)">
                Cancel
            </flux:button>
            <flux:button variant="danger" wire:click="deleteFolder" icon="trash">
                Delete Folder
            </flux:button>
        </div>
    </div>
</flux:modal>
```

### PHP Support for Delete Stats

```php
public ?int $folderToDelete = null;

#[Computed]
public function folderToDeleteName(): ?string
{
    return $this->folderToDelete
        ? Folder::find($this->folderToDelete)?->name
        : null;
}

#[Computed]
public function folderToDeleteStats(): array
{
    if (!$this->folderToDelete) {
        return ['total_files' => 0, 'total_folders' => 0, 'total_size' => 0, 'formatted_size' => '0 B'];
    }

    $folder = Folder::find($this->folderToDelete);

    // Get all descendant folder IDs using materialized path
    $descendantFolderIds = Folder::where('path', 'like', $folder->path . '%')
        ->where('id', '!=', $folder->id)
        ->pluck('id');

    $allFolderIds = $descendantFolderIds->push($folder->id);

    // Count files in all folders
    $fileQuery = $this->modelType === 'project'
        ? ProjectFile::whereIn('folder_id', $allFolderIds)
        : PitchFile::whereIn('folder_id', $allFolderIds);

    $totalFiles = $fileQuery->count();
    $totalSize = $fileQuery->sum('size');

    return [
        'total_files' => $totalFiles,
        'total_folders' => $descendantFolderIds->count(),
        'total_size' => $totalSize,
        'formatted_size' => $this->formatFileSize($totalSize),
    ];
}

public function confirmDeleteFolder(int $folderId): void
{
    $this->folderToDelete = $folderId;
    $this->showDeleteFolderModal = true;
}

public function deleteFolder(): void
{
    $folder = Folder::findOrFail($this->folderToDelete);
    $this->authorize('delete', $folder);

    app(FolderService::class)->deleteFolder($folder, deleteContents: true);

    $this->showDeleteFolderModal = false;
    $this->folderToDelete = null;

    // If we were inside the deleted folder, navigate up
    if ($this->currentFolderId === $folder->id) {
        $this->currentFolderId = $folder->parent_id;
    }

    $this->loadFolderContents();
    Toaster::success("Folder deleted successfully");
}
```

---

## Summary

This plan provides a concrete, implementable UI design for the folder-enabled FileList with:
- **Height constraints** via configurable max-height (10.5 items = 588px) with scroll shadow indicator
- **Breadcrumb navigation** with back button for folder traversal
- **Unified Actions dropdowns** in header, selection toolbar, and per-item
- **Folder items** visually distinct from files (amber color) but following same layout patterns
- **Selection support** for both files and folders with context-aware actions
- **Empty folder state** with upload prompt and dashed border
- **Delete confirmation** with recursive content summary (file count, folder count, total size)
- **Mobile-friendly** responsive design maintaining current patterns

---

## Related Documentation

- [FILELIST_EXPANSION.md](./FILELIST_EXPANSION.md) - Technical expansion details for FileList component
- [IMPLEMENTATION_PLAN.md](./IMPLEMENTATION_PLAN.md) - Full implementation plan with all phases
- [PROGRESS.md](./PROGRESS.md) - Current status tracking
