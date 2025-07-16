<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Track;
use App\Models\FileUploadSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

// Added imports for refactoring
use App\Services\Project\ProjectManagementService;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Exceptions\Project\ProjectCreationException;
use App\Exceptions\Project\ProjectUpdateException;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    protected $projectService;

    // Inject the service
    public function __construct(ProjectManagementService $projectService)
    {
        $this->projectService = $projectService;
        // Add middleware if needed, e.g., ->only(), ->except()
        // $this->middleware('auth')->except(['index', 'show']);
    }

    public function index(Request $request)
    {
        // Validate optional request inputs
        $validated = $request->validate([
            'genre' => 'nullable|string|max:50', // Example validation
            'workflow_type' => 'nullable|string|in:' . implode(',', [
                Project::WORKFLOW_TYPE_STANDARD,
                Project::WORKFLOW_TYPE_CONTEST,
                // Do not allow direct filtering for private types here
            ]),
            'status' => 'nullable|string|in:' . implode(',', [
                Project::STATUS_OPEN,
                Project::STATUS_COMPLETED,
                // Add other publicly viewable statuses if needed
            ]),
        ]);

        $query = Project::query();

        // Default filter: Exclude private project types from public browsing
        $query->whereNotIn('workflow_type', [
            Project::WORKFLOW_TYPE_DIRECT_HIRE,
            Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT
        ]);

        // Apply filters from request
        if (!empty($validated['genre'])) {
            $query->where('genre', $validated['genre']);
        }
        if (!empty($validated['workflow_type'])) {
            $query->where('workflow_type', $validated['workflow_type']);
        }
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        } else {
            // Default to showing only OPEN projects if no status is specified
            $query->where('status', Project::STATUS_OPEN);
        }
        
        // Eager load user for display
        $query->with('user');

        // Add sorting options (e.g., by creation date, deadline)
        $query->latest(); // Default sort

        $projects = $query->paginate(12); // Adjust pagination as needed

        // Pass filters back to view for display/form persistence
        return view('projects.index', [
            'projects' => $projects,
            'filters' => $validated // Pass validated filters
        ]);
    }

    public function show(Project $project)
    {
        // TODO: Add authorization check? Policy might handle this via route model binding
        // $this->authorize('view', $project);
        $userPitch = null;
        $canPitch = false; // Default to false
        
        if (auth()->check()) {
            $userPitch = $project->userPitch(auth()->id());
            
            // Determine if the user can pitch
            $canPitch = auth()->check() && 
                        !$project->isOwnedByUser(auth()->user()) && 
                        !$userPitch && 
                        $project->status === Project::STATUS_OPEN;
        }
        
        return view('projects.project', compact('project', 'userPitch', 'canPitch'));
    }

    // TODO: This method seems redundant or incorrect. Review/Remove.
    public function projects()
    {
        $projects = Track::all(); // This fetches Tracks, not Projects
        //$projects = Track::distinct()->select('title', 'user_id', 'created_at')->get();
        return view('projects.index', compact('projects'));
    }

    public function createProject()
    {
        // Assumes any authenticated user can view the create form
        // $this->authorize('create', Project::class); // Policy check if needed
        return view('projects.upload-project');
    }

    // TODO: Refactor: This multi-step creation is being replaced.
    // Consider removing this route and view if CreateProject Livewire component handles all creation steps.
    public function createStep2(Project $project)
    {
        return view('projects.create_step2', compact('project'));
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjectRequest $request)
    {
        // Check subscription limits before creating project
        if (!$request->user()->canCreateProject()) {
            return redirect()->route('subscription.index')
                ->with('error', 'You have reached your project limit. Upgrade to Pro for unlimited projects.');
        }
        
        // Authorization and validation handled by StoreProjectRequest
        try {
            $project = $this->projectService->createProject(
                $request->user(),
                $request->validated(),
                $request->hasFile('project_image') ? $request->file('project_image') : null
            );

            // Redirect to the project page (or manage page)
            return redirect()->route('projects.show', $project)->with('success', 'Project created successfully!');
            // If multi-step is kept: return redirect()->route('projects.createStep2', $project)->with('success', 'Project details saved! Upload files now.');
        } catch (ProjectCreationException $e) {
            // Logged in the service
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error('Unexpected error storing project in controller', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'An unexpected error occurred while creating the project.')->withInput();
        }
    }

    public function edit(Project $project)
    {
        // Authorization handled by policy check
        $this->authorize('update', $project);
        // If using Livewire for editing, this route might just load the Livewire component
        return view('projects.edit', compact('project'));
    }

    /**
     * Update the specified project.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        // Authorization handled by UpdateProjectRequest
        // Validation handled by UpdateProjectRequest
        try {
            $updatedProject = $this->projectService->updateProject(
                $project,
                $request->validated(),
                $request->hasFile('image') ? $request->file('image') : null
                // Pass delete flag if needed, default is true in service
            );

            // Handle publish/unpublish based on the request flag
            if ($request->has('is_published')) {
                if ($request->boolean('is_published')) {
                    // Authorize publish action specifically if needed
                    // $this->authorize('publish', $updatedProject);
                    $this->projectService->publishProject($updatedProject);
                } else {
                    // Authorize unpublish action specifically if needed
                    // $this->authorize('unpublish', $updatedProject);
                    $this->projectService->unpublishProject($updatedProject);
                }
            }

            return redirect()->route('projects.show', $updatedProject)->with('success', 'Project updated successfully.');
            // Or redirect to manage page: return redirect()->route('projects.manage', $updatedProject)->with('success', 'Project updated successfully.');
        } catch (ProjectUpdateException $e) {
             // Logged in the service
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error('Unexpected error updating project in controller', ['project_id' => $project->id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'An unexpected error occurred while updating the project.')->withInput();
        }
    }

    /**
     * Delete the specified project.
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);
        
        try {
            $project->delete(); // Observer will handle cascading deletes
            
            return redirect()->route('dashboard')
                ->with('success', 'Project deleted successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error deleting project', [
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'An error occurred while deleting the project.');
        }
    }

    // TODO: Refactor: This helper likely belongs elsewhere (e.g., a helper file/trait or removed).
    function formatBytes($bytes, $precision = 2)
    {
        // ... (Keep existing code for now, mark for removal/refactor) ...
    }

    // TODO: Refactor: This helper likely belongs elsewhere or removed.
    private function cleanTempFolder($folder)
    {
         // ... (Keep existing code for now, mark for removal/refactor) ...
    }

    // TODO: Remove this method, replaced by store()
    public function storeProject(Request $request)
    {
        // Mark as deprecated/to be removed
        Log::warning('Deprecated ProjectController::storeProject called.');
        // Keep existing code for now or redirect to new store method?
        // For safety, maybe just return an error or redirect
        return redirect()->route('projects.create')->with('error', 'This action is outdated. Please use the standard create form.');
        /*
        $request->validate([...]);
        try {
            // ... old logic ...
            $project->save();
            return redirect()->route('projects.createStep2', ['project' => $project]);
        } catch (\Exception $e) {
            Log::error(...);
            return redirect()->back()->with('error', 'Failed to create project: ' . $e->getMessage())->withInput();
        }
        */
    }

    /**
     * Handle a single file upload for a project via AJAX.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadSingle(Request $request)
    {
        try {
            // Get project context settings
            $settings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_PROJECTS);
            $maxFileSizeKB = $settings[FileUploadSetting::MAX_FILE_SIZE_MB] * 1024; // Convert MB to KB for Laravel validation
            
            $request->validate([
                'file' => "required|file|max:{$maxFileSizeKB}",
                'project_id' => 'required|exists:projects,id',
            ]);

            $project = Project::findOrFail($request->project_id);
            
            // Check if user is authorized to upload to this project
            if (!Auth::user()->can('uploadFile', $project)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'You are not authorized to upload files to this project'
                ], 403);
            }

            // Get the FileManagementService instance
            $fileManagementService = app(\App\Services\FileManagementService::class);

            // Use the service to handle the upload
            $projectFile = $fileManagementService->uploadProjectFile(
                $project, 
                $request->file('file'), 
                Auth::user()
            );

            // Return success response with file information
            return response()->json([
                'success' => true,
                'file_id' => $projectFile->id,
                'file_path' => $projectFile->file_path,
                'file_name' => $projectFile->file_name,
                'storage_percentage' => $project->getStorageUsedPercentage(),
                'storage_limit_message' => $project->getStorageLimitMessage(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to get proper 422 response
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error uploading project file via AJAX', [
                'error' => $e->getMessage(),
                'project_id' => $request->project_id ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
