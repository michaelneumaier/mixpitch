<?php

namespace App\Livewire;

use App\Models\GoogleDriveBackup;
use App\Models\Project;
use App\Services\GoogleDriveService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class GoogleDriveBackupHistoryModal extends Component
{
    use WithPagination;

    public ?Model $model = null; // Project or User

    public string $viewType = 'user'; // 'user' or 'project'

    public array $stats = [];

    public string $filterStatus = 'all';

    protected $queryString = [
        'filterStatus' => ['except' => 'all'],
        'page' => ['except' => 1],
    ];

    public function mount(?Model $model = null, string $viewType = 'user'): void
    {
        $this->model = $model ?? Auth::user();
        $this->viewType = $viewType;
        $this->loadStats();
    }

    public function loadStats(): void
    {
        $googleDriveService = app(GoogleDriveService::class);

        if ($this->viewType === 'project' && $this->model instanceof Project) {
            // Project-specific stats
            $backups = GoogleDriveBackup::forProject($this->model);
            $this->stats = [
                'total_backups' => $backups->count(),
                'successful_backups' => $backups->completed()->count(),
                'failed_backups' => $backups->failed()->count(),
                'pending_backups' => $backups->where('status', 'pending')->count(),
                'total_size_backed_up' => $backups->completed()->sum('file_size'),
                'latest_backup' => $backups->latest()->first()?->backed_up_at,
            ];
        } else {
            // User stats
            $this->stats = $googleDriveService->getBackupStats(Auth::user());
        }
    }

    public function setFilter(string $status): void
    {
        $this->filterStatus = $status;
        $this->resetPage();
    }

    public function getBackupsProperty()
    {
        $query = $this->viewType === 'project' && $this->model instanceof Project
            ? GoogleDriveBackup::forProject($this->model)->with(['user', 'file'])
            : GoogleDriveBackup::forUser(Auth::user())->with(['project', 'file']);

        // Apply status filter
        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        return $query->latest()->paginate(10);
    }

    public function retryBackup(int $backupId): void
    {
        $backup = GoogleDriveBackup::findOrFail($backupId);

        // Only allow retry of failed backups
        if (! $backup->hasFailed()) {
            return;
        }

        // Reset backup status to pending
        $backup->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        $this->dispatch('backup-retry-initiated', $backupId);
    }

    public function deleteBackupRecord(int $backupId): void
    {
        $backup = GoogleDriveBackup::findOrFail($backupId);

        // Only allow deletion by the backup owner
        if ($backup->user_id !== Auth::id()) {
            return;
        }

        $backup->delete();
        $this->loadStats();
    }

    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'completed' => 'text-green-600 bg-green-50 border-green-200',
            'failed' => 'text-red-600 bg-red-50 border-red-200',
            'pending' => 'text-yellow-600 bg-yellow-50 border-yellow-200',
            'deleted' => 'text-gray-600 bg-gray-50 border-gray-200',
            default => 'text-gray-600 bg-gray-50 border-gray-200',
        };
    }

    public function getStatusIcon(string $status): string
    {
        return match ($status) {
            'completed' => 'check-circle',
            'failed' => 'x-circle',
            'pending' => 'clock',
            'deleted' => 'trash',
            default => 'question-mark-circle',
        };
    }

    public function formatFileSize(?int $bytes): string
    {
        if (! $bytes) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return round($bytes / pow(1024, $power), 2).' '.$units[$power];
    }

    public function render()
    {
        return view('livewire.google-drive-backup-history-modal', [
            'backups' => $this->getBackupsProperty(),
        ]);
    }
}
