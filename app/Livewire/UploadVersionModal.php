<?php

namespace App\Livewire;

use App\Models\FileUploadSetting;
use App\Models\PitchFile;
use App\Services\FileManagementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class UploadVersionModal extends Component
{
    public ?PitchFile $file = null;

    public bool $isOpen = false;

    public array $uploadedFilesData = [];

    public bool $uploading = false;

    public bool $isUploadDisabled = true;

    public ?string $errorMessage = null;

    public int $uploadProgress = 0;

    #[On('openUploadVersionModal')]
    public function openModal(int $fileId): void
    {
        $this->file = PitchFile::with(['pitch'])->findOrFail($fileId);

        $this->authorize('uploadVersion', $this->file);

        $this->reset(['uploadedFilesData', 'uploading', 'errorMessage', 'uploadProgress', 'isUploadDisabled']);
        $this->isUploadDisabled = true;
        $this->isOpen = true;
    }

    /**
     * Handle file uploads from GlobalUploader
     */
    #[On('versionFileUploaded')]
    public function handleGlobalUploadSuccess(array $fileData): void
    {
        // $fileData is a single file object {name, key, size, type, meta}
        if (! empty($fileData)) {
            $this->uploadedFilesData = [$fileData];
            $this->isUploadDisabled = false;

            Toaster::success('File ready to upload!');
        }
    }

    public function createFileVersion(FileManagementService $fileManagementService, ?array $fileData = null): void
    {
        // Use parameter if provided (from JavaScript), otherwise fall back to property
        $dataToUse = $fileData ?? ($this->uploadedFilesData[0] ?? null);

        if (empty($dataToUse)) {
            $this->errorMessage = 'No file selected';
            Toaster::error('Please select a file first');

            return;
        }

        $this->uploading = true;
        $this->errorMessage = null;
        $this->uploadProgress = 10;

        try {

            $this->uploadProgress = 30;

            // Create new version using service with S3 key from GlobalUploader
            $newVersion = $fileManagementService->uploadFileVersion(
                $this->file,
                $dataToUse['key'],
                $dataToUse['name'],
                $dataToUse['size'],
                $dataToUse['type'],
                Auth::user()
            );

            $this->uploadProgress = 90;

            Toaster::success('New version uploaded successfully!');

            $this->uploadProgress = 100;

            // Dispatch event to refresh file lists (matches GlobalFileUploader pattern)
            $this->dispatch('filesUploaded', [
                'count' => 1,
                'source' => 'version_upload',
                'model_type' => 'App\\Models\\Pitch',
                'model_id' => $this->file->pitch_id,
            ]);

            // Close modal after brief delay
            $this->close();

        } catch (\Exception $e) {
            Log::error('Error uploading file version via modal', [
                'file_id' => $this->file->id,
                'error' => $e->getMessage(),
            ]);

            $this->errorMessage = $e->getMessage();
            Toaster::error('Failed to upload version: '.$e->getMessage());
        } finally {
            $this->uploading = false;
            $this->uploadProgress = 0;
        }
    }


    public function close(): void
    {
        $this->isOpen = false;
        $this->reset(['uploadedFilesData', 'uploading', 'errorMessage', 'uploadProgress', 'file', 'isUploadDisabled']);
        $this->isUploadDisabled = true;
    }

    public function render()
    {
        return view('livewire.upload-version-modal');
    }
}
