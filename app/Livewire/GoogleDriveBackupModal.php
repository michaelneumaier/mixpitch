<?php

namespace App\Livewire;

use App\Services\GoogleDriveService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class GoogleDriveBackupModal extends Component
{
    public Model $model;

    // Google Drive connection state
    public bool $isConnected = false;

    public array $folders = [];

    public string $currentFolder = 'root';

    public string $searchQuery = '';

    public bool $loading = false;

    public ?array $selectedFolder = null;

    public bool $backingUp = false;

    // Files to backup
    public array $filesToBackup = [];

    public array $selectedFiles = [];

    // Breadcrumb navigation
    public array $breadcrumbs = [];

    // Folder creation
    public bool $showCreateFolderForm = false;

    public string $newFolderName = '';

    public bool $creatingFolder = false;

    // Mobile tab state
    public string $activeTab = 'files';

    protected GoogleDriveService $googleDriveService;

    protected $rules = [
        'newFolderName' => 'required|string|min:1|max:100',
        'activeTab' => 'required|string|in:files,destination',
    ];

    public function boot(GoogleDriveService $googleDriveService): void
    {
        $this->googleDriveService = $googleDriveService;
    }

    public function mount(Model $model): void
    {
        $this->model = $model;
        $this->loadFilesToBackup();
        $this->checkConnectionStatus();

        if ($this->isConnected) {
            $this->loadFolders();
        }
    }

    protected function loadFilesToBackup(): void
    {
        // Get files from the model (Project or Pitch files)
        if ($this->model instanceof \App\Models\Project) {
            $this->filesToBackup = $this->model->files()
                ->get()
                ->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'name' => $file->file_name,
                        'size' => $file->size,
                        'formatted_size' => $file->formatted_size,
                        'type' => 'project_file',
                        'created_at' => $file->created_at->format('M d, Y'),
                    ];
                })
                ->toArray();
        } elseif ($this->model instanceof \App\Models\Pitch) {
            $this->filesToBackup = $this->model->files()
                ->get()
                ->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'name' => $file->file_name,
                        'size' => $file->size,
                        'formatted_size' => $file->formatted_size,
                        'type' => 'pitch_file',
                        'created_at' => $file->created_at->format('M d, Y'),
                    ];
                })
                ->toArray();
        }

        // Select all files by default
        $this->selectedFiles = array_column($this->filesToBackup, 'id');
    }

    public function closeModal(): void
    {
        $this->selectedFolder = null;
        $this->searchQuery = '';
        $this->currentFolder = 'root';
        $this->breadcrumbs = [];
        $this->selectedFiles = array_column($this->filesToBackup, 'id');
        $this->activeTab = 'files';
        $this->dispatch('close-modal');
    }

    public function connectGoogleDrive(): void
    {
        try {
            $authUrl = $this->googleDriveService->getAuthorizationUrl(Auth::user());
            $this->redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('Failed to initiate Google Drive connection', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            Toaster::error('Failed to connect to Google Drive. Please try again.');
        }
    }

    public function checkConnectionStatus(): void
    {
        $this->isConnected = $this->googleDriveService->isConnected(Auth::user());
    }

    public function loadFolders(): void
    {
        if (! $this->isConnected) {
            return;
        }

        $this->loading = true;

        try {
            $result = $this->googleDriveService->listFiles(
                Auth::user(),
                $this->currentFolder,
                $this->searchQuery
            );

            // Filter to only show folders for backup destination selection
            $this->folders = array_filter($result['files'], function ($file) {
                return $file['mimeType'] === 'application/vnd.google-apps.folder';
            });

            $this->breadcrumbs = $result['breadcrumbs'];

        } catch (\Exception $e) {
            Log::error('Failed to load Google Drive folders', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'folder' => $this->currentFolder,
                'search' => $this->searchQuery,
            ]);

            Toaster::error('Failed to load Google Drive folders. Please try again.');
        } finally {
            $this->loading = false;
        }
    }

    public function searchFolders(): void
    {
        $this->currentFolder = 'root';
        $this->loadFolders();
    }

    public function navigateToFolder(string $folderId): void
    {
        $this->currentFolder = $folderId;
        $this->searchQuery = '';
        $this->loadFolders();
    }

    public function selectFolder(array $folder): void
    {
        $this->selectedFolder = $folder;
    }

    public function selectCurrentFolder(): void
    {
        // Use the current folder as the selected destination
        $currentFolderInfo = [
            'id' => $this->currentFolder,
            'name' => $this->breadcrumbs ? end($this->breadcrumbs)['name'] : 'My Drive',
        ];
        $this->selectedFolder = $currentFolderInfo;
    }

    public function toggleFileSelection(int $fileId): void
    {
        if (in_array($fileId, $this->selectedFiles)) {
            $this->selectedFiles = array_values(array_diff($this->selectedFiles, [$fileId]));
        } else {
            $this->selectedFiles[] = $fileId;
        }
    }

    public function selectAllFiles(): void
    {
        $this->selectedFiles = array_column($this->filesToBackup, 'id');
    }

    public function deselectAllFiles(): void
    {
        $this->selectedFiles = [];
    }

    public function backupSelectedFiles(): void
    {
        if (empty($this->selectedFiles)) {
            Toaster::error('Please select files to backup.');

            return;
        }

        if (! $this->selectedFolder) {
            Toaster::error('Please select a destination folder.');

            return;
        }

        $this->backingUp = true;

        try {
            $backedUpCount = 0;
            $errors = [];

            foreach ($this->selectedFiles as $fileId) {
                $file = collect($this->filesToBackup)->firstWhere('id', $fileId);

                if (! $file) {
                    continue;
                }

                try {
                    $result = $this->googleDriveService->backupFileToGoogleDrive(
                        Auth::user(),
                        $fileId,
                        $file['type'],
                        $this->selectedFolder['id']
                    );

                    if ($result['success']) {
                        $backedUpCount++;
                    } else {
                        $errors[] = "Failed to backup {$file['name']}: {$result['error']}";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to backup {$file['name']}: {$e->getMessage()}";
                }
            }

            if ($backedUpCount > 0) {
                $message = $backedUpCount === 1
                    ? '1 file backed up successfully to Google Drive!'
                    : "{$backedUpCount} files backed up successfully to Google Drive!";
                Toaster::success($message);
            }

            if (! empty($errors)) {
                foreach (array_slice($errors, 0, 3) as $error) {
                    Toaster::error($error);
                }
            }

            if ($backedUpCount === count($this->selectedFiles) && empty($errors)) {
                $this->js('$flux.modal("syncOptions").close()');
                $this->dispatch('close-modal');
            }

        } catch (\Exception $e) {
            Log::error('Failed to backup files to Google Drive', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'model_type' => get_class($this->model),
                'model_id' => $this->model->id,
                'selected_files' => $this->selectedFiles,
            ]);

            Toaster::error('Failed to backup files: '.$e->getMessage());
        } finally {
            $this->backingUp = false;
        }
    }

    public function toggleCreateFolderForm(): void
    {
        $this->showCreateFolderForm = true;
        $this->newFolderName = '';
    }

    public function cancelCreateFolder(): void
    {
        $this->showCreateFolderForm = false;
        $this->newFolderName = '';
        $this->resetErrorBag('newFolderName');
    }

    public function createFolder(): void
    {
        $this->validate(['newFolderName' => $this->rules['newFolderName']]);

        $this->creatingFolder = true;

        try {
            $result = $this->googleDriveService->createFolder(
                Auth::user(),
                $this->newFolderName,
                $this->currentFolder
            );

            if ($result['success']) {
                Toaster::success("Folder '{$this->newFolderName}' created successfully!");

                // Reset form
                $this->cancelCreateFolder();

                // Reload folders to show the new one
                $this->loadFolders();
            } else {
                throw new \Exception($result['error']);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create folder in Google Drive', [
                'user_id' => Auth::id(),
                'folder_name' => $this->newFolderName,
                'parent_folder_id' => $this->currentFolder,
                'error' => $e->getMessage(),
            ]);

            Toaster::error('Failed to create folder: '.$e->getMessage());
        } finally {
            $this->creatingFolder = false;
        }
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['files', 'destination'])) {
            $this->activeTab = $tab;
        }
    }

    public function continueToDestination(): void
    {
        $this->activeTab = 'destination';
    }

    public function render()
    {
        return view('livewire.google-drive-backup-modal');
    }
}
