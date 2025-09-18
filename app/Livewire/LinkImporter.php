<?php

namespace App\Livewire;

use App\Models\LinkImport;
use App\Models\Project;
use App\Services\LinkImportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class LinkImporter extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public string $importUrl = '';

    public ?LinkImport $activeImport = null;

    public array $importProgress = [
        'active' => false,
        'completed' => 0,
        'total' => 0,
        'currentFile' => '',
    ];

    public int $refreshKey = 0;

    public bool $showModal = false;

    protected $rules = [
        'importUrl' => 'required|url|max:2000',
    ];

    protected $messages = [
        'importUrl.required' => 'Please enter a sharing link URL.',
        'importUrl.url' => 'Please enter a valid URL.',
        'importUrl.max' => 'URL is too long.',
    ];

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->checkActiveImport();
    }

    public function render()
    {
        return view('livewire.link-importer');
    }

    /**
     * Show the import modal.
     */
    public function showImportModal()
    {
        $this->authorize('update', $this->project);
        $this->showModal = true;
        $this->importUrl = '';
        $this->resetValidation();
    }

    /**
     * Hide the import modal.
     */
    public function hideImportModal()
    {
        $this->showModal = false;
        $this->importUrl = '';
        $this->resetValidation();
    }

    /**
     * Import files from the provided link.
     */
    public function importFromLink(LinkImportService $service)
    {
        $this->validate();

        try {
            $this->activeImport = $service->createImport(
                $this->project,
                $this->importUrl,
                auth()->user()
            );

            $this->importUrl = '';
            $this->importProgress['active'] = true;
            $this->hideImportModal();

            Toaster::success('Import started! We\'ll process your files in the background.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('importUrl', $e->validator->errors()->first('url'));
        } catch (\Exception $e) {
            Toaster::error('Failed to start import: '.$e->getMessage());
        }
    }

    /**
     * Handle real-time import progress updates.
     */
    #[On('echo:project.{project.id},LinkImportUpdated')]
    public function handleImportUpdate($data)
    {
        if ($this->activeImport && $this->activeImport->id == $data['import_id']) {
            $this->activeImport->refresh();
            $this->updateProgress();
        }
    }

    /**
     * Handle import completion.
     */
    #[On('echo:project.{project.id},LinkImportCompleted')]
    public function handleImportCompleted($data)
    {
        if ($this->activeImport && $this->activeImport->id == $data['import_id']) {
            $this->activeImport->refresh();
            $this->importProgress['active'] = false;

            $count = count($this->activeImport->imported_files ?? []);
            $fileWord = $count === 1 ? 'file' : 'files';
            Toaster::success("Import completed! {$count} {$fileWord} added to your project.");

            $this->activeImport = null;
            $this->dispatch('refreshClientFiles');
        }
    }

    /**
     * Cancel the active import.
     */
    public function cancelImport(LinkImportService $service)
    {
        if (! $this->activeImport) {
            return;
        }

        try {
            $service->cancelImport($this->activeImport, auth()->user());
            $this->activeImport = null;
            $this->importProgress['active'] = false;

            Toaster::info('Import canceled successfully.');
        } catch (\Exception $e) {
            Toaster::error('Failed to cancel import: '.$e->getMessage());
        }
    }

    /**
     * Check for any active imports on mount.
     */
    protected function checkActiveImport()
    {
        $this->activeImport = LinkImport::where('project_id', $this->project->id)
            ->whereIn('status', [
                LinkImport::STATUS_PENDING,
                LinkImport::STATUS_ANALYZING,
                LinkImport::STATUS_IMPORTING,
            ])
            ->latest()
            ->first();

        if ($this->activeImport) {
            $this->importProgress['active'] = true;
            $this->updateProgress();
        }
    }

    /**
     * Poll for import status updates (called by wire:poll).
     */
    public function pollImportStatus()
    {
        if (! $this->activeImport) {
            return;
        }

        // Force a fresh query to ensure we get the latest data
        $this->activeImport = LinkImport::find($this->activeImport->id);

        if (! $this->activeImport) {
            return;
        }

        $this->updateProgress();
        $this->refreshKey++; // Force component to re-render

        // Check if import is completed
        if ($this->activeImport->status === LinkImport::STATUS_COMPLETED) {
            $this->importProgress['active'] = false;

            $count = count($this->activeImport->imported_files ?? []);
            $fileWord = $count === 1 ? 'file' : 'files';
            Toaster::success("Import completed! {$count} {$fileWord} added to your project.");

            // Dispatch event to refresh the parent component
            $this->dispatch('refreshClientFiles');

            $this->activeImport = null;
        } elseif ($this->activeImport->status === LinkImport::STATUS_FAILED) {
            $this->importProgress['active'] = false;

            $errorMessage = $this->activeImport->error_message ?? 'Import failed due to an unknown error.';
            Toaster::error("Import failed: {$errorMessage}");

            $this->activeImport = null;
        }
    }

    /**
     * Update the progress tracking data.
     */
    protected function updateProgress()
    {
        if (! $this->activeImport) {
            return;
        }

        $metadata = $this->activeImport->metadata ?? [];
        $progress = $metadata['progress'] ?? [];

        $this->importProgress = [
            'active' => $this->activeImport->isInProgress(),
            'completed' => $progress['completed'] ?? 0,
            'total' => $progress['total'] ?? count($this->activeImport->detected_files ?? []),
            'currentFile' => $progress['current_file'] ?? '',
        ];
    }

    /**
     * Get the progress percentage for display.
     */
    public function getProgressPercentageProperty(): int
    {
        if (! $this->activeImport) {
            return 0;
        }

        // Calculate progress based on the metadata
        $metadata = $this->activeImport->metadata ?? [];
        $progress = $metadata['progress'] ?? [];

        $completed = $progress['completed'] ?? 0;
        $total = $progress['total'] ?? 0;

        if ($total === 0) {
            // If we're still analyzing, show some progress
            if ($this->activeImport->status === LinkImport::STATUS_ANALYZING) {
                return 10;
            }

            return 0;
        }

        return (int) round(($completed / $total) * 100);
    }

    /**
     * Get supported domains for display.
     */
    public function getSupportedDomainsProperty(): string
    {
        $domains = config('linkimport.allowed_domains', []);

        return implode(', ', array_map(fn ($domain) => ucfirst(str_replace('.com', '', $domain)), $domains));
    }

    /**
     * Check if there are any import errors.
     */
    public function getHasImportErrorsProperty(): bool
    {
        if (! $this->activeImport) {
            return false;
        }

        $metadata = $this->activeImport->metadata ?? [];

        return ! empty($metadata['file_errors']);
    }

    /**
     * Get import error details.
     */
    public function getImportErrorsProperty(): array
    {
        if (! $this->activeImport) {
            return [];
        }

        $metadata = $this->activeImport->metadata ?? [];

        return $metadata['file_errors'] ?? [];
    }
}
