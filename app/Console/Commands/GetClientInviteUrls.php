<?php

namespace App\Console\Commands;

use App\Models\EmailAudit;
use App\Models\Project;
use Illuminate\Console\Command;

class GetClientInviteUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:invite-urls {project_id? : Optional project ID to filter by}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get client invite URLs from email audit logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projectId = $this->argument('project_id');

        // Show header
        $this->info('Client Invite URL Retrieval Tool');
        $this->line('---------------------------');

        if ($projectId) {
            // Check if project exists
            $project = Project::find($projectId);
            if (! $project) {
                $this->error("Project with ID {$projectId} not found.");

                return Command::FAILURE;
            }

            // Check if it's a client management project
            if (! $project->isClientManagement()) {
                $this->error("Project with ID {$projectId} is not a client management project.");

                return Command::FAILURE;
            }

            $this->info("Retrieving invite URLs for project: {$project->title} (ID: {$projectId})");
        } else {
            $this->info('Retrieving invite URLs for all client management projects');
        }

        try {
            // Get URLs from email audit logs - fix the redundant condition
            $query = EmailAudit::where('status', 'queued')
                ->where(function ($q) {
                    $q->where('metadata->email_type', 'client_project_invite');
                });

            // Filter by project ID if provided
            if ($projectId) {
                $query->where('metadata->project_id', $projectId);
            }

            // Get the records
            $audits = $query->orderBy('created_at', 'desc')->get();

            if ($audits->isEmpty()) {
                $this->warn('No client invite emails found in the audit logs.');

                // Try checking application logs as fallback
                $this->info('Checking application logs for manually logged URLs...');
                $this->info("You can also check the Laravel log files directly for entries containing 'Client invite URL generated'");

                return Command::SUCCESS;
            }

            // Build a table for display
            $tableData = [];
            foreach ($audits as $audit) {
                try {
                    $metadata = $audit->metadata ?? [];

                    // Handle both possible metadata key names
                    $projectId = $metadata['project_id'] ?? 'Unknown';
                    $url = $metadata['client_portal_url'] ?? null;

                    // If not found with first key, try the alternate key
                    if (! $url) {
                        $url = $metadata['signed_url'] ?? null;
                    }

                    // If URL isn't in metadata, try to extract from content
                    if (! $url && ! empty($audit->content)) {
                        // Very simple extraction - could be made more robust
                        preg_match('/href=["\'](https?:\/\/[^"\']+)["\']/i', $audit->content, $matches);
                        $url = $matches[1] ?? null;
                    }

                    if ($url) {
                        $tableData[] = [
                            'project_id' => $projectId,
                            'recipient' => $audit->email,
                            'sent_at' => $audit->created_at->format('Y-m-d H:i:s'),
                            'url' => $url,
                        ];
                    }
                } catch (\Exception $e) {
                    $this->warn('Error processing audit record: '.$e->getMessage());

                    continue;
                }
            }

            if (empty($tableData)) {
                $this->warn('No URLs could be extracted from the found audit records.');

                return Command::SUCCESS;
            }

            // Display the table
            $this->table(
                ['Project ID', 'Recipient', 'Sent At', 'URL'],
                $tableData
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('An error occurred: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
