# Directory Support Implementation

This directory contains the complete implementation plan and progress tracking for adding folder/directory support to MixPitch.

## Quick Links

- [Implementation Plan](./IMPLEMENTATION_PLAN.md) - Full technical plan with phases
- [Progress Tracker](./PROGRESS.md) - Current status of each phase and task
- [Impact Analysis](./IMPACT_ANALYSIS.md) - All files that need modification
- [Technical Details](./TECHNICAL_DETAILS.md) - Code snippets and implementation specifics
- [FileList Expansion](./FILELIST_EXPANSION.md) - Details on expanding the FileList component

## Overview

### What We're Building
- Folder/directory support for both ProjectFiles and PitchFiles
- Up to 10 levels of nested folders
- Full CRUD operations (create, rename, move, delete folders)
- Folder upload with structure preservation (webkitdirectory)
- Client portal with folder browsing (expanded by default)

### Key Design Decisions

1. **Single Polymorphic Folder Model** - One `Folder` model works with both Project and Pitch
2. **Database-Only Structure** - S3 keys stay flat, folder hierarchy is in database only
3. **Expand FileList Component** - No new FolderTree component, enhance existing FileList
4. **Hybrid Folder Storage** - `parent_id` (adjacency list) + `path` (materialized path)

### Implementation Phases

0. **FileList Component Refactoring** - Split monolithic FileList into smaller components FIRST
1. **Database & Model Foundation** - Migrations, Folder model, relationships
2. **FileManagementService Integration** - Folder-aware file operations
3. **Expand FileList Component** - Folder navigation, CRUD, move operations (+ FolderItem)
4. **Parent Component Integration** - Update ManageProject, ManagePitch, etc.
5. **Folder Upload Support** - webkitdirectory, relative paths
6. **Client Portal Integration** - Folder browsing for clients
7. **Polish & Testing** - Update tests, factories, edge cases

### Phase 0: Component Architecture

Before adding folders, the FileList component (~2400 lines) will be split into:

| Component | Lines | Purpose |
|-----------|-------|---------|
| **FileList** | ~500 | Main coordinator, file collection management |
| **FileItem** | ~360 | Individual file row, actions dropdown |
| **FileComments** | ~600 | Comments system for each file |
| **FileBulkActions** | ~380 | Bulk selection and actions toolbar |

This creates a clean foundation for adding **FolderItem** in Phase 3.

## For Agents Continuing This Work

1. Check [PROGRESS.md](./PROGRESS.md) for current status
2. Read the relevant section in [IMPLEMENTATION_PLAN.md](./IMPLEMENTATION_PLAN.md)
3. Reference [TECHNICAL_DETAILS.md](./TECHNICAL_DETAILS.md) for code snippets
4. Update PROGRESS.md as you complete tasks

## Critical Notes

- **PitchFile.parent_file_id is for VERSIONING** - Don't confuse with folder structure
- **Existing files stay at root** - `folder_id = null` means root directory
- **Backwards compatible** - All existing functionality must continue to work
