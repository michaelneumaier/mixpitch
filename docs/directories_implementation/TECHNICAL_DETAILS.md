# Technical Implementation Details

This document contains detailed code snippets and implementation specifics.

---

## Folder Model

### Complete Implementation

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Folder extends Model
{
    use SoftDeletes;

    public const MAX_DEPTH = 10;

    protected $fillable = [
        'name',
        'parent_id',
        'folderable_type',
        'folderable_id',
        'path',
        'depth',
        'created_by',
    ];

    protected $casts = [
        'depth' => 'integer',
    ];

    // ==================
    // RELATIONSHIPS
    // ==================

    /**
     * Get the parent model (Project or Pitch)
     */
    public function folderable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the parent folder
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * Get immediate child folders
     */
    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id')->orderBy('name');
    }

    /**
     * Get all descendant folders (uses materialized path)
     */
    public function allDescendants(): HasMany
    {
        return $this->hasMany(Folder::class, 'folderable_id')
            ->where('folderable_type', $this->folderable_type)
            ->where('path', 'like', $this->path . '%')
            ->where('id', '!=', $this->id);
    }

    /**
     * Get project files in this folder
     */
    public function projectFiles(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    /**
     * Get pitch files in this folder
     */
    public function pitchFiles(): HasMany
    {
        return $this->hasMany(PitchFile::class);
    }

    /**
     * Get the user who created this folder
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================
    // PATH METHODS
    // ==================

    /**
     * Get the full path as a readable string (e.g., "Stems/Drums/Kick")
     */
    public function getFullPath(): string
    {
        $ancestors = $this->ancestors();
        $names = $ancestors->pluck('name')->push($this->name);
        return $names->implode('/');
    }

    /**
     * Get all ancestor folders in order from root to parent
     */
    public function ancestors(): Collection
    {
        if (!$this->parent_id) {
            return collect();
        }

        // Extract IDs from materialized path
        $ids = array_filter(explode('/', trim($this->path, '/')));
        array_pop($ids); // Remove self

        if (empty($ids)) {
            return collect();
        }

        // Fetch and order by path position
        return static::whereIn('id', $ids)
            ->get()
            ->sortBy(function ($folder) use ($ids) {
                return array_search($folder->id, $ids);
            })
            ->values();
    }

    /**
     * Get breadcrumb data for navigation
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        foreach ($this->ancestors() as $ancestor) {
            $breadcrumbs[] = [
                'id' => $ancestor->id,
                'name' => $ancestor->name,
            ];
        }
        $breadcrumbs[] = [
            'id' => $this->id,
            'name' => $this->name,
        ];
        return $breadcrumbs;
    }

    // ==================
    // DEPTH METHODS
    // ==================

    /**
     * Check if this folder can have children (not at max depth)
     */
    public function canHaveChildren(): bool
    {
        return $this->depth < self::MAX_DEPTH;
    }

    /**
     * Build the materialized path for a new folder
     */
    public static function buildPath(int $id, ?string $parentPath = null): string
    {
        return ($parentPath ?? '/') . $id . '/';
    }

    // ==================
    // CONTENT METHODS
    // ==================

    /**
     * Get all files in this folder (regardless of type)
     */
    public function getFiles(): Collection
    {
        if ($this->folderable_type === Project::class) {
            return $this->projectFiles;
        }
        return $this->pitchFiles;
    }

    /**
     * Get all files in this folder and all subfolders
     */
    public function getAllFiles(): Collection
    {
        $folderIds = $this->allDescendants()->pluck('id')->push($this->id);

        if ($this->folderable_type === Project::class) {
            return ProjectFile::whereIn('folder_id', $folderIds)->get();
        }

        return PitchFile::whereIn('folder_id', $folderIds)->get();
    }

    /**
     * Get counts for display
     */
    public function getCountsAttribute(): array
    {
        return [
            'folders' => $this->children()->count(),
            'files' => $this->getFiles()->count(),
        ];
    }

    // ==================
    // VALIDATION
    // ==================

    /**
     * Check if moving to target would create a cycle
     */
    public function wouldCreateCycle(?Folder $target): bool
    {
        if (!$target) {
            return false;
        }

        // Can't move to self
        if ($target->id === $this->id) {
            return true;
        }

        // Can't move to descendant
        return str_starts_with($target->path, $this->path);
    }
}
```

---

## Folder Service

### Complete Implementation

```php
<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\ProjectFile;
use App\Models\PitchFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FolderService
{
    /**
     * Create a new folder
     */
    public function createFolder(
        Model $parent,
        string $name,
        ?Folder $parentFolder = null,
        ?User $createdBy = null
    ): Folder {
        // Validate depth
        $depth = $parentFolder ? $parentFolder->depth + 1 : 1;
        if ($depth > Folder::MAX_DEPTH) {
            throw new \InvalidArgumentException(
                "Maximum folder depth of " . Folder::MAX_DEPTH . " exceeded."
            );
        }

        // Validate unique name at this level
        $this->validateUniqueName($parent, $name, $parentFolder);

        return DB::transaction(function () use ($parent, $name, $parentFolder, $depth, $createdBy) {
            $folder = new Folder([
                'name' => $name,
                'parent_id' => $parentFolder?->id,
                'folderable_type' => get_class($parent),
                'folderable_id' => $parent->id,
                'depth' => $depth,
                'created_by' => $createdBy?->id,
                'path' => '', // Temporary
            ]);

            $folder->save();

            // Update path with actual ID
            $folder->path = Folder::buildPath($folder->id, $parentFolder?->path);
            $folder->save();

            return $folder;
        });
    }

    /**
     * Rename a folder
     */
    public function renameFolder(Folder $folder, string $newName): Folder
    {
        $this->validateUniqueName(
            $folder->folderable,
            $newName,
            $folder->parent,
            $folder->id
        );

        $folder->name = $newName;
        $folder->save();

        return $folder;
    }

    /**
     * Move a folder to a new parent
     */
    public function moveFolder(Folder $folder, ?Folder $newParent = null): Folder
    {
        // Validate no cycle
        if ($folder->wouldCreateCycle($newParent)) {
            throw new \InvalidArgumentException(
                "Cannot move a folder into itself or its descendant."
            );
        }

        // Calculate new depth
        $newDepth = $newParent ? $newParent->depth + 1 : 1;
        $depthChange = $newDepth - $folder->depth;

        // Check max depth for all descendants
        $maxDescendantDepth = Folder::where('path', 'like', $folder->path . '%')
            ->max('depth') ?? $folder->depth;

        if ($maxDescendantDepth + $depthChange > Folder::MAX_DEPTH) {
            throw new \InvalidArgumentException(
                "Moving this folder would exceed the maximum depth of " . Folder::MAX_DEPTH . "."
            );
        }

        // Validate unique name at destination
        $this->validateUniqueName(
            $folder->folderable,
            $folder->name,
            $newParent,
            $folder->id
        );

        return DB::transaction(function () use ($folder, $newParent, $newDepth, $depthChange) {
            $oldPath = $folder->path;
            $newPath = Folder::buildPath($folder->id, $newParent?->path);

            // Update this folder
            $folder->parent_id = $newParent?->id;
            $folder->depth = $newDepth;
            $folder->path = $newPath;
            $folder->save();

            // Update all descendants
            Folder::where('path', 'like', $oldPath . '%')
                ->where('id', '!=', $folder->id)
                ->update([
                    'depth' => DB::raw("depth + ({$depthChange})"),
                    'path' => DB::raw("REPLACE(path, '{$oldPath}', '{$newPath}')"),
                ]);

            return $folder->fresh();
        });
    }

    /**
     * Delete a folder
     */
    public function deleteFolder(Folder $folder, bool $deleteContents = false): void
    {
        DB::transaction(function () use ($folder, $deleteContents) {
            // Get all folder IDs (this folder + descendants)
            $folderIds = Folder::where('path', 'like', $folder->path . '%')
                ->pluck('id')
                ->push($folder->id);

            if ($deleteContents) {
                // Delete all files in these folders
                if ($folder->folderable_type === Project::class) {
                    ProjectFile::whereIn('folder_id', $folderIds)->delete();
                } else {
                    PitchFile::whereIn('folder_id', $folderIds)->delete();
                }
            } else {
                // Move files to root (folder_id = null)
                if ($folder->folderable_type === Project::class) {
                    ProjectFile::whereIn('folder_id', $folderIds)
                        ->update(['folder_id' => null]);
                } else {
                    PitchFile::whereIn('folder_id', $folderIds)
                        ->update(['folder_id' => null]);
                }
            }

            // Delete folder (cascade deletes children via FK)
            $folder->delete();
        });
    }

    /**
     * Move a file to a folder
     */
    public function moveFileToFolder($file, ?Folder $folder): void
    {
        // Validate folder belongs to same parent
        if ($folder) {
            $expectedType = $file instanceof ProjectFile ? Project::class : Pitch::class;
            $expectedId = $file instanceof ProjectFile ? $file->project_id : $file->pitch_id;

            if ($folder->folderable_type !== $expectedType ||
                $folder->folderable_id !== $expectedId) {
                throw new \InvalidArgumentException(
                    "Folder does not belong to the same " . class_basename($expectedType) . "."
                );
            }
        }

        $file->folder_id = $folder?->id;
        $file->save();
    }

    /**
     * Create folder structure from relative paths (for folder uploads)
     */
    public function createFolderStructure(
        Model $parent,
        array $relativePaths,
        ?User $createdBy = null
    ): array {
        $folderCache = []; // "path/string" => Folder

        return DB::transaction(function () use ($parent, $relativePaths, $createdBy, &$folderCache) {
            foreach ($relativePaths as $relativePath) {
                $parts = explode('/', dirname($relativePath));
                $parts = array_filter($parts, fn($p) => $p !== '.' && $p !== '');

                if (empty($parts)) {
                    continue;
                }

                $currentPath = '';
                $currentParent = null;

                foreach ($parts as $folderName) {
                    $currentPath .= '/' . $folderName;

                    if (isset($folderCache[$currentPath])) {
                        $currentParent = $folderCache[$currentPath];
                        continue;
                    }

                    // Try to find existing folder
                    $query = Folder::where('folderable_type', get_class($parent))
                        ->where('folderable_id', $parent->id)
                        ->where('name', $folderName);

                    if ($currentParent) {
                        $query->where('parent_id', $currentParent->id);
                    } else {
                        $query->whereNull('parent_id');
                    }

                    $folder = $query->first();

                    if (!$folder) {
                        $folder = $this->createFolder($parent, $folderName, $currentParent, $createdBy);
                    }

                    $folderCache[$currentPath] = $folder;
                    $currentParent = $folder;
                }
            }

            return $folderCache;
        });
    }

    /**
     * Get folder by path string
     */
    public function getFolderByPath(Model $parent, string $pathString): ?Folder
    {
        $parts = array_filter(explode('/', $pathString), fn($p) => $p !== '');

        if (empty($parts)) {
            return null;
        }

        $currentFolder = null;

        foreach ($parts as $folderName) {
            $query = Folder::where('folderable_type', get_class($parent))
                ->where('folderable_id', $parent->id)
                ->where('name', $folderName);

            if ($currentFolder) {
                $query->where('parent_id', $currentFolder->id);
            } else {
                $query->whereNull('parent_id');
            }

            $currentFolder = $query->first();

            if (!$currentFolder) {
                return null;
            }
        }

        return $currentFolder;
    }

    /**
     * Get contents of a folder (or root)
     */
    public function getContents(Model $parent, ?Folder $folder = null): array
    {
        $folderId = $folder?->id;

        // Get folders
        $folders = $parent->folders()
            ->when($folderId, fn($q) => $q->where('parent_id', $folderId))
            ->when(!$folderId, fn($q) => $q->whereNull('parent_id'))
            ->orderBy('name')
            ->get();

        // Get files
        $files = $parent->files()
            ->when($folderId, fn($q) => $q->where('folder_id', $folderId))
            ->when(!$folderId, fn($q) => $q->whereNull('folder_id'))
            ->orderBy('file_name')
            ->get();

        return [
            'folders' => $folders,
            'files' => $files,
        ];
    }

    /**
     * Validate folder name is unique at given level
     */
    protected function validateUniqueName(
        Model $parent,
        string $name,
        ?Folder $parentFolder,
        ?int $excludeId = null
    ): void {
        $query = Folder::where('folderable_type', get_class($parent))
            ->where('folderable_id', $parent->id)
            ->where('name', $name);

        if ($parentFolder) {
            $query->where('parent_id', $parentFolder->id);
        } else {
            $query->whereNull('parent_id');
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new \InvalidArgumentException(
                "A folder named '{$name}' already exists in this location."
            );
        }
    }
}
```

---

## Folder Policy

### Complete Implementation

```php
<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;

class FolderPolicy
{
    /**
     * Determine if user can view the folder
     */
    public function view(User $user, Folder $folder): bool
    {
        return $this->ownsParent($user, $folder);
    }

    /**
     * Determine if user can create folders in the parent
     */
    public function create(User $user, Folder $folder): bool
    {
        return $this->ownsParent($user, $folder) && $folder->canHaveChildren();
    }

    /**
     * Determine if user can create folders in a project
     */
    public function createInProject(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    /**
     * Determine if user can create folders in a pitch
     */
    public function createInPitch(User $user, Pitch $pitch): bool
    {
        return $user->id === $pitch->user_id;
    }

    /**
     * Determine if user can update the folder
     */
    public function update(User $user, Folder $folder): bool
    {
        return $this->ownsParent($user, $folder);
    }

    /**
     * Determine if user can delete the folder
     */
    public function delete(User $user, Folder $folder): bool
    {
        return $this->ownsParent($user, $folder);
    }

    /**
     * Determine if user can move the folder
     */
    public function move(User $user, Folder $folder): bool
    {
        return $this->ownsParent($user, $folder);
    }

    /**
     * Check if user owns the parent (Project or Pitch)
     */
    protected function ownsParent(User $user, Folder $folder): bool
    {
        if ($folder->folderable_type === Project::class) {
            $project = Project::find($folder->folderable_id);
            return $project && $user->id === $project->user_id;
        }

        if ($folder->folderable_type === Pitch::class) {
            $pitch = Pitch::find($folder->folderable_id);
            return $pitch && $user->id === $pitch->user_id;
        }

        return false;
    }
}
```

---

## Migration Examples

### Create Folders Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('folders')
                ->cascadeOnDelete();
            $table->morphs('folderable');
            $table->string('path', 500);
            $table->unsignedTinyInteger('depth')->default(1);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['folderable_type', 'folderable_id', 'parent_id']);
            $table->index('path');

            // Unique constraint: no duplicate names in same location
            $table->unique(
                ['folderable_type', 'folderable_id', 'parent_id', 'name'],
                'folders_unique_name_in_parent'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
```

### Add folder_id to project_files

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->foreignId('folder_id')
                ->nullable()
                ->after('project_id')
                ->constrained('folders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('folder_id');
        });
    }
};
```

### Add folder_id to pitch_files

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pitch_files', function (Blueprint $table) {
            $table->foreignId('folder_id')
                ->nullable()
                ->after('pitch_id')
                ->constrained('folders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pitch_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('folder_id');
        });
    }
};
```
