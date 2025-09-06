<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Models\Client;
use App\Models\Project;
use App\Services\Project\ProjectManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateProjectAction extends ZapierApiController
{
    protected $projectService;

    public function __construct(ProjectManagementService $projectService)
    {
        $this->projectService = $projectService;
    }

    /**
     * Create a new client management project
     *
     * This action allows creating projects for existing clients
     * from external triggers like CRM deals, calendar events, etc.
     */
    public function create(Request $request): JsonResponse
    {
        if (! $this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (! $this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        $user = $request->user();

        // Validate input
        $validated = $request->validate([
            // Required fields
            'name' => ['required', 'string', 'max:255'],
            'client_id' => ['required_without:client_email', 'integer'],
            'client_email' => ['required_without:client_id', 'email'],

            // Optional project fields
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'project_type' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'integer', 'min:0'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'deadline' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:5000'],

            // Client management specific
            'auto_allow_access' => ['nullable', 'boolean'],
            'requires_license_agreement' => ['nullable', 'boolean'],
            'is_prioritized' => ['nullable', 'boolean'],
            'is_private' => ['nullable', 'boolean'],

            // Optional client creation fields
            'create_client_if_missing' => ['nullable', 'boolean'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_company' => ['nullable', 'string', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:50'],
        ]);

        // Find or create client
        $client = null;
        if (isset($validated['client_id'])) {
            $client = Client::where('user_id', $user->id)
                ->where('id', $validated['client_id'])
                ->first();
        } else {
            $client = Client::where('user_id', $user->id)
                ->where('email', $validated['client_email'])
                ->first();
        }

        // Create client if missing and requested
        if (! $client && isset($validated['client_email'])) {
            if ($request->boolean('create_client_if_missing', false)) {
                $client = Client::create([
                    'user_id' => $user->id,
                    'email' => $validated['client_email'],
                    'name' => $validated['client_name'] ?? null,
                    'company' => $validated['client_company'] ?? null,
                    'phone' => $validated['client_phone'] ?? null,
                    'status' => Client::STATUS_ACTIVE,
                ]);
            } else {
                return $this->errorResponse('Client not found. Set create_client_if_missing=true to create automatically.', 404);
            }
        }

        if (! $client) {
            return $this->errorResponse('Client not found', 404);
        }

        // Prepare project data
        $projectData = [
            'user_id' => $user->id,
            'name' => $validated['name'],
            'title' => $validated['title'] ?? $validated['name'],
            'description' => $validated['description'],
            'project_type' => $validated['project_type'] ?? 'mixing',
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'status' => Project::STATUS_PENDING,
            'budget' => $validated['budget'],
            'payment_amount' => $validated['payment_amount'],
            'deadline' => isset($validated['deadline']) ? \Carbon\Carbon::parse($validated['deadline']) : null,
            'notes' => $validated['notes'],

            // Client reference
            'client_id' => $client->id,
            'client_email' => $client->email,
            'client_name' => $client->name,

            // Client management settings
            'auto_allow_access' => $request->boolean('auto_allow_access', true),
            'requires_license_agreement' => $request->boolean('requires_license_agreement', false),
            'is_prioritized' => $request->boolean('is_prioritized', false),
            'is_private' => $request->boolean('is_private', false),
            'is_published' => true,
        ];

        try {
            // Create the project directly
            $project = Project::create($projectData);

            // For client management projects, we typically create an initial pitch automatically
            // This follows the pattern established in the codebase
            $pitch = $project->pitches()->create([
                'user_id' => $user->id,
                'status' => \App\Models\Pitch::STATUS_PENDING,
                'payment_amount' => $project->payment_amount,
                'payment_status' => 'unpaid',
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to create Zapier project', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'data' => $projectData,
            ]);

            return $this->errorResponse('Failed to create project: '.$e->getMessage(), 500);
        }

        // Update client project count
        $client->increment('total_projects');

        // Log the action
        if (config('zapier.log_usage')) {
            \App\Models\ZapierUsageLog::create([
                'user_id' => $user->id,
                'endpoint' => 'actions.projects.create',
                'request_data' => $this->sanitizeRequestData($request->all()),
                'response_count' => 1,
                'status_code' => 200,
            ]);
        }

        return $this->successResponse([
            'id' => $project->id,
            'name' => $project->name,
            'title' => $project->title,
            'description' => $project->description,
            'status' => $project->status,
            'workflow_type' => $project->workflow_type,
            'project_type' => $project->project_type,
            'budget' => $project->budget,
            'payment_amount' => $project->payment_amount,
            'deadline' => $project->deadline?->toDateString(),
            'is_prioritized' => $project->is_prioritized,
            'is_private' => $project->is_private,
            'created_at' => $project->created_at->toIso8601String(),
            'updated_at' => $project->updated_at->toIso8601String(),

            // Client information
            'client' => [
                'id' => $client->id,
                'email' => $client->email,
                'name' => $client->name,
                'company' => $client->company,
                'phone' => $client->phone,
                'status' => $client->status,
                'was_created' => $client->wasRecentlyCreated,
                'total_projects' => $client->total_projects,
            ],

            // Pitch information
            'pitch' => $pitch ? [
                'id' => $pitch->id,
                'status' => $pitch->status,
                'payment_status' => $pitch->payment_status,
                'created_at' => $pitch->created_at->toIso8601String(),
            ] : null,

            // URLs
            'producer_dashboard_url' => route('projects.show', $project),
            'client_portal_url' => $this->generateClientPortalUrl($project),

            // Confirmation flags
            'was_created' => true,
            'client_was_created' => $client->wasRecentlyCreated,
            'pitch_created' => $pitch ? true : false,
        ], 'Project created successfully');
    }
}
