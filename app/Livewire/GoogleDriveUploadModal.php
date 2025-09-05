<?php

namespace App\Livewire;

use App\Services\GoogleDriveService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class GoogleDriveUploadModal extends Component
{
    public Model $model;

    // Google Drive connection state
    public bool $isConnected = false;

    public array $files = [];

    public string $currentFolder = 'root';

    public string $searchQuery = '';

    public bool $loading = false;

    public ?array $selectedFile = null;

    public bool $importing = false;

    // Breadcrumb navigation
    public array $breadcrumbs = [];

    protected GoogleDriveService $googleDriveService;

    public function boot(GoogleDriveService $googleDriveService): void
    {
        $this->googleDriveService = $googleDriveService;
    }

    public function mount(Model $model): void
    {
        $this->model = $model;
        $this->checkConnectionStatus();

        if ($this->isConnected) {
            $this->loadFiles();
        }
    }

    public function closeModal(): void
    {
        $this->selectedFile = null;
        $this->searchQuery = '';
        $this->currentFolder = 'root';
        $this->breadcrumbs = [];
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

    public function loadFiles(): void
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

            $this->files = $result['files'];
            $this->breadcrumbs = $result['breadcrumbs'];

        } catch (\Exception $e) {
            Log::error('Failed to load Google Drive files', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'folder' => $this->currentFolder,
                'search' => $this->searchQuery,
            ]);

            Toaster::error('Failed to load Google Drive files. Please try again.');
        } finally {
            $this->loading = false;
        }
    }

    public function searchFiles(): void
    {
        $this->currentFolder = 'root';
        $this->loadFiles();
    }

    public function navigateToFolder(string $folderId): void
    {
        $this->currentFolder = $folderId;
        $this->searchQuery = '';
        $this->loadFiles();
    }

    public function selectFile(array $file): void
    {
        $this->selectedFile = $file;
    }

    public function importSelectedFile(): void
    {
        if (! $this->selectedFile) {
            Toaster::error('Please select a file to import.');

            return;
        }

        $this->importing = true;

        try {
            $result = $this->googleDriveService->importFileToModel(
                Auth::user(),
                $this->selectedFile['id'],
                $this->model
            );

            if ($result['success']) {
                Toaster::success("File '{$this->selectedFile['name']}' imported successfully!");

                // Dispatch event to refresh file lists
                $this->dispatch('filesUploaded', [
                    'count' => 1,
                    'model_type' => get_class($this->model),
                    'model_id' => $this->model->id,
                    'source' => 'google_drive',
                ]);

                $this->js('$flux.modal("google-drive-modal").close()');
                $this->dispatch('close-modal');
            } else {
                throw new \Exception($result['error'] ?? 'Import failed');
            }
        } catch (\Exception $e) {
            Log::error('Failed to import Google Drive file', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'file_id' => $this->selectedFile['id'],
                'file_name' => $this->selectedFile['name'],
                'model_type' => get_class($this->model),
                'model_id' => $this->model->id,
            ]);

            Toaster::error('Failed to import file: '.$e->getMessage());
        } finally {
            $this->importing = false;
        }
    }

    public function render()
    {
        return view('livewire.google-drive-upload-modal');
    }
}
