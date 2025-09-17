<?php

namespace App\Console\Commands;

use App\Jobs\ProcessLinkImport;
use App\Models\Project;
use App\Models\User;
use App\Services\LinkImportService;
use Illuminate\Console\Command;

class TestWeTransferImportCommand extends Command
{
    protected $signature = 'test:wetransfer-import {url}';

    protected $description = 'Test WeTransfer import end-to-end';

    public function handle()
    {
        $url = $this->argument('url');
        $this->info("Testing WeTransfer import: {$url}");

        try {
            // Get or create a test user and project
            $user = User::first();
            if (! $user) {
                $this->error('No users found. Please create a user first.');

                return 1;
            }

            $project = Project::where('workflow_type', 'client_management')->first();
            if (! $project) {
                $this->error('No client management projects found. Please create one first.');

                return 1;
            }

            $this->info("Using user: {$user->name} (ID: {$user->id})");
            $this->info("Using project: {$project->name} (ID: {$project->id})");

            // Create the link import
            $linkImportService = app(LinkImportService::class);
            $import = $linkImportService->createImport($project, $url, $user);

            $this->info("Created link import ID: {$import->id}");

            // Process the import synchronously for testing
            $job = new ProcessLinkImport($import);
            $this->info('Processing import job...');

            $job->handle(
                app(\App\Services\LinkAnalysisService::class),
                app(\App\Services\FileManagementService::class)
            );

            // Check the results
            $import->refresh();
            $this->info("Import status: {$import->status}");

            if ($import->status === 'completed') {
                $this->info('✅ Import completed successfully!');
                $this->info('Files imported: '.count($import->imported_files ?? []));

                if ($import->detected_files) {
                    $this->info('Detected files:');
                    foreach ($import->detected_files as $file) {
                        $this->info('  - '.($file['filename'] ?? 'unknown'));
                    }
                }
            } elseif ($import->status === 'failed') {
                $this->error('❌ Import failed: '.($import->error_message ?? 'Unknown error'));
            } else {
                $this->warn("⚠️ Import status: {$import->status}");
            }

        } catch (\Exception $e) {
            $this->error('❌ Test failed: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());

            return 1;
        }

        return 0;
    }
}
