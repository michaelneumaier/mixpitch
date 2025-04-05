<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Track;
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
        $genres = $request->get('genre');
        $query = Project::query();

        if ($genres) {
            $query->whereIn('genre', $genres);
        }

        // TODO: Add authorization check? Can anyone view any project list?
        // Consider adding filters for published/status
        $projects = $query->where('status', Project::STATUS_OPEN)->paginate(10); // Example: Only show OPEN projects
        return view('projects.index', compact('projects'));
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

    // TODO: Refactor: Project deletion logic might belong in ProjectManagementService.
    public function destroy(Project $project)
    {
        // ... (Keep existing code for now, mark for removal/refactor) ...
        $this->authorize('delete', $project);
        // ... existing deletion logic (needs to handle S3 files etc.) ...
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
            $request->validate([
                'file' => 'required|file|max:204800', // 200MB max
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
