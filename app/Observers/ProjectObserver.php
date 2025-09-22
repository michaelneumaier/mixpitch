<?php

namespace App\Observers;

use App\Models\Pitch;
use App\Models\Project;
use App\Services\FileManagementService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProjectObserver
{
    protected $notificationService;

    /**
     * Constructor to inject dependencies.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the Project "created" event.
     */
    public function created(Project $project): void
    {
        if ($project->isDirectHire() && $project->target_producer_id) {
            $initialStatus = Pitch::STATUS_IN_PROGRESS;
            Log::info('Creating automatic pitch for Direct Hire project', ['project_id' => $project->id, 'target_producer_id' => $project->target_producer_id, 'initial_status' => $initialStatus]);
            try {
                // Simplify pitch creation for debugging
                Log::info('Attempting Pitch::create for Direct Hire');

                // Ensure title is always present, use project->title
                $pitchTitle = ! empty($project->title) ? $project->title.' - Direct Offer' : 'Direct Hire Offer'; // Default title

                $pitchData = [
                    'project_id' => $project->id,
                    'user_id' => $project->target_producer_id,
                    'status' => $initialStatus,
                    'title' => $pitchTitle, // Use the guaranteed title
                    'terms_agreed' => true, // Assuming implicit agreement for direct hire
                ];

                // Debugging the data we're about to insert
                Log::info('Pitch creation data', $pitchData);

                // Attempt to create the pitch with explicit data
                $pitch = Pitch::create($pitchData);

                // Check if pitch creation was successful before proceeding
                if (! $pitch) {
                    throw new \Exception('Pitch::create returned null or false.');
                }

                Log::info('Pitch::create successful', [
                    'pitch_id' => $pitch->id,
                    'pitch_status' => $pitch->status,
                ]);

                // Create initial event
                $pitch->events()->create([
                    'event_type' => 'direct_hire_initiated',
                    'comment' => 'Direct hire project initiated for producer.',
                    'status' => $pitch->status,
                    'created_by' => $project->user_id, // Project owner initiated
                ]);

                // Notify the target producer (Implicit Flow)
                $this->notificationService->notifyDirectHireAssignment($pitch);

            } catch (\Exception $e) {
                Log::error('Failed to create automatic pitch for Direct Hire project', [
                    'project_id' => $project->id,
                    'target_producer_id' => $project->target_producer_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(), // Log full trace for debugging
                ]);
                // Re-throw the exception to potentially rollback transaction or signal failure
                throw $e;
            }
        } elseif ($project->isClientManagement() && $project->client_email) {
            // Handle Client Management: create client record and pitch
            Log::info('Creating client record and automatic pitch for Client Management project', ['project_id' => $project->id, 'client_email' => $project->client_email]);

            // First, ensure client record exists
            $client = \App\Models\Client::firstOrCreate(
                ['user_id' => $project->user_id, 'email' => $project->client_email],
                [
                    'name' => $project->client_name,
                    'timezone' => 'UTC',
                    'status' => \App\Models\Client::STATUS_ACTIVE,
                ]
            );

            // Link the project to the client
            $project->client_id = $client->id;
            $project->save();

            Log::info('Client record ensured', ['client_id' => $client->id, 'project_id' => $project->id]);
            try {
                // Determine pitch title
                $pitchTitle = ! empty($project->title) ? $project->title : 'Client Project';

                // Manually generate the slug before creating the pitch
                $slug = Str::slug($pitchTitle);
                // Ensure uniqueness (optional but recommended, mimicking Sluggable logic)
                // This simple check might suffice if titles are generally unique per producer
                $count = Pitch::where('slug', $slug)->where('user_id', $project->user_id)->count();
                if ($count > 0) {
                    $slug = $slug.'-'.($count + 1);
                }

                // Added: Get payment amount from the project (set via CreateProject component)
                $paymentAmount = $project->payment_amount ?? 0;
                // Added: Determine initial payment status
                $initialPaymentStatus = $paymentAmount > 0 ? Pitch::PAYMENT_STATUS_PENDING : Pitch::PAYMENT_STATUS_NOT_REQUIRED;

                $pitch = Pitch::create([
                    'project_id' => $project->id,
                    'user_id' => $project->user_id, // Pitch belongs to the *producer*
                    'title' => $pitchTitle,
                    'slug' => $slug, // <-- Add the generated slug here
                    'description' => $project->description, // Copy description from project
                    'status' => Pitch::STATUS_IN_PROGRESS, // Start directly in progress
                    'terms_agreed' => true, // Assume terms agreed between producer and client externally initially
                    // Added: Save payment details to Pitch
                    'payment_amount' => $paymentAmount,
                    'payment_status' => $initialPaymentStatus,
                ]);

                if (! $pitch) {
                    throw new \Exception('Pitch::create returned null or false for Client Management.');
                }

                // Create initial event
                $pitch->events()->create([
                    'event_type' => 'client_project_created',
                    'comment' => 'Client management project created by producer.',
                    'status' => $pitch->status,
                    'created_by' => $project->user_id, // Producer action
                ]);

                // Generate Signed URL for Client Portal
                $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'client.portal.view', // Route name for the portal view
                    now()->addDays(config('mixpitch.client_portal_link_expiry_days', 7)), // Use configured expiry
                    ['project' => $project->id] // Pass project ID
                );

                // Notify the external client via email
                // Assuming notifyClientProjectInvite exists in NotificationService
                $this->notificationService->notifyClientProjectInvite($project, $signedUrl);

            } catch (\Exception $e) {
                Log::error('Failed to create automatic pitch/invite client for Client Management project', [
                    'project_id' => $project->id,
                    'client_email' => $project->client_email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Re-throw exception to signal failure
                throw $e;
            }
        }
    }

    /**
     * Handle the Project "updated" event.
     */
    public function updated(Project $project): void
    {
        //
    }

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        Log::info('ProjectObserver: Project deletion started', ['project_id' => $project->id]);

        // Delete associated pitches and their files
        $project->pitches()->each(function ($pitch) {
            Log::info('ProjectObserver: Processing pitch deletion', ['pitch_id' => $pitch->id]);

            // Delete pitch files first
            $pitch->files()->each(function ($file) {
                try {
                    app(FileManagementService::class)->deletePitchFile($file);
                    Log::info('ProjectObserver: Pitch file deleted', ['file_id' => $file->id]);
                } catch (\Exception $e) {
                    Log::error('ProjectObserver: Failed to delete pitch file during project deletion', [
                        'file_id' => $file->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            // Delete the pitch
            $pitch->delete();
            Log::info('ProjectObserver: Pitch deleted', ['pitch_id' => $pitch->id]);
        });

        // Delete project files
        $project->files()->each(function ($file) {
            try {
                app(FileManagementService::class)->deleteProjectFile($file);
                Log::info('ProjectObserver: Project file deleted', ['file_id' => $file->id]);
            } catch (\Exception $e) {
                Log::error('ProjectObserver: Failed to delete project file during project deletion', [
                    'file_id' => $file->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        Log::info('ProjectObserver: Project deletion completed', ['project_id' => $project->id]);
    }

    /**
     * Handle the Project "restored" event.
     */
    public function restored(Project $project): void
    {
        //
    }

    /**
     * Handle the Project "force deleted" event.
     */
    public function forceDeleted(Project $project): void
    {
        //
    }
}
