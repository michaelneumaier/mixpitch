<?php

namespace App\Livewire;

use App\Exceptions\GoogleDrive\GoogleDriveAuthException;
use App\Exceptions\GoogleDrive\GoogleDriveFileException;
use App\Exceptions\GoogleDrive\GoogleDriveQuotaException;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

class GoogleDriveFileBrowser extends Component
{
    use WithPagination;

    public array $files = [];
    public array $connectionStatus = [];
    public bool $isLoading = false;
    public bool $showImportModal = false;
    public array $selectedFile = [];
    public ?string $currentFolderId = null;
    public ?string $nextPageToken = null;
    public bool $hasMoreFiles = false;
    public string $searchQuery = '';
    public int $filesPerPage = 20;

    protected GoogleDriveService $googleDriveService;

    protected $listeners = [
        'refreshConnection' => 'checkConnectionStatus',
        'importComplete' => 'handleImportComplete',
    ];

    public function boot()
    {
        $this->googleDriveService = app(GoogleDriveService::class);
    }

    public function mount()
    {
        $this->checkConnectionStatus();
        if ($this->connectionStatus['connected']) {
            $this->loadFiles();
        }
    }

    public function checkConnectionStatus()
    {
        try {
            $this->connectionStatus = $this->googleDriveService->getConnectionStatus(Auth::user());
        } catch (\Exception $e) {
            $this->connectionStatus = ['connected' => false, 'error' => $e->getMessage()];
        }
    }

    public function loadFiles($resetPagination = true)
    {
        if (!$this->connectionStatus['connected']) {
            return;
        }

        $this->isLoading = true;

        try {
            $pageToken = $resetPagination ? null : $this->nextPageToken;
            
            $result = $this->googleDriveService->listFiles(
                Auth::user(),
                $this->currentFolderId,
                $this->filesPerPage,
                $pageToken
            );

            if ($resetPagination) {
                $this->files = $result['files'];
            } else {
                $this->files = array_merge($this->files, $result['files']);
            }

            $this->nextPageToken = $result['nextPageToken'] ?? null;
            $this->hasMoreFiles = !is_null($this->nextPageToken);

        } catch (GoogleDriveAuthException $e) {
            $this->connectionStatus['connected'] = false;
            $this->connectionStatus['needs_reauth'] = true;
            Toaster::error('Google Drive authentication expired. Please reconnect your account.');
        } catch (GoogleDriveFileException $e) {
            Toaster::error('Failed to load Google Drive files: ' . $e->getMessage());
        } catch (\Exception $e) {
            Toaster::error('An unexpected error occurred while loading files.');
        } finally {
            $this->isLoading = false;
        }
    }

    public function loadMoreFiles()
    {
        if ($this->hasMoreFiles && !$this->isLoading) {
            $this->loadFiles(false);
        }
    }

    public function refreshFiles()
    {
        $this->nextPageToken = null;
        $this->hasMoreFiles = false;
        $this->loadFiles();
    }

    public function navigateToFolder(?string $folderId = null)
    {
        $this->currentFolderId = $folderId;
        $this->nextPageToken = null;
        $this->hasMoreFiles = false;
        $this->loadFiles();
    }

    public function selectFileForImport(array $file)
    {
        if (!$file['isAudio']) {
            Toaster::warning('Only audio files can be imported.');
            return;
        }

        $this->selectedFile = $file;
        $this->showImportModal = true;
    }

    public function importSelectedFile()
    {
        if (empty($this->selectedFile)) {
            return;
        }

        try {
            $this->isLoading = true;

            $result = $this->googleDriveService->downloadFile(
                Auth::user(),
                $this->selectedFile['id']
            );

            Toaster::success('File imported successfully from Google Drive!');
            
            $this->dispatch('fileImported', [
                'fileName' => $result['name'],
                'localPath' => $result['localPath'],
                'temporaryUrl' => $result['temporaryUrl'],
                'size' => $result['size'],
                'mimeType' => $result['mimeType'],
            ]);

            $this->closeImportModal();

        } catch (GoogleDriveAuthException $e) {
            $this->connectionStatus['connected'] = false;
            $this->connectionStatus['needs_reauth'] = true;
            Toaster::error('Google Drive authentication expired. Please reconnect your account.');
        } catch (GoogleDriveQuotaException $e) {
            Toaster::error('Insufficient storage space to import this file.');
        } catch (GoogleDriveFileException $e) {
            Toaster::error('Failed to import file: ' . $e->getMessage());
        } catch (\Exception $e) {
            Toaster::error('An unexpected error occurred while importing the file.');
        } finally {
            $this->isLoading = false;
        }
    }

    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->selectedFile = [];
    }

    public function handleImportComplete()
    {
        $this->closeImportModal();
        Toaster::success('File has been processed and is ready to use!');
    }

    public function updatedSearchQuery()
    {
        // Note: Google Drive API doesn't easily support search without complex query building
        // For now, we'll do client-side filtering
        // In a production app, you might want to implement server-side search
        $this->filterFiles();
    }

    protected function filterFiles()
    {
        if (empty($this->searchQuery)) {
            $this->loadFiles();
            return;
        }

        // Simple client-side filtering
        $this->files = array_filter($this->files, function ($file) {
            return str_contains(strtolower($file['name']), strtolower($this->searchQuery));
        });
    }

    public function getConnectionUrl()
    {
        return route('integrations.google-drive.connect');
    }

    public function render()
    {
        return view('livewire.google-drive-file-browser', [
            'files' => $this->files,
            'isConnected' => $this->connectionStatus['connected'] ?? false,
            'needsReauth' => $this->connectionStatus['needs_reauth'] ?? false,
            'connectionError' => $this->connectionStatus['error'] ?? null,
        ]);
    }
}