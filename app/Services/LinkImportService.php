<?php

namespace App\Services;

use App\Jobs\ProcessLinkImport;
use App\Models\LinkImport;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LinkImportService
{
    protected FileManagementService $fileManagementService;

    public function __construct(FileManagementService $fileManagementService)
    {
        $this->fileManagementService = $fileManagementService;
    }

    /**
     * Create a new link import and queue it for processing.
     */
    public function createImport(Project $project, string $url, User $user): LinkImport
    {
        // Authorization check
        if (! $user->can('update', $project)) {
            throw new \Exception('You are not authorized to import files for this project.');
        }

        // Validate the URL
        $this->validateUrl($url);

        // Check rate limits
        $this->checkRateLimits($project, $user);

        // Extract domain for tracking
        $domain = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        // Create the import record
        $import = LinkImport::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'source_url' => $url,
            'source_domain' => $domain,
            'status' => LinkImport::STATUS_PENDING,
            'detected_files' => [],
            'metadata' => [],
        ]);

        Log::info('Link import created', [
            'import_id' => $import->id,
            'project_id' => $project->id,
            'user_id' => $user->id,
            'domain' => $domain,
        ]);

        // Dispatch background job to process the import
        ProcessLinkImport::dispatch($import);

        return $import;
    }

    /**
     * Validate the provided URL.
     */
    protected function validateUrl(string $url): void
    {
        $validator = Validator::make(['url' => $url], [
            'url' => ['required', 'url', 'max:2000'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Check if domain is allowed
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $allowedDomains = (array) config('linkimport.allowed_domains', []);

        $isAllowed = collect($allowedDomains)->contains(function ($allowedDomain) use ($host) {
            return str_ends_with($host, $allowedDomain);
        });

        if (! $isAllowed) {
            throw ValidationException::withMessages([
                'url' => ['Domain not supported. Supported domains: '.implode(', ', $allowedDomains)],
            ]);
        }
    }

    /**
     * Check rate limits for the user and project.
     */
    protected function checkRateLimits(Project $project, User $user): void
    {
        $limits = config('linkimport.rate_limits');

        // Check per-project rate limits
        $recentProjectImports = LinkImport::where('project_id', $project->id)
            ->where('created_at', '>', now()->subHour())
            ->count();

        $projectLimit = $limits['per_project_per_hour'] ?? 5;
        if ($recentProjectImports >= $projectLimit) {
            throw ValidationException::withMessages([
                'url' => ['Too many imports for this project. Please wait before importing more links.'],
            ]);
        }

        // Check per-user hourly rate limits
        $recentUserImports = LinkImport::where('user_id', $user->id)
            ->where('created_at', '>', now()->subHour())
            ->count();

        $userHourlyLimit = $limits['per_user_per_hour'] ?? 10;
        if ($recentUserImports >= $userHourlyLimit) {
            throw ValidationException::withMessages([
                'url' => ['Too many imports in the last hour. Please wait before importing more links.'],
            ]);
        }

        // Check per-user daily rate limits
        $dailyUserImports = LinkImport::where('user_id', $user->id)
            ->where('created_at', '>', now()->subDay())
            ->count();

        $userDailyLimit = $limits['per_user_per_day'] ?? 50;
        if ($dailyUserImports >= $userDailyLimit) {
            throw ValidationException::withMessages([
                'url' => ['Daily import limit reached. Please try again tomorrow.'],
            ]);
        }
    }

    /**
     * Get active imports for a project.
     */
    public function getActiveImportsForProject(Project $project): \Illuminate\Database\Eloquent\Collection
    {
        return LinkImport::where('project_id', $project->id)
            ->whereIn('status', [
                LinkImport::STATUS_PENDING,
                LinkImport::STATUS_ANALYZING,
                LinkImport::STATUS_IMPORTING,
            ])
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get import history for a project.
     */
    public function getImportHistoryForProject(Project $project, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return LinkImport::where('project_id', $project->id)
            ->with(['user', 'importedFiles.projectFile'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Cancel an active import.
     */
    public function cancelImport(LinkImport $import, User $user): bool
    {
        // Authorization check
        if (! $user->can('update', $import->project) && $import->user_id !== $user->id) {
            throw new \Exception('You are not authorized to cancel this import.');
        }

        // Can only cancel if still in progress
        if (! $import->isInProgress()) {
            throw new \Exception('Import cannot be canceled as it is not in progress.');
        }

        try {
            $import->update([
                'status' => LinkImport::STATUS_FAILED,
                'error_message' => 'Import canceled by user',
                'completed_at' => now(),
            ]);

            Log::info('Link import canceled', [
                'import_id' => $import->id,
                'canceled_by' => $user->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cancel import', [
                'import_id' => $import->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to cancel import. Please try again.');
        }
    }

    /**
     * Retry a failed import.
     */
    public function retryImport(LinkImport $import, User $user): LinkImport
    {
        // Authorization check
        if (! $user->can('update', $import->project) && $import->user_id !== $user->id) {
            throw new \Exception('You are not authorized to retry this import.');
        }

        // Can only retry failed imports
        if (! $import->hasFailed()) {
            throw new \Exception('Only failed imports can be retried.');
        }

        // Check rate limits again
        $this->checkRateLimits($import->project, $user);

        // Reset the import status
        $import->update([
            'status' => LinkImport::STATUS_PENDING,
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
            'metadata' => [],
        ]);

        Log::info('Link import retried', [
            'import_id' => $import->id,
            'retried_by' => $user->id,
        ]);

        // Dispatch the job again
        ProcessLinkImport::dispatch($import);

        return $import;
    }

    /**
     * Get statistics for link imports.
     */
    public function getImportStatistics(Project $project): array
    {
        $imports = LinkImport::where('project_id', $project->id)->get();

        $totalImports = $imports->count();
        $completedImports = $imports->where('status', LinkImport::STATUS_COMPLETED)->count();
        $failedImports = $imports->where('status', LinkImport::STATUS_FAILED)->count();
        $inProgressImports = $imports->filter(fn ($import) => $import->isInProgress())->count();

        $totalFilesImported = $imports->sum(function ($import) {
            return count($import->imported_files ?? []);
        });

        return [
            'total_imports' => $totalImports,
            'completed_imports' => $completedImports,
            'failed_imports' => $failedImports,
            'in_progress_imports' => $inProgressImports,
            'total_files_imported' => $totalFilesImported,
            'success_rate' => $totalImports > 0 ? round(($completedImports / $totalImports) * 100, 1) : 0,
        ];
    }
}
