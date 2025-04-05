# MixPitch Backend Refactoring Guide: Models, Controllers, Services

**Version:** 1.0
**Date:** 2024-07-26

## Introduction

This guide provides a detailed, step-by-step process for refactoring the backend architecture (Models, Controllers, Services) related to the Project and Pitch features of the MixPitch application. The goal is to achieve a cleaner separation of concerns, improve maintainability, enhance security, and increase robustness by implementing the principles outlined in the `REFACTORING_PLAN.md`.

**Prerequisites:**

*   Familiarity with Laravel (Models, Controllers, Services, Form Requests, Eloquent, Policies, Events, Queues).
*   Familiarity with Livewire (Components, Actions, Data Binding, Events).
*   Understanding of the existing Project and Pitch workflow (`WORKFLOW.md`).
*   Setup of a local development environment with the MixPitch codebase.
*   **Note:** The simulation identified some initial setup steps (creating directories, registering policies) might be needed if not already done.

**Core Principles Being Implemented:**

*   **Thin Controllers:** Handle HTTP layer concerns only.
*   **Fat Services:** Encapsulate business logic and workflows.
*   **Focused Models:** Represent data and relationships.
*   **Form Requests:** Handle validation and authorization for controller actions.
*   **Transaction Management:** Ensure atomicity for critical operations.
*   **Custom Exceptions:** Provide specific error handling.
*   **Dependency Injection:** Manage dependencies cleanly.

**Process Overview:**

1.  **Setup:** Create necessary directory structures and base classes.
2.  **Domain Refactoring (Iterative):** Refactor logic domain by domain (e.g., Project Creation, Pitch Creation, Status Updates, File Management, Completion).
    *   For each domain/feature:
        *   Create/Update Service Classes.
        *   Create Form Request Classes.
        *   Refactor Controller Methods.
        *   Refactor Models (if necessary).
        *   Refactor Corresponding Livewire Components / Blade Views.
        *   Write/Update Tests.
3.  **Testing:** Thoroughly test each refactored part and the integrated whole.

**Important Notes:**

*   **Incremental Changes:** Apply these changes incrementally and test frequently.
*   **Version Control:** Use Git diligently. Commit small, logical changes.
*   **Collaboration:** If working in a team, communicate changes clearly.
*   **Testing:** This guide assumes Feature and potentially Unit tests will be written or updated alongside the refactoring steps to verify correctness.

---

## Step 1: Setup and Preparation

1.  **Create Service Directory Structure:**
    *   Ensure the `app/Services` directory exists. (Likely already exists).
    *   Consider creating subdirectories for organization if needed (e.g., `app/Services/Pitch`, `app/Services/Project`, `app/Services/File`).
2.  **Create Form Request Directory:**
    *   **Action:** Create the directory `app/Http/Requests` if it doesn't exist.
    *   Consider subdirectories like `app/Http/Requests/Project` and `app/Http/Requests/Pitch` within it.
3.  **Create Custom Exceptions Directory:**
    *   Ensure the `app/Exceptions` directory exists. (Likely already exists).
    *   Consider subdirectories like `app/Exceptions/Pitch`, `app/Exceptions/Project`, `app/Exceptions/File`.
4.  **Review Policies & Registration:**
    *   Ensure Policies exist for relevant models (`ProjectPolicy`, `PitchPolicy`, `PitchFilePolicy` etc.) in `app/Policies`. (`ProjectPolicy` and `PitchPolicy` likely exist).
    *   **Action:** Register *all* necessary policies in `app/Providers/AuthServiceProvider.php`. Ensure the existing `ProjectPolicy` is registered if not already done.
    *   Verify policy methods cover all necessary actions (view, create, update, delete, status changes, file uploads/downloads) – *Note: Specific methods needed for refactoring (e.g., `approveSubmission`, `complete`, `submitForReview`, `uploadFile`) will likely need to be added in later steps.* 

---

## Step 2: Refactoring Project Management

This section covers refactoring the creation, update, and potentially publishing/unpublishing of Projects, primarily involving `ProjectController` and the `Project` model.

**Target Files:**

*   `app/Http/Controllers/ProjectController.php`
*   `app/Models/Project.php`
*   `app/Livewire/CreateProject.php` (and associated view)
*   `app/Livewire/ManageProject.php` (and associated view)
*   `resources/views/projects/edit.blade.php` (if not Livewire)

**A. Create `ProjectManagementService`**

1.  Create `app/Services/ProjectManagementService.php`.
2.  Define methods based on `ProjectController` logic:
    ```php
    <?php

    namespace App\Services;

    use App\Models\Project;
    use App\Models\User;
    use Illuminate\Http\UploadedFile;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Storage;
    use App\Exceptions\Project\ProjectCreationException;
    use App\Exceptions\Project\ProjectUpdateException;
    use App\Exceptions\File\FileUploadException;
    use App\Exceptions\File\StorageLimitException;
    use Illuminate\Support\Facades\Log;

    class ProjectManagementService
    {
        /**
         * Create a new project.
         *
         * @param User $user The user creating the project.
         * @param array $validatedData Validated data from the request.
         * @param ?UploadedFile $projectImage Optional uploaded project image.
         * @return Project The newly created project.
         * @throws ProjectCreationException|
         */
        public function createProject(User $user, array $validatedData, ?UploadedFile $projectImage): Project
        {
            try {
                return DB::transaction(function () use ($user, $validatedData, $projectImage) {
                    $project = new Project($validatedData);
                    $project->user_id = $user->id;
                    $project->status = Project::STATUS_UNPUBLISHED; // Ensure default

                    if ($projectImage) {
                        // Consider moving path generation logic here or keep in model if simple
                        $path = $projectImage->store('project_images', 's3');
                        $project->image_path = $path;
                        Log::info('Project image uploaded to S3', ['path' => $path, 'project_name' => $project->name]);
                    }

                    $project->save();
                    // Dispatch ProjectCreated event if needed
                    // event(new ProjectCreated($project));
                    return $project;
                });
            } catch (\Exception $e) {
                Log::error('Error creating project in service', ['error' => $e->getMessage(), 'data' => $validatedData]);
                throw new ProjectCreationException('Failed to create project: ' . $e->getMessage(), 0, $e);
            }
        }

        /**
         * Update an existing project.
         *
         * @param Project $project The project to update.
         * @param array $validatedData Validated data from the request.
         * @param ?UploadedFile $newProjectImage Optional new project image.
         * @param bool $deleteExistingImage Flag to delete existing image if new one is uploaded.
         * @return Project The updated project.
         * @throws ProjectUpdateException
         */
        public function updateProject(Project $project, array $validatedData, ?UploadedFile $newProjectImage, bool $deleteExistingImage = true): Project
        {
             try {
                return DB::transaction(function () use ($project, $validatedData, $newProjectImage, $deleteExistingImage) {
                    $oldImagePath = $project->image_path;

                    $project->fill($validatedData);

                    if ($newProjectImage) {
                        // Delete old image first (if requested and exists)
                        if ($deleteExistingImage && $oldImagePath && Storage::disk('s3')->exists($oldImagePath)) {
                             try {
                                Storage::disk('s3')->delete($oldImagePath);
                                Log::info('Old project image deleted from S3', ['path' => $oldImagePath, 'project_id' => $project->id]);
                            } catch (\Exception $e) {
                                Log::error('Failed to delete old project image', ['path' => $oldImagePath, 'project_id' => $project->id, 'error' => $e->getMessage()]);
                                // Decide if this should halt the update or just log
                            }
                        }
                        // Store new image
                        $path = $newProjectImage->store('project_images', 's3');
                        $project->image_path = $path;
                        Log::info('New project image uploaded to S3', ['path' => $path, 'project_id' => $project->id]);
                    }

                    $project->save();
                    // Dispatch ProjectUpdated event if needed
                    // event(new ProjectUpdated($project));
                    return $project;
                });
            } catch (\Exception $e) {
                Log::error('Error updating project in service', ['project_id' => $project->id, 'error' => $e->getMessage(), 'data' => $validatedData]);
                throw new ProjectUpdateException('Failed to update project: ' . $e->getMessage(), 0, $e);
            }
        }

        /**
         * Publish a project.
         *
         * @param Project $project
         * @return Project
         */
        public function publishProject(Project $project): Project
        {
            // Policy check should happen before calling the service
            $project->publish(); // Keep simple status logic in model or move here if complex
             // Dispatch ProjectPublished event if needed
             // event(new ProjectPublished($project));
            return $project;
        }

        /**
         * Unpublish a project.
         *
         * @param Project $project
         * @return Project
         */
        public function unpublishProject(Project $project): Project
        {
            // Policy check should happen before calling the service
            $project->unpublish(); // Keep simple status logic in model or move here if complex
             // Dispatch ProjectUnpublished event if needed
             // event(new ProjectUnpublished($project));
            return $project;
        }

         /**
         * Mark a project as completed.
         * Typically called by the PitchCompletionService.
         *
         * @param Project $project
         * @return Project
         */
        public function completeProject(Project $project): Project
        {
            // Ensure this is idempotent or called correctly
            if ($project->status !== Project::STATUS_COMPLETED) {
                $project->status = Project::STATUS_COMPLETED;
                $project->completed_at = now();
                $project->save();
                 // Dispatch ProjectCompleted event if needed
                // event(new ProjectCompleted($project));
            }
            return $project;
        }

        // Add other methods as needed (e.g., deleteProject)
    }
    ```

**B. Create `Project` Form Requests**

1.  Create `app/Http/Requests/Project/StoreProjectRequest.php`:
    ```php
    <?php
    namespace App\Http\Requests\Project;

    use Illuminate\Foundation\Http\FormRequest;
    use App\Models\Project; // If needed for policy

    class StoreProjectRequest extends FormRequest
    {
        public function authorize(): bool
        {
            // Assumes any authenticated user can create a project
            // Or use Policy: return $this->user()->can('create', Project::class);
            return auth()->check();
        }

        public function rules(): array
        {
            // Extracted from ProjectController::storeProject
            return [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:2048',
                'genre' => 'required|string|in:Pop,Rock,Country,Hip Hop,Jazz', // Consider making this configurable
                'project_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
                'artist_name' => 'required|string|max:255',
                'project_type' => 'required|string|max:100', // Adjust max as needed
                'collaboration_type' => 'required|array', // Add validation for array contents if possible
                'collaboration_type.*' => 'string|max:100',
                'budget' => 'required|numeric|min:0',
                'deadline' => 'nullable|date|after_or_equal:today',
                'notes' => 'nullable|string|max:5000',
                // Add rules for any other fields captured at creation
            ];
        }
    }
    ```
2.  Create `app/Http/Requests/Project/UpdateProjectRequest.php`:
    ```php
    <?php
    namespace App\Http\Requests\Project;

    use Illuminate\Foundation\Http\FormRequest;
    use App\Models\Project;

    class UpdateProjectRequest extends FormRequest
    {
        public function authorize(): bool
        {
            $project = $this->route('project'); // Assuming route model binding
            return $this->user()->can('update', $project);
        }

        public function rules(): array
        {
             // Extracted from ProjectController::update
             // Loosen 'required' for fields that aren't always updated
             return [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:2048',
                'genre' => 'sometimes|required|string|in:Pop,Rock,Country,Hip Hop,Jazz',
                'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'artist_name' => 'sometimes|required|string|max:255',
                'project_type' => 'sometimes|required|string|max:100',
                'collaboration_type' => 'sometimes|required|array',
                'collaboration_type.*' => 'string|max:100',
                'budget' => 'sometimes|required|numeric|min:0',
                'deadline' => 'nullable|date|after_or_equal:today',
                'notes' => 'nullable|string|max:5000',
                'is_published' => 'sometimes|boolean', // For publish/unpublish via update
                // Only allow status changes via dedicated actions/services, not mass update.
                // 'status' => 'sometimes|required|in:...' - Avoid this
             ];
        }
    }
    ```

**C. Refactor `ProjectController`**

1.  Inject `ProjectManagementService` in the constructor or methods.
2.  Refactor `storeProject` (assuming this maps to a route, not just Livewire):
    ```php
    use App\Http\Requests\Project\StoreProjectRequest;
    use App\Services\ProjectManagementService;
    use App\Exceptions\Project\ProjectCreationException;
    use Illuminate\Support\Facades\Log;

    // ... inject service ...
    protected $projectService;
    public function __construct(ProjectManagementService $projectService) {
        $this->projectService = $projectService;
    }

    public function store(StoreProjectRequest $request)
    {
        try {
            $project = $this->projectService->createProject(
                $request->user(),
                $request->validated(),
                $request->hasFile('project_image') ? $request->file('project_image') : null
            );

            // Redirect to step 2 or project page
            // return redirect()->route('projects.createStep2', $project)->with('success', 'Project created!');
            return redirect()->route('projects.show', $project)->with('success', 'Project created!');
        } catch (ProjectCreationException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error('Unexpected error storing project', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'An unexpected error occurred while creating the project.')->withInput();
        }
    }
    ```
3.  Refactor `update` method:
    ```php
    use App\Http\Requests\Project\UpdateProjectRequest;
    use App\Models\Project;
    use App\Services\ProjectManagementService;
    use App\Exceptions\Project\ProjectUpdateException;
    use Illuminate\Support\Facades\Log;

    public function update(UpdateProjectRequest $request, Project $project)
    {
        try {
            $project = $this->projectService->updateProject(
                $project,
                $request->validated(),
                $request->hasFile('image') ? $request->file('image') : null
            );

            // Handle publish/unpublish if included in the request
            if ($request->has('is_published')) {
                if ($request->boolean('is_published')) {
                    $this->projectService->publishProject($project);
                } else {
                    $this->projectService->unpublishProject($project);
                }
            }

            return redirect()->route('projects.show', $project)->with('success', 'Project updated successfully.');
            // Or redirect to manage page: return redirect()->route('projects.manage', $project)->with('success', 'Project updated successfully.');
        } catch (ProjectUpdateException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error('Unexpected error updating project', ['project_id' => $project->id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'An unexpected error occurred while updating the project.')->withInput();
        }
    }
    ```
4.  Refactor `publish` / `unpublish` actions (if they are separate controller actions):
    ```php
    // Example publish action
    public function publish(Request $request, Project $project)
    {
        $this->authorize('update', $project); // Or a specific 'publish' ability
        try {
            $this->projectService->publishProject($project);
            return back()->with('success', 'Project published.');
        } catch (\Exception $e) {
             Log::error('Failed to publish project', ['project_id' => $project->id, 'error' => $e->getMessage()]);
             return back()->with('error', 'Failed to publish project.');
        }
    }
    // Similar for unpublish
    ```
5.  Review other methods (`index`, `show`, `create`, `edit`): These are generally okay but ensure authorization is checked (`$this->authorize(...)` or middleware).

**D. Refactor `Project` Model**

1.  Remove `publish()` and `unpublish()` methods *if* you moved the core logic (status change, saving) entirely into the `ProjectManagementService`. Keep them if they only contain the simple status update and save call, although moving the save guarantees better transaction control within the service.
2.  Review `syncStatusWithPitches()`: This logic is complex and inefficient.
    *   **Action:** This method *must be removed* from the `Project` model. Choose one of the following replacement strategies:
    *   **Option 1 (Event-Driven - Decoupled):**
        *   Remove `syncStatusWithPitches`.
        *   Dispatch specific events when a `Pitch` status changes significantly (e.g., `App\\Events\\Pitch\\PitchApproved`, `App\\Events\\Pitch\\PitchCompleted`, `App\\Events\\Pitch\\PitchClosed`).
        *   Create corresponding Listeners (e.g., `App\\Listeners\\Project\\UpdateProjectStatusOnPitchChange`) that react to these events.
        *   The listener fetches the relevant `Project` and updates its status based *only* on the information needed (e.g., does it have *any* active/approved pitches? Does it have a completed pitch?). This avoids iterating all pitches every time and decouples the models. Requires setting up the event/listener infrastructure if not already present.
    *   **Option 2 (Service Orchestration - More Direct):**
        *   Remove `syncStatusWithPitches`.
        *   The `PitchWorkflowService` (or `PitchCompletionService`) becomes responsible for calling `ProjectManagementService->completeProject($project)` when a pitch is completed.
        *   When a pitch is approved or made active, the `PitchWorkflowService` could potentially call a method like `ProjectManagementService->updateProjectStatusIfNeeded($project)` which performs an optimized check (e.g., `projects()->where(id, $id)->whereDoesntHave('pitches', fn($q) => $q->where('status', Pitch::COMPLETED))->whereHas('pitches', fn($q) => $q->whereIn('status', [ACTIVE_STATUSES]))->exists()`).
    *   **Decision:** Choose the approach that best fits the overall application architecture. The event-driven approach offers better decoupling but adds complexity if events/listeners are not already common. Service orchestration is more direct but creates tighter coupling between services.
3.  Review `hasStorageCapacity`, `updateStorageUsed`, `isFileSizeAllowed`: Move the *orchestration* of these checks and related actions (like `deleteProjectImage`, `deleteCachedZip`) to a `FileManagementService` (see Step 5). The basic calculation/constant lookup can remain in the model for convenience, but the service should call them. **Action:** Replace `updateStorageUsed` with atomic `increment`/`decrement` methods (detailed in Step 5D).

**E. Refactor Frontend (Livewire / Blade)**

1.  **`CreateProject.php` / `ManageProject.php` (or Blade forms):**
    *   Ensure forms submit to the refactored controller actions (`store`, `update`).
    *   If these are Livewire components handling the form submission directly:
        *   Inject `ProjectManagementService`.
        *   Move validation rules from the controller/Form Request into the Livewire component's `$rules` property or `rules()` method.
        *   Implement authorization checks within the Livewire action method using `$this->authorize(...)`.
        *   Call the appropriate `ProjectManagementService` methods within a try/catch block.
        *   Handle exceptions thrown by the service to display user feedback (e.g., using Toaster).
        *   **Example (Livewire `save` method):**
            ```php
            use App\Services\ProjectManagementService;
            use App\Exceptions\Project\ProjectCreationException;
            use Livewire\WithFileUploads;
            use Masmerise\Toaster\Toaster;

            class CreateProject extends Component
            {
                use WithFileUploads;
                // ... properties for form fields (name, description, genre, project_image ...) ...
                public $project_image;

                protected function rules() {
                    // Copy rules from StoreProjectRequest
                    return [...];
                }

                public function save(ProjectManagementService $projectService)
                {
                    $this->authorize('create', \App\Models\Project::class);
                    $validatedData = $this->validate();

                    try {
                        // Separate image data from other validated data
                        $imageData = null;
                        if ($this->project_image) {
                           $imageData = $this->project_image;
                           unset($validatedData['project_image']);
                        }

                        $project = $projectService->createProject(
                            auth()->user(),
                            $validatedData,
                            $imageData
                        );
                        Toaster::success('Project created successfully!');
                        return redirect()->route('projects.show', $project);
                    } catch (ProjectCreationException $e) {
                        Toaster::error($e->getMessage());
                    } catch (\Exception $e) {
                        Log::error('Unexpected error creating project via Livewire', ['error' => $e->getMessage()]);
                        Toaster::error('An unexpected error occurred.');
                    }
                }
                // ...
            }
            ```
2.  **Displaying Validation Errors:** Ensure validation errors are displayed correctly next to fields, whether using standard Blade `@error` directives or Livewire's `$errors` object.
3.  **Displaying Success/Error Messages:** Standardize the use of Toaster notifications or session flash messages based on the controller/Livewire responses.
4.  **Publish/Unpublish Buttons:** Ensure buttons in `ManageProject.php` (or Blade views) now call the refactored controller actions or corresponding Livewire actions that delegate to the `ProjectManagementService`.

---

## Step 3: Refactoring Pitch Creation & Basic Management

Covers creating pitches, viewing, and basic updates (excluding status changes and file management for now).

**Target Files:**

*   `app/Http/Controllers/PitchController.php`
*   `app/Models/Pitch.php`
*   `app/Models/Project.php`
*   `resources/views/pitches/create.blade.php`
*   `app/Livewire/Pitch/Component/ManagePitch.php` (and view)

**A. Create `PitchWorkflowService`**

1.  Create `app/Services/PitchWorkflowService.php`.
2.  Define initial methods:
    ```php
    <?php
    namespace App\Services;

    use App\Models\Project;
    use App\Models\Pitch;
    use App\Models\User;
    use Illuminate\Support\Facades\DB;
    use App\Exceptions\Pitch\PitchCreationException;
    use App\Exceptions\Pitch\UnauthorizedActionException; // General purpose
    use Illuminate\Support\Facades\Log;
    use App\Services\NotificationService; // Assumed dependency

    class PitchWorkflowService
    {
        protected $notificationService;

        // Inject other services like NotificationService if needed
        // Note: Assumes an App\Services\NotificationService exists, responsible for
        // queuing and dispatching application notifications via appropriate channels
        // (e.g., mail, database) based on user preferences and event types.
        public function __construct(NotificationService $notificationService)
        {
            $this->notificationService = $notificationService;
        }

        /**
         * Create a new pitch for a project.
         *
         * @param Project $project
         * @param User $user The user creating the pitch (producer).
         * @param array $validatedData (Potentially empty for initial creation, or could include title/desc if added)
         * @return Pitch
         * @throws PitchCreationException|UnauthorizedActionException
         */
        public function createPitch(Project $project, User $user, array $validatedData): Pitch
        {
            // Perform checks previously in controller/model (can user pitch? project open? user already pitched?)
            // Use policies or dedicated model methods for checks.
            if (!$project->isOpenForPitches()) { // Example check - implement this in Project model
                 throw new PitchCreationException('This project is not currently open for pitches.');
            }
            if ($project->userPitch($user->id)) {
                throw new PitchCreationException('You have already submitted a pitch for this project.');
            }
            // Add policy check if not handled by Form Request
            // if ($user->cannot('createPitch', $project)) { ... }

            try {
                return DB::transaction(function () use ($project, $user, $validatedData) {
                    $pitch = new Pitch();
                    $pitch->project_id = $project->id;
                    $pitch->user_id = $user->id;
                    $pitch->status = Pitch::STATUS_PENDING; // Default initial status
                    $pitch->fill($validatedData); // If title/desc are captured at creation

                    // Slug generation is handled by the Sluggable trait on saving
                    $pitch->save();

                    // Create initial event (Consider moving to an Observer: PitchObserver::created)
                    $pitch->events()->create([
                        'event_type' => 'status_change',
                        'comment' => 'Pitch created and pending project owner approval.',
                        'status' => $pitch->status,
                        'created_by' => $user->id, // Or system user?
                    ]);

                    // Notify project owner (queue this notification)
                    $this->notificationService->notifyPitchSubmitted($pitch);

                    return $pitch;
                });
            } catch (\Exception $e) {
                Log::error('Error creating pitch in service', ['project_id' => $project->id, 'user_id' => $user->id, 'error' => $e->getMessage()]);
                // Don't expose raw DB errors
                throw new PitchCreationException('An error occurred while creating your pitch. Please try again.');
            }
        }

        // Add updatePitchDetails method later if needed for title/description edits

    }
    ```

**B. Create `Pitch` Form Requests**

1.  Create `app/Http/Requests/Pitch/StorePitchRequest.php`:
    ```php
    <?php
    namespace App\Http\Requests\Pitch;

    use Illuminate\Foundation\Http\FormRequest;
    use App\Models\Project;

    class StorePitchRequest extends FormRequest
    {
        public function authorize(): bool
        {
            $project = $this->route('project'); // Assumes route binding: projects/{project}/pitches
            if (!$project) {
                // Handle case where project isn't route bound (e.g., coming from a different form)
                $project = Project::find($this->input('project_id'));
            }
            if (!$project) return false;

            // Use Policy: User can create a pitch for *this specific project*
            return $this->user()->can('createPitch', $project);
        }

        public function rules(): array
        {
            // Validation from PitchController::store
            return [
                // project_id is usually from route binding, but validate if in request body
                'project_id' => 'sometimes|required|exists:projects,id',
                'agree_terms' => 'accepted', // Ensures checkbox is ticked
                // Add rules for title, description if they are part of the initial form
                // 'title' => 'required|string|max:255',
                // 'description' => 'nullable|string|max:2048',
            ];
        }
    }
    ```

**C. Refactor `PitchController`**

1.  Inject `PitchWorkflowService`.
2.  Refactor `create` method: Mostly view logic, but ensure project state check is robust.
    ```php
    public function create(Project $project)
    {
        // Policy check (can user *view* the create form? Often tied to project visibility)
        // $this->authorize('view', $project); // Or similar

        $user = auth()->user();
        if ($user) {
            // Check if user already has a pitch (redirect if so)
            $userPitch = $project->userPitch($user->id);
            if ($userPitch) {
                return redirect()->route('projects.pitches.show', ['project' => $project->slug, 'pitch' => $userPitch->slug]);
            }

            // Check project status / ability to pitch (can be done via Policy in Form Request too)
             if (!$project->isOpenForPitches()) {
                 return redirect()->route('projects.show', $project->slug)
                    ->with('error', 'This project is not currently open for pitches.');
             }
             // Policy check for 'createPitch' ability
             if ($user->cannot('createPitch', $project)) {
                  return redirect()->route('projects.show', $project->slug)
                    ->with('error', 'You are not eligible to pitch for this project.');
             }
        }

        return view('pitches.create', compact('project'));
    }
    ```
3.  Refactor `store` method:
    ```php
    use App\Http\Requests\Pitch\StorePitchRequest;
    use App\Services\PitchWorkflowService;
    use App\Exceptions\Pitch\PitchCreationException;
    use Illuminate\Support\Facades\Log;
    use App\Models\Project;

    // ... inject service ...

    public function store(StorePitchRequest $request, Project $project, PitchWorkflowService $pitchWorkflowService)
    {
        // Auth/Validation handled by Form Request
        try {
            $pitch = $pitchWorkflowService->createPitch(
                $project, // Project from route model binding
                $request->user(),
                $request->validated()
            );

            return redirect()->route('projects.pitches.show', ['project' => $project->slug, 'pitch' => $pitch->slug])
                             ->with('success', 'Your pitch application has been submitted and is pending approval.');

        } catch (PitchCreationException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error('Unexpected error storing pitch', ['project_id' => $project->id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again.')->withInput();
        }
    }
    ```
4.  Refactor `showProjectPitch` method:
    *   Authorization (`$user->can('view', $pitch)`) should be the primary check using `PitchPolicy`.
    *   The logic redirecting the project owner to the manage page is fine.
    *   Ensure the view (`pitches.show`) receives all necessary data.
5.  Refactor `editProjectPitch` / `update` methods (if editing title/description is allowed):
    *   Create `UpdatePitchRequest` Form Request (validation, auth: `$user->can('update', $pitch)`).
    *   Create `updatePitchDetails` method in `PitchWorkflowService`.
    *   Controller delegates to the service.

**D. Refactor `Pitch` Model**

1.  Remove any complex creation logic (event creation, initial status setting) if fully moved to `PitchWorkflowService` or Observers.
2.  Keep relationships, constants, simple accessors/mutators (`readableStatusAttribute`), and query scopes.
3.  Ensure slug generation (`sluggable()`) remains.

**E. Refactor `Project` Model**

1.  Implement `isOpenForPitches()` method (e.g., checks `status === STATUS_OPEN`).
2.  Ensure `userPitch()` method is efficient.

**F. Refactor Frontend (Livewire / Blade)**

1.  **`resources/views/pitches/create.blade.php`:**
    *   Ensure form submits to the correct `store` route (`projects.pitches.store`).
    *   Display validation errors from the session (`@error`).
    *   Display success/error flash messages.
2.  **`app/Livewire/Pitch/Component/ManagePitch.php`:**
    *   This component shows pitch details. Ensure it correctly loads the `Pitch` model and related data (project, user, files, events, snapshots).
    *   If this component allows editing title/description:
        *   Inject `PitchWorkflowService`.
        *   Add properties for editable fields.
        *   Add validation rules (`$rules`).
        *   Add an `updateDetails` method.
        *   Inside `updateDetails`, perform authorization (`$this->authorize('update', $this->pitch)`), validate, call `pitchWorkflowService->updatePitchDetails(...)` in a try/catch, and provide user feedback via Toaster.
3.  **General Views:** Ensure project/pitch links use the correct routes (`projects.show`, `projects.pitches.show`, `projects.manage`).

---

## Step 4: Refactoring Pitch Status Updates

Covers project owner actions: Approve initial pitch, Approve submission, Deny submission, Request Revisions. Also covers producer action: Cancel Submission.

**Target Files:**

*   `app/Http/Controllers/PitchStatusController.php` (Likely to be removed or heavily refactored)
*   `app/Livewire/Pitch/Component/UpdatePitchStatus.php`
*   `app/Livewire/Pitch/Component/ManagePitch.php` (for Cancel Submission)
*   `app/Services/PitchWorkflowService.php`
*   `app/Models/Pitch.php`
*   `app/Models/PitchSnapshot.php`
*   `app/Policies/PitchPolicy.php`
*   `app/Services/NotificationService.php`

**A. Refine `PitchWorkflowService`**

1.  Add methods for each status transition action:
    ```php
    // Inside PitchWorkflowService.php
    use App\Models\PitchSnapshot;
    use App\Exceptions\Pitch\InvalidStatusTransitionException;
    use App\Exceptions\Pitch\SnapshotException;

    // ... (previous methods) ...

    /**
     * Approve an initial pitch application (Pending -> In Progress).
     *
     * @param Pitch $pitch
     * @param User $approvingUser (Project Owner)
     * @return Pitch
     * @throws InvalidStatusTransitionException|UnauthorizedActionException
     */
    public function approveInitialPitch(Pitch $pitch, User $approvingUser): Pitch
    {
        // Authorization check (is user the project owner?)
        if ($pitch->project->user_id !== $approvingUser->id) {
            throw new UnauthorizedActionException('approve initial pitch');
        }
        // Use Policy as well: if ($approvingUser->cannot('approveInitial', $pitch)) { ... }

        if ($pitch->status !== Pitch::STATUS_PENDING) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_IN_PROGRESS, 'Pitch must be pending for initial approval.');
        }

        try {
            return DB::transaction(function () use ($pitch) {
                $pitch->status = Pitch::STATUS_IN_PROGRESS;
                $pitch->save();

                // Create event
                $pitch->events()->create([
                    'event_type' => 'status_change',
                    'comment' => 'Pitch application approved by project owner.',
                    'status' => $pitch->status,
                    'created_by' => Auth::id(), // Assumes auth user is project owner
                ]);

                // Notify pitch creator
                $this->notificationService->notifyPitchApproved($pitch);

                return $pitch;
            });
        } catch (\Exception $e) {
            Log::error('Error approving initial pitch', ['pitch_id' => $pitch->id, 'error' => $e->getMessage()]);
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_IN_PROGRESS, 'Failed to approve pitch.');
        }
    }

    /**
     * Approve a submitted snapshot/pitch (Ready For Review -> Approved).
     *
     * @param Pitch $pitch
     * @param int $snapshotId The ID of the snapshot being approved.
     * @param User $approvingUser (Project Owner)
     * @return Pitch
     * @throws InvalidStatusTransitionException|UnauthorizedActionException|SnapshotException
     */
    public function approveSubmittedPitch(Pitch $pitch, int $snapshotId, User $approvingUser): Pitch
    {
        // Authorization
        if ($pitch->project->user_id !== $approvingUser->id) {
            throw new UnauthorizedActionException('approve submitted pitch');
        }
        // Policy check: $approvingUser->can('approveSubmission', $pitch)

        // Validate status and snapshot state
        $snapshot = $pitch->snapshots()->find($snapshotId);
        if (!$snapshot || $snapshot->status !== PitchSnapshot::STATUS_PENDING) {
             throw new SnapshotException('Snapshot not found or not pending review.');
        }
        if ($pitch->status !== Pitch::STATUS_READY_FOR_REVIEW || $pitch->current_snapshot_id !== $snapshotId) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_APPROVED, 'Pitch must be ready for review with the specified snapshot.');
        }
        // Check for completed/paid status (as in UpdatePitchStatus component)
        if ($this->isPitchPaidAndCompleted($pitch)) {
             throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_APPROVED, 'Paid & completed pitch cannot be modified.');
        }

        try {
             return DB::transaction(function() use ($pitch, $snapshot) {
                // Update Pitch Status
                $pitch->status = Pitch::STATUS_APPROVED;
                $pitch->save();

                // Update Snapshot Status
                $snapshot->status = PitchSnapshot::STATUS_ACCEPTED;
                $snapshot->save();

                // Create event
                $pitch->events()->create([... 'comment' => 'Pitch submission approved.' ...]);

                // Notify pitch creator
                $this->notificationService->notifyPitchSubmissionApproved($pitch, $snapshot);

                return $pitch;
             });
        } catch (\Exception $e) {
            Log::error('Error approving submitted pitch', ['pitch_id' => $pitch->id, 'snapshot_id' => $snapshotId, 'error' => $e->getMessage()]);
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_APPROVED, 'Failed to approve pitch submission.');
        }
    }

     /**
     * Deny a submitted snapshot/pitch (Ready For Review -> Denied).
     *
     * @param Pitch $pitch
     * @param int $snapshotId
     * @param User $denyingUser
     * @param string|null $reason
     * @return Pitch
     * @throws InvalidStatusTransitionException|UnauthorizedActionException|SnapshotException
     */
    public function denySubmittedPitch(Pitch $pitch, int $snapshotId, User $denyingUser, ?string $reason = null): Pitch
    {
        // Auth, Snapshot, Status validation similar to approveSubmittedPitch
        if ($this->isPitchPaidAndCompleted($pitch)) { ... }

         try {
             return DB::transaction(function() use ($pitch, $snapshotId, $reason) {
                $snapshot = PitchSnapshot::findOrFail($snapshotId); // Ensure snapshot exists

                $pitch->status = Pitch::STATUS_DENIED;
                $pitch->save();

                $snapshot->status = PitchSnapshot::STATUS_DENIED;
                $snapshot->save();

                $comment = 'Pitch submission denied.';
                if ($reason) $comment .= " Reason: {$reason}";
                $pitch->events()->create([... 'comment' => $comment ...]);

                $this->notificationService->notifyPitchSubmissionDenied($pitch, $snapshot, $reason);

                return $pitch;
             });
        } catch (\Exception $e) {
             Log::error('Error denying submitted pitch', ...);
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_DENIED, 'Failed to deny pitch submission.');
        }
    }

    /**
     * Request revisions for a submitted snapshot/pitch (Ready For Review -> Revisions Requested).
     *
     * @param Pitch $pitch
     * @param int $snapshotId
     * @param User $requestingUser
     * @param string $feedback
     * @return Pitch
     * @throws InvalidStatusTransitionException|UnauthorizedActionException|SnapshotException
     */
    public function requestPitchRevisions(Pitch $pitch, int $snapshotId, User $requestingUser, string $feedback): Pitch
    {
         // Auth, Snapshot, Status validation similar to approveSubmittedPitch
        if ($this->isPitchPaidAndCompleted($pitch)) { ... }

        if (empty($feedback)) {
            throw new \InvalidArgumentException('Revision feedback cannot be empty.');
        }

        try {
             return DB::transaction(function() use ($pitch, $snapshotId, $feedback) {
                $snapshot = PitchSnapshot::findOrFail($snapshotId);

                $pitch->status = Pitch::STATUS_REVISIONS_REQUESTED;
                $pitch->save();

                $snapshot->status = PitchSnapshot::STATUS_REVISIONS_REQUESTED;
                $snapshot->save();

                $pitch->events()->create([... 'comment' => "Revisions requested. Feedback: {$feedback}" ...]);

                $this->notificationService->notifyPitchRevisionsRequested($pitch, $snapshot, $feedback);

                return $pitch;
            });
        } catch (\Exception $e) {
            Log::error('Error requesting pitch revisions', ...);
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_REVISIONS_REQUESTED, 'Failed to request revisions.');
        }
    }

     /**
     * Cancel a pitch submission (Ready For Review -> In Progress).
     * Action performed by the pitch creator.
     *
     * @param Pitch $pitch
     * @param User $cancellingUser (Pitch Creator)
     * @return Pitch
     * @throws InvalidStatusTransitionException|UnauthorizedActionException|SnapshotException
     */
    public function cancelPitchSubmission(Pitch $pitch, User $cancellingUser): Pitch
    {
        // Authorization
        if ($pitch->user_id !== $cancellingUser->id) {
            throw new UnauthorizedActionException('cancel pitch submission');
        }
        // Policy check: $cancellingUser->can('cancelSubmission', $pitch)

        // Validation
        $snapshot = $pitch->currentSnapshot;
        if ($pitch->status !== Pitch::STATUS_READY_FOR_REVIEW) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_IN_PROGRESS, 'Pitch must be ready for review to cancel submission.');
        }
        if (!$snapshot || $snapshot->status !== PitchSnapshot::STATUS_PENDING) {
             throw new SnapshotException('Cannot cancel submission; the current snapshot is not pending review.');
        }

        try {
            return DB::transaction(function () use ($pitch, $snapshot) {
                $pitch->status = Pitch::STATUS_IN_PROGRESS;
                $pitch->current_snapshot_id = null; // Reset current snapshot ID
                $pitch->save();

                // Mark the snapshot as cancelled to preserve history.
                // Consider adding a 'cancelled' status constant to PitchSnapshot.
                $snapshot->status = 'cancelled'; // Or a dedicated constant
                $snapshot->save();

                $pitch->events()->create([... 'comment' => 'Pitch submission cancelled by creator.' ...]);

                // Notify project owner? Maybe not necessary.

                return $pitch;
            });
        } catch (\Exception $e) {
            Log::error('Error cancelling pitch submission', ['pitch_id' => $pitch->id, 'error' => $e->getMessage()]);
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_IN_PROGRESS, 'Failed to cancel submission.');
        }
    }

    // Helper function
    private function isPitchPaidAndCompleted(Pitch $pitch): bool
    {
        return $pitch->status === Pitch::STATUS_COMPLETED &&
               in_array($pitch->payment_status, [Pitch::PAYMENT_STATUS_PAID, Pitch::PAYMENT_STATUS_PROCESSING]);
    }

    ```

**B. Refactor `Pitch` Model**

1.  Remove status transition validation logic (`canApprove`, `canDeny`, `canRequestRevisions`, `canCancelSubmission`) as this is now handled within the `PitchWorkflowService` methods before attempting the state change. **Action:** Ensure these methods are removed from the `Pitch` model.
2.  Keep status constants and basic properties.
3.  Consider adding query scopes for statuses if not already present (`scopeReadyForReview`, `scopePending`, etc.).

**C. Refactor/Remove `PitchStatusController`**

*   This controller is likely redundant now. Actions are handled by Livewire components delegating to the `PitchWorkflowService`.
*   Review if any routes point to this controller. If so, update them to point to new controller actions (if needed for non-Livewire contexts) or remove them if Livewire handles everything.
*   If kept for specific API endpoints, ensure it uses the `PitchWorkflowService` and appropriate Form Requests/Authorization.

**D. Refactor `UpdatePitchStatus` Livewire Component**

1.  Inject `PitchWorkflowService`.
2.  Remove internal validation logic (`canApprove`, etc.).
3.  Modify action methods (`approveSnapshot`, `denySnapshot`, `requestRevisions`) to:
    *   Perform minimal UI-level checks (e.g., ensure reason/feedback is provided).
    *   Call the corresponding `PitchWorkflowService` method within a `try/catch` block.
    *   Use `$this->authorize(...)` *before* calling the service method (using policy methods like `approveInitial`, `approveSubmission`, `denySubmission`, `requestRevisions`).
    *   Handle specific exceptions thrown by the service (`InvalidStatusTransitionException`, `UnauthorizedActionException`, `SnapshotException`, `InvalidArgumentException`) and display appropriate Toaster messages.
    *   On success, dispatch events (`pitchStatusUpdated`, `snapshot-status-updated`) or trigger `$refresh` as needed, or rely on redirects if preferred.
    *   **Example (`approveSnapshot` method):**
        ```php
        // Inside UpdatePitchStatus.php
        use App\Services\PitchWorkflowService;
        use App\Exceptions\Pitch\InvalidStatusTransitionException;
        use App\Exceptions\Pitch\UnauthorizedActionException;
        use App\Exceptions\Pitch\SnapshotException;
        use Masmerise\Toaster\Toaster;

        // ... properties ...
        public $currentSnapshotIdToActOn;

        public function confirmApproveSnapshot($data)
        {
            $snapshotId = $data['snapshotId'];
            $this->currentSnapshotIdToActOn = $snapshotId; // Store for the action method
            // Trigger confirmation dialog if needed, or call approveSnapshot directly
            $this->approveSnapshot(app(PitchWorkflowService::class)); // Pass service via method injection
        }

        public function approveSnapshot(PitchWorkflowService $pitchWorkflowService)
        {
            if (!$this->currentSnapshotIdToActOn) return;

            try {
                $this->authorize('approveSubmission', $this->pitch);

                $pitchWorkflowService->approveSubmittedPitch(
                    $this->pitch,
                    $this->currentSnapshotIdToActOn,
                    auth()->user()
                );

                Toaster::success('Pitch approved successfully!');
                $this->dispatch('pitchStatusUpdated'); // Notify parent/other components
                $this->dispatch('snapshot-status-updated');
                $this->currentSnapshotIdToActOn = null;
                // Optionally redirect: return redirect()->route('projects.manage', $this->pitch->project);
            } catch (UnauthorizedActionException $e) {
                Toaster::error('You are not authorized to approve this pitch.');
            } catch (InvalidStatusTransitionException | SnapshotException $e) {
                Toaster::error($e->getMessage());
            } catch (\Exception $e) {
                Log::error('Error approving pitch via Livewire', ['pitch_id' => $this->pitch->id, 'error' => $e->getMessage()]);
                Toaster::error('An unexpected error occurred.');
            }
        }

        // Similar refactoring for denySnapshot, requestRevisions methods
        ```

**E. Refactor `ManagePitch` Livewire Component (for Cancel Submission)**

1.  Inject `PitchWorkflowService`.
2.  Add a `cancelSubmission` method.
3.  Inside the method:
    *   Perform authorization: `$this->authorize('cancelSubmission', $this->pitch);`
    *   Call `pitchWorkflowService->cancelPitchSubmission($this->pitch, auth()->user())` in a `try/catch`.
    *   Handle exceptions (`InvalidStatusTransitionException`, `UnauthorizedActionException`, `SnapshotException`) with Toaster messages.
    *   Refresh component state or redirect as needed on success.

**F. Update Policies (`PitchPolicy`)**

1.  Ensure policy methods exist and correctly check permissions for:
    *   `approveInitial(User $user, Pitch $pitch)`
    *   `approveSubmission(User $user, Pitch $pitch)`
    *   `denySubmission(User $user, Pitch $pitch)`
    *   `requestRevisions(User $user, Pitch $pitch)`
    *   `cancelSubmission(User $user, Pitch $pitch)`
    *   **Action:** These specific methods likely need to be *added* to `app/Policies/PitchPolicy.php` as they probably don't exist yet. They should check user roles (project owner vs pitch creator) and potentially the pitch's current status.

---

## Step 5: Refactoring File Management

Covers uploading, deleting, and potentially downloading files for Projects and Pitches. Centralizes file handling logic and storage checks.

**Target Files:**

*   `app/Http/Controllers/ProjectController.php` (`storeTrack`, `storeStep2`, `deleteFile`)
*   `app/Http/Controllers/PitchFileController.php`
*   `app/Http/Controllers/FileDownloadController.php` (If used for secure downloads)
*   `app/Livewire/ManageProject.php`
*   `app/Livewire/Pitch/Component/ManagePitch.php`
*   `app/Models/Project.php`
*   `app/Models/Pitch.php`
*   `app/Models/ProjectFile.php`
*   `app/Models/PitchFile.php`
*   `app/Services/FileManagementService.php` (New)
*   Associated views/components displaying files (e.g., file lists, audio players).

**A. Create `FileManagementService`**

1.  Create `app/Services/FileManagementService.php`.
2.  Define methods for core file operations:
    ```php
    <?php
    namespace App\Services;

    use App\Models\Project;
    use App\Models\Pitch;
    use App\Models\ProjectFile;
    use App\Models\PitchFile;
    use App\Models\User;
    use Illuminate\Http\UploadedFile;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Log;
    use App\Exceptions\File\FileUploadException;
    use App\Exceptions\File\StorageLimitException;
    use App\Exceptions\File\FileDeletionException;
    use App\Exceptions\Pitch\UnauthorizedActionException;

    class FileManagementService
    {
        /**
         * Upload a file for a Project.
         *
         * @param Project $project
         * @param UploadedFile $file
         * @param User $uploader
         * @return ProjectFile
         * @throws FileUploadException|StorageLimitException|UnauthorizedActionException
         */
        public function uploadProjectFile(Project $project, UploadedFile $file, User $uploader): ProjectFile
        {
            // Authorization (e.g., only project owner uploads? Check policy)
            // if ($uploader->cannot('uploadFile', $project)) { ... }

            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();

            // Validate file size and project storage capacity
            if (!Project::isFileSizeAllowed($fileSize)) { // Keep simple check in Model
                throw new FileUploadException("File '{$fileName}' exceeds the maximum size limit.");
            }
            if (!$project->hasStorageCapacity($fileSize)) { // Keep simple check in Model
                throw new StorageLimitException('Project storage limit reached. Cannot upload file.');
            }

            try {
                return DB::transaction(function () use ($project, $file, $fileName, $fileSize) {
                    $path = $file->store('projects/' . $project->id, 's3'); // Use unique names later if needed
                    Log::info('Project file uploaded to S3', ['filename' => $fileName, 'path' => $path, 'project_id' => $project->id]);

                    $projectFile = $project->files()->create([
                        'file_path' => $path,
                        // 'file_name' => $fileName, // Store original name if needed
                        'size' => $fileSize,
                        // 'user_id' => $uploader->id, // If tracking uploader is needed
                    ]);

                    // Atomically update project storage usage
                    $project->incrementStorageUsed($fileSize);

                    // Optionally delete cached ZIP if project files change
                    // $project->deleteCachedZip();

                    // Dispatch event if needed (e.g., ProjectFileUploaded)

                    return $projectFile;
                });
            } catch (\Exception $e) {
                Log::error('Error uploading project file', ['project_id' => $project->id, 'filename' => $fileName, 'error' => $e->getMessage()]);
                throw new FileUploadException("Failed to upload file '{$fileName}'.", 0, $e);
            }
        }

        /**
         * Upload a file for a Pitch.
         *
         * @param Pitch $pitch
         * @param UploadedFile $file
         * @param User $uploader (Pitch owner)
         * @return PitchFile
         * @throws FileUploadException|StorageLimitException|UnauthorizedActionException|
         */
        public function uploadPitchFile(Pitch $pitch, UploadedFile $file, User $uploader): PitchFile
        {
            // Authorization (Only pitch owner?)
            if ($uploader->id !== $pitch->user_id) {
                 throw new UnauthorizedActionException('upload file to this pitch');
            }
             // Policy Check: if ($uploader->cannot('uploadFile', $pitch)) { ... }

            // Validation (Pitch status must allow uploads: e.g., in_progress)
             if (!in_array($pitch->status, [Pitch::STATUS_IN_PROGRESS, Pitch::STATUS_REVISIONS_REQUESTED /* or others? */])) {
                 throw new FileUploadException('Cannot upload files when pitch status is ' . $pitch->readableStatusAttribute);
             }

            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();

            // File size / Pitch storage limits
            if (!Pitch::isFileSizeAllowed($fileSize)) { ... }
            if (!$pitch->hasStorageCapacity($fileSize)) { ... }

            try {
                return DB::transaction(function () use ($pitch, $file, $fileName, $fileSize, $uploader) {
                    $path = $file->store('pitch_files/' . $pitch->id, 's3');
                    Log::info('Pitch file uploaded to S3', ['filename' => $fileName, 'path' => $path, 'pitch_id' => $pitch->id]);

                    $pitchFile = $pitch->files()->create([
                        'file_path' => $path,
                        // 'file_name' => $fileName,
                        'size' => $fileSize,
                        'user_id' => $uploader->id,
                    ]);

                    // Update pitch storage usage
                    $pitch->incrementStorageUsed($fileSize);

                    // Dispatch event (e.g., PitchFileUploaded)
                    // event(new PitchFileUploaded($pitchFile));
                    
                    // If file upload triggers waveform generation
                    // dispatch(new \App\Jobs\GenerateAudioWaveform($pitchFile));

                    return $pitchFile;
                });
            } catch (\Exception $e) {
                Log::error('Error uploading pitch file', ['pitch_id' => $pitch->id, 'filename' => $fileName, 'error' => $e->getMessage()]);
                throw new FileUploadException("Failed to upload file '{$fileName}'.", 0, $e);
            }
        }

        /**
         * Delete a Project file.
         *
         * @param ProjectFile $projectFile
         * @param User $deleter
         * @return bool
         * @throws FileDeletionException|UnauthorizedActionException
         */
        public function deleteProjectFile(ProjectFile $projectFile, User $deleter): bool
        {
            $project = $projectFile->project;
            // Authorization (Project Owner?)
            if ($deleter->cannot('deleteFile', $project)) { ... }

            try {
                 DB::transaction(function () use ($projectFile, $project) {
                    $filePath = $projectFile->file_path;
                    $fileSize = $projectFile->size;

                    // Delete DB record
                    $deleted = $projectFile->delete();

                    if ($deleted) {
                        // Decrement storage used
                        $project->decrementStorageUsed($fileSize);

                        // Delete file from S3
                        try {
                           Storage::disk('s3')->delete($filePath);
                           Log::info('Project file deleted from S3', ['path' => $filePath, 'project_id' => $project->id]);
                        } catch (\Exception $storageEx) {
                            Log::error('Failed to delete project file from S3', ['path' => $filePath, 'error' => $storageEx->getMessage()]);
                            // Decide: Throw exception? Or just log? Usually log, as DB record is gone.
                        }
                         // Dispatch event (e.g., ProjectFileDeleted)
                    } else {
                         throw new FileDeletionException('Failed to delete file record from database.');
                    }
                });
                return true;
            } catch (\Exception $e) {
                Log::error('Error deleting project file', ['file_id' => $projectFile->id, 'error' => $e->getMessage()]);
                throw new FileDeletionException('Failed to delete project file.', 0, $e);
            }
        }

        /**
         * Delete a Pitch file.
         *
         * @param PitchFile $pitchFile
         * @param User $deleter (Pitch Owner)
         * @return bool
         * @throws FileDeletionException|UnauthorizedActionException
         */
        public function deletePitchFile(PitchFile $pitchFile, User $deleter): bool
        {
            $pitch = $pitchFile->pitch;
            // Authorization (Pitch Owner?)
            if ($deleter->id !== $pitch->user_id) { ... }
            // Policy: if ($deleter->cannot('deleteFile', $pitchFile)) { ... }
            
            // Validation: Can delete only if pitch status allows?
            if (!in_array($pitch->status, [Pitch::STATUS_IN_PROGRESS, Pitch::STATUS_REVISIONS_REQUESTED /* others? */])) {
                throw new FileDeletionException('Cannot delete files when pitch status is ' . $pitch->readableStatusAttribute);
            }

            try {
                 DB::transaction(function () use ($pitchFile, $pitch) {
                    $filePath = $pitchFile->file_path;
                    $fileSize = $pitchFile->size;

                    $deleted = $pitchFile->delete();
                    if ($deleted) {
                        $pitch->decrementStorageUsed($fileSize);
                        try {
                           Storage::disk('s3')->delete($filePath);
                           Log::info('Pitch file deleted from S3', ['path' => $filePath, 'pitch_id' => $pitch->id]);
                        } catch (\Exception $storageEx) {
                            Log::error('Failed to delete pitch file from S3', ...);
                        }
                        // Dispatch event
                    } else {
                         throw new FileDeletionException('Failed to delete file record from database.');
                    }
                 });
                return true;
            } catch (\Exception $e) {
                Log::error('Error deleting pitch file', ['file_id' => $pitchFile->id, 'error' => $e->getMessage()]);
                throw new FileDeletionException('Failed to delete pitch file.', 0, $e);
            }
        }

        /**
         * Generate a temporary URL for downloading a file.
         *
         * @param ProjectFile|PitchFile $fileModel
         * @param User $requester
         * @param int $minutes Expiration time for the URL.
         * @return string
         * @throws UnauthorizedActionException|\Exception
         */
        public function getTemporaryDownloadUrl($fileModel, User $requester, int $minutes = 15): string
        {
            // Authorization: This should primarily be handled by Policies.
            // Ensure a Policy exists (e.g., ProjectFilePolicy, PitchFilePolicy)
            // with a 'download' method.
            if ($requester->cannot('download', $fileModel)) {
                 throw new UnauthorizedActionException('download this file');
            }

            // The detailed logic below is illustrative of checks the policy *might* perform:
            // $canAccess = false;
            // if ($fileModel instanceof ProjectFile) {
            //     $project = $fileModel->project;
            //     if ($requester->id === $project->user_id /* || user is involved pitch creator */ ) {
            //         $canAccess = true;
            //     }
            // } elseif ($fileModel instanceof PitchFile) {
            //     $pitch = $fileModel->pitch;
            //     if ($requester->id === $pitch->user_id || $requester->id === $pitch->project->user_id) {
            //          $canAccess = true;
            //     }
            // }
            // if (!$canAccess) {
            //     throw new UnauthorizedActionException('download this file');
            // }


            try {
                return Storage::disk('s3')->temporaryUrl(
                    $fileModel->file_path,
                    now()->addMinutes($minutes)
                );
            } catch (\Exception $e) {
                 Log::error('Error generating temporary URL', ['file_path' => $fileModel->file_path, 'error' => $e->getMessage()]);
                throw $e; // Re-throw
            }
        }
    }
    ```

**B. Create Form Requests (Optional for Controller Actions)**

*   If using controller actions for uploads (less likely with Livewire): `UploadProjectFileRequest`, `UploadPitchFileRequest` with rules for file type/size and authorization.

**C. Refactor Controllers**

1.  **`ProjectController`:**
    *   Remove `storeTrack`, `storeStep2`, `deleteFile` methods. This logic is now handled by `FileManagementService` called from Livewire components.
2.  **`PitchFileController`:**
    *   Inject `FileManagementService`.
    *   Refactor `store` (upload) and `destroy` (delete) methods to:
        *   Use Form Requests (if applicable) or perform auth checks directly.
        *   Delegate file operations to `FileManagementService` methods.
        *   Handle exceptions and return appropriate JSON responses or redirects.
    *   Refactor `download` method (if exists) to use `FileManagementService->getTemporaryDownloadUrl` and return a redirect to the temporary URL.

**D. Refactor Models (`Project`, `Pitch`)**

1.  Remove methods directly handling file storage logic (`deleteProjectImage` from `Project`, potentially complex storage calculation helpers).
2.  Keep constants (`MAX_FILE_SIZE_BYTES`, `MAX_STORAGE_BYTES`).
3.  Keep basic calculation helpers (`isFileSizeAllowed`, `hasStorageCapacity`) called by the service.
4.  Add `incrementStorageUsed(int $bytes)` and `decrementStorageUsed(int $bytes)` methods using atomic increments/decrements (`increment('total_storage_used', $bytes)`).

**E. Refactor Livewire Components (`ManageProject`, `ManagePitch`)**

1.  Inject `FileManagementService`.
2.  **File Upload Logic:**
    *   Modify upload actions (e.g., `ManageProject::uploadFiles`, `ManagePitch::processQueuedFiles` / `uploadSingleFile`):
        *   Perform authorization checks (`$this->authorize(...)`).
        *   Loop through the files to be uploaded.
        *   Inside the loop, call `fileManagementService->uploadProjectFile(...)` or `fileManagementService->uploadPitchFile(...)` within a `try/catch` block.
        *   Handle `FileUploadException` and `StorageLimitException` specifically to provide feedback (Toaster messages) for individual file failures.
        *   Handle generic `Exception`.
        *   Update the UI progressively (e.g., marking files as uploaded or failed).
        *   **Note on Batch/Sequential Uploads:** For complex uploaders (like `ManagePitch`'s potential sequential logic), ensure the call to the `FileManagementService` method happens *for each individual file* as it's processed in the sequence. The component's state should accurately track the success or failure of each service call and update the UI accordingly. Error handling needs to accommodate failures at any point in the sequence.
3.  **File Deletion Logic:**
    *   Modify delete actions (e.g., `ManageProject::deleteFile`, and add `deletePitchFile` to `ManagePitch`):
        *   Take the `ProjectFile` or `PitchFile` ID.
        *   Fetch the model (`ProjectFile::find($fileId)`).
        *   Perform authorization (`$this->authorize('delete', $fileModel)`).
        *   Call `fileManagementService->deleteProjectFile(...)` or `fileManagementService->deletePitchFile(...)` in a `try/catch` block.
        *   Handle `FileDeletionException` and `UnauthorizedActionException` with Toaster messages.
        *   Refresh the file list on success (e.g., `$this->project->refresh()`, `$this->pitch->refresh()`).
4.  **File Listing & Downloads:**
    *   Ensure file lists are correctly displayed using the model relationships (`$project->files`, `$pitch->files`).
    *   For download links/buttons:
        *   Create a Livewire action (e.g., `getDownloadUrl($fileId)`).
        *   Inside the action, perform authorization (ideally `$this->authorize('download', $fileModel)`), call `fileManagementService->getTemporaryDownloadUrl(...)` in try/catch.
        *   Dispatch a browser event with the URL (e.g., `dispatch('openUrl', url: $url)`), which JS can use to open the download link in a new tab.

**F. Update Policies (`ProjectPolicy`, `PitchPolicy`, create `PitchFilePolicy`)**

1.  **Action:** Add methods like `uploadFile(User $user, Project $project)`, `deleteFile(User $user, Project $project)`, `downloadFile(...)` to the appropriate policy (`ProjectPolicy`).
2.  **Action:** Add similar methods to `PitchPolicy` or create a new `app/Policies/PitchFilePolicy.php` (and register it) for `uploadFile`, `deleteFile`, `downloadFile` specific to pitch files and their related pitch/project context. These methods likely need to be *created*.

---

## Step 6: Refactoring Pitch Submission (Snapshots)

Covers the producer submitting their pitch (with files) for review, which creates a snapshot and changes the pitch status.

**Target Files:**

*   `app/Livewire/Pitch/Component/ManagePitch.php`
*   `app/Services/PitchWorkflowService.php`
*   `app/Models/Pitch.php`
*   `app/Models/PitchSnapshot.php`
*   `app/Policies/PitchPolicy.php`
*   `app/Services/NotificationService.php`

**A. Refine `PitchWorkflowService`**

1.  Add `submitPitchForReview` method:
    ```php
    // Inside PitchWorkflowService.php
    use App\Exceptions\Pitch\SubmissionValidationException;

    // ... (other methods) ...

    /**
     * Submit a pitch for review.
     *
     * @param Pitch $pitch
     * @param User $submitter (Pitch Owner)
     * @param string|null $responseToFeedback Optional message when resubmitting after revisions.
     * @return Pitch
     * @throws SubmissionValidationException|InvalidStatusTransitionException|UnauthorizedActionException
     */
    public function submitPitchForReview(Pitch $pitch, User $submitter, ?string $responseToFeedback = null): Pitch
    {
        // Authorization
        if ($pitch->user_id !== $submitter->id) {
            throw new UnauthorizedActionException('submit this pitch for review');
        }
        // Policy: if ($submitter->cannot('submitForReview', $pitch)) { ... }

        // Validation
        if (!in_array($pitch->status, [Pitch::STATUS_IN_PROGRESS, Pitch::STATUS_REVISIONS_REQUESTED, Pitch::STATUS_DENIED /* Denied can be resubmitted? */])) {
            throw new InvalidStatusTransitionException($pitch->status, Pitch::STATUS_READY_FOR_REVIEW, 'Pitch cannot be submitted from its current status.');
        }
        if ($pitch->files()->count() === 0) {
            throw new SubmissionValidationException('Cannot submit pitch for review with no files attached.');
        }
        if ($pitch->status === Pitch::STATUS_REVISIONS_REQUESTED && empty($responseToFeedback)) {
             // Optional: Require feedback response when resubmitting after revisions requested.
             // throw new SubmissionValidationException('Please provide a response to the revision feedback.');
        }

        try {
            return DB::transaction(function() use ($pitch, $responseToFeedback, $submitter) {
                $previousSnapshot = $pitch->currentSnapshot;
                $newVersion = $previousSnapshot ? ($previousSnapshot->snapshot_data['version'] ?? 0) + 1 : 1;

                // Create new Snapshot
                $snapshotData = [
                    'version' => $newVersion,
                    'file_ids' => $pitch->files->pluck('id')->toArray(),
                    'response_to_feedback' => $responseToFeedback,
                    'previous_snapshot_id' => $previousSnapshot?->id,
                ];

                $newSnapshot = $pitch->snapshots()->create([
                    'project_id' => $pitch->project_id,
                    'user_id' => $submitter->id,
                    'snapshot_data' => $snapshotData,
                    'status' => PitchSnapshot::STATUS_PENDING, // Initially pending review
                ]);

                // Update Pitch
                $pitch->status = Pitch::STATUS_READY_FOR_REVIEW;
                $pitch->current_snapshot_id = $newSnapshot->id;
                $pitch->save();

                // Update previous snapshot status if applicable
                if ($previousSnapshot && $pitch->getOriginal('status') === Pitch::STATUS_REVISIONS_REQUESTED) {
                    // Mark the snapshot that *received* the revisions feedback as addressed
                    $previousSnapshot->status = PitchSnapshot::STATUS_REVISION_ADDRESSED;
                    $previousSnapshot->save();
                }

                // Create Event
                $comment = 'Pitch submitted for review (Version ' . $newVersion . ').';
                if ($responseToFeedback) $comment .= " Response: {$responseToFeedback}";
                $pitch->events()->create([... 'comment' => $comment, 'snapshot_id' => $newSnapshot->id ...]);

                // Notify Project Owner
                $this->notificationService->notifyPitchReadyForReview($pitch, $newSnapshot);

                return $pitch;
            });
        } catch (\Exception $e) {
            Log::error('Error submitting pitch for review', ['pitch_id' => $pitch->id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to submit pitch for review.', 0, $e);
        }
    }
    ```

**B. Refactor `Pitch` Model**

1.  **Action:** Remove `createSnapshot` method if its logic is fully covered by the service, as it likely duplicates logic from `ManagePitch::submitForReview`.
2.  Keep relationships (`snapshots`, `currentSnapshot`).

**C. Refactor `ManagePitch` Livewire Component**

1.  Inject `PitchWorkflowService`.
2.  Add a `submitForReview` method.
3.  This method should:
    *   Perform authorization: `$this->authorize('submitForReview', $this->pitch);`
    *   Get any necessary data from component properties (e.g., `$this->responseToFeedback` if collecting it in the UI).
    *   Call `pitchWorkflowService->submitPitchForReview($this->pitch, auth()->user(), $this->responseToFeedback)` within a `try/catch` block.
    *   Handle exceptions (`SubmissionValidationException`, `InvalidStatusTransitionException`, `UnauthorizedActionException`) with Toaster messages.
    *   On success, dispatch events (`pitchStatusUpdated`, potentially `projectStatusUpdated`).
    *   If payment is required (`$this->pitch->payment_status === Pitch::PAYMENT_STATUS_PENDING`), dispatch an event to trigger the payment flow (e.g., `dispatch('openPaymentModal')`).
    *   Close the completion modal (`$this->closeCompletionModal()`).
    *   Redirect if necessary.

**D. Update Policies (`PitchPolicy`)**

1.  **Action:** Ensure `submitForReview(User $user, Pitch $pitch)` policy method *is added* to `app/Policies/PitchPolicy.php`. It should check if the user is the pitch owner and if the pitch is in a submittable state.

---

## Step 7: Refactoring Pitch Completion

Covers the project owner marking an approved pitch as complete, closing other pitches, and updating project status.

**Target Files:**

*   `app/Livewire/Pitch/Component/CompletePitch.php`
*   `app/Services/PitchCompletionService.php` (New)
*   `app/Services/PitchWorkflowService.php`
*   `app/Services/ProjectManagementService.php`
*   `app/Models/Pitch.php`
*   `app/Models/PitchSnapshot.php`
*   `app/Models/Project.php`
*   `app/Models/PitchFeedback.php` (If feedback is stored separately)
*   `app/Policies/PitchPolicy.php`
*   `app/Services/NotificationService.php`

**A. Create `PitchCompletionService`**

1.  Create `app/Services/PitchCompletionService.php`.
2.  Define the main `completePitch` method:
    ```php
    <?php
    namespace App\Services;

    use App\Models\Pitch;
    use App\Models\Project;
    use App\Models\User;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Log;
    use App\Exceptions\Pitch\CompletionValidationException;
    use App\Exceptions\Pitch\UnauthorizedActionException;

    class PitchCompletionService
    {
        protected $projectManagementService;
        protected $notificationService;

        public function __construct(
            ProjectManagementService $projectManagementService,
            NotificationService $notificationService
        ) {
            $this->projectManagementService = $projectManagementService;
            $this->notificationService = $notificationService;
        }

        /**
         * Mark a pitch as completed, close others, and complete the project.
         *
         * @param Pitch $pitchToComplete The pitch being marked as complete.
         * @param User $completingUser (Project Owner)
         * @param string|null $feedback Optional feedback.
         * @return Pitch The completed pitch.
         * @throws CompletionValidationException|UnauthorizedActionException
         */
        public function completePitch(Pitch $pitchToComplete, User $completingUser, ?string $feedback = null): Pitch
        {
            $project = $pitchToComplete->project;

            // Authorization
            if ($project->user_id !== $completingUser->id) {
                throw new UnauthorizedActionException('complete this pitch');
            }
            // Policy: if ($completingUser->cannot('complete', $pitchToComplete)) { ... }

            // Validation
            if ($pitchToComplete->status !== Pitch::STATUS_APPROVED) {
                throw new CompletionValidationException('Pitch must be approved before it can be completed.');
            }
            // Prevent re-completion if already paid/processing?
            if ($pitchToComplete->payment_status === Pitch::PAYMENT_STATUS_PAID || $pitchToComplete->payment_status === Pitch::PAYMENT_STATUS_PROCESSING) {
                 throw new CompletionValidationException('This pitch has already been completed and paid/is processing payment.');
            }

            try {
                DB::transaction(function() use ($pitchToComplete, $project, $completingUser, $feedback) {
                    // 1. Mark the selected pitch as completed
                    $pitchToComplete->status = Pitch::STATUS_COMPLETED;
                    $pitchToComplete->completed_at = now();
                    $pitchToComplete->completion_feedback = $feedback; // Or store in PitchFeedback model
                    
                    // Set initial payment status
                    if ($project->budget > 0) {
                        $pitchToComplete->payment_status = Pitch::PAYMENT_STATUS_PENDING;
                    } else {
                         $pitchToComplete->payment_status = Pitch::PAYMENT_STATUS_NOT_REQUIRED;
                    }
                    $pitchToComplete->save();

                    // 2. Update the final snapshot status
                    if ($pitchToComplete->currentSnapshot) {
                        $pitchToComplete->currentSnapshot->status = PitchSnapshot::STATUS_COMPLETED;
                        $pitchToComplete->currentSnapshot->save();
                    }

                    // 3. Close other active pitches for the same project
                    $otherPitches = $project->pitches()
                        ->where('id', '!=', $pitchToComplete->id)
                        ->whereNotIn('status', [Pitch::STATUS_COMPLETED, Pitch::STATUS_CLOSED, Pitch::STATUS_DENIED]) // Close pending, in_progress, approved etc.
                        ->get();

                    foreach ($otherPitches as $otherPitch) {
                        $originalStatus = $otherPitch->status;
                        $otherPitch->status = Pitch::STATUS_CLOSED;
                        $otherPitch->save();

                        // Decline any pending snapshots for closed pitches
                        if ($otherPitch->currentSnapshot && $otherPitch->currentSnapshot->status === PitchSnapshot::STATUS_PENDING) {
                             $otherPitch->currentSnapshot->status = PitchSnapshot::STATUS_DENIED; // Or maybe 'cancelled'?
                             $otherPitch->currentSnapshot->save();
                        }
                        
                        Log::info('Pitch closed due to project completion', ['pitch_id' => $otherPitch->id, 'project_id' => $project->id]);
                        // Notify creator of closed pitch
                        $this->notificationService->notifyPitchClosed($otherPitch);
                    }

                    // 4. Mark the project as completed (using the service)
                    $this->projectManagementService->completeProject($project);

                    // 5. Create Event for completed pitch
                     $pitchToComplete->events()->create([... 'comment' => 'Pitch marked as completed by project owner.' ...]);
                    
                     // 6. Notify creator of completed pitch
                    $this->notificationService->notifyPitchCompleted($pitchToComplete);

                });

                return $pitchToComplete->refresh();

            } catch (\Exception $e) {
                Log::error('Error completing pitch', ['pitch_id' => $pitchToComplete->id, 'error' => $e->getMessage()]);
                throw new \RuntimeException('Failed to complete pitch.', 0, $e);
            }
        }
    }
    ```

**B. Refactor `CompletePitch` Livewire Component**

1.  Inject `PitchCompletionService`.
2.  Refactor the `completePitch` / `debugComplete` method:
    *   Perform authorization: `$this->authorize('complete', $this->pitch);`
    *   Call `pitchCompletionService->completePitch($this->pitch, auth()->user(), $this->feedback)` within a `try/catch` block.
    *   Handle exceptions (`CompletionValidationException`, `UnauthorizedActionException`, `RuntimeException`) with Toaster messages.
    *   On success:
        *   Dispatch events (`pitchStatusUpdated`, potentially `projectStatusUpdated`).
        *   If payment is required (`$this->pitch->payment_status === Pitch::PAYMENT_STATUS_PENDING`), dispatch an event to trigger the payment flow (e.g., `dispatch('openPaymentModal')`).
        *   Close the completion modal (`$this->closeCompletionModal()`).
        *   Redirect if necessary.

**C. Refactor `Pitch` Model**

1.  **Action:** Remove `canComplete` validation method if moved to service. Ensure any direct calls to Project completion logic (like `Project::markAsCompleted`) are replaced by calls to `ProjectManagementService->completeProject` within the `PitchCompletionService`.

**D. Update Policies (`PitchPolicy`)**

1.  **Action:** Ensure `complete(User $user, Pitch $pitch)` policy method *is added* to `app/Policies/PitchPolicy.php`. It should check if the user is the project owner and the pitch is in the `approved` state.

---

## Step 8: Refactoring Payment Processing

Covers handling payments for completed pitches using Stripe via Cashier/InvoiceService.

**Target Files:**

*   `app/Services/InvoiceService.php`
*   `app/Services/PitchWorkflowService.php` (Potentially add payment status updates)
*   `app/Http/Controllers/PitchPaymentController.php`
*   `app/Http/Controllers/WebhookController.php` (Assumed for Stripe webhooks)
*   `app/Models/Pitch.php`
*   Livewire components or Blade views handling payment forms/modals.

**A. Review and Refine `InvoiceService`**

1.  **Error Handling:** Ensure methods like `createPitchInvoice` and `processInvoicePayment` catch specific Stripe exceptions (e.g., `\Stripe\Exception\CardException`, `\Stripe\Exception\ApiErrorException`) and re-throw them as custom application exceptions (e.g., `PaymentProcessingFailedException`) or handle them gracefully.
2.  **Atomicity:** While Stripe operations are external, ensure any related local database updates (like storing the `invoice_id` on the pitch *after* successful invoice creation) happen reliably. Consider if `createPitchInvoice` should be called *within* the `PitchCompletionService` transaction if an invoice needs to be created immediately upon completion.
    *   **Alternative:** Create the invoice *after* completion, triggered by the payment UI.
3.  **Invoice Metadata:** Ensure metadata includes `pitch_id` and `project_id` consistently for reconciliation, especially via webhooks.

**B. Create Service Method for Updating Pitch Payment Status**

1.  Add a method, potentially in `PitchWorkflowService`, to handle payment status updates based on Stripe results (likely called from webhook handler or after synchronous payment).
    ```php
    // Inside PitchWorkflowService.php

    /**
     * Mark a pitch as paid.
     *
     * @param Pitch $pitch
     * @param string $stripeInvoiceId
     * @param string $stripeChargeId // Or PaymentIntent ID
     * @return Pitch
     */
    public function markPitchAsPaid(Pitch $pitch, string $stripeInvoiceId, string $stripeChargeId): Pitch
    {
        // Add validation if needed (e.g., ensure pitch status is Completed)
        if ($pitch->status !== Pitch::STATUS_COMPLETED) {
            Log::warning('Attempted to mark non-completed pitch as paid.', ['pitch_id' => $pitch->id]);
            // Decide: throw exception or just return?
            return $pitch;
        }
        
        // Ensure idempotency - don't re-process if already paid
        if ($pitch->payment_status === Pitch::PAYMENT_STATUS_PAID) {
            return $pitch;
        }

        try {
             // No transaction needed if only updating one model atomically
            $pitch->payment_status = Pitch::PAYMENT_STATUS_PAID;
            $pitch->final_invoice_id = $stripeInvoiceId; // Store Stripe Invoice ID
            $pitch->payment_completed_at = now();
            // Store $stripeChargeId if needed
            $pitch->save();

            // Create event
            $pitch->events()->create([... 'comment' => 'Payment completed successfully. Invoice: ' . $stripeInvoiceId ...]);

            // Notify user/owner?
            $this->notificationService->notifyPaymentSuccessful($pitch);

            return $pitch;
        } catch (\Exception $e) {
            Log::error('Failed to mark pitch as paid', ['pitch_id' => $pitch->id, 'invoice_id' => $stripeInvoiceId, 'error' => $e->getMessage()]);
            // Maybe throw a specific exception?
            throw $e;
        }
    }
     /**
     * Mark a pitch payment as failed.
     */
    public function markPitchPaymentFailed(Pitch $pitch, string $stripeInvoiceId, string $failureReason = 'Unknown'): Pitch
    {
         // Validation, Idempotency...
         $pitch->payment_status = Pitch::PAYMENT_STATUS_FAILED;
         $pitch->final_invoice_id = $stripeInvoiceId;
         $pitch->save();
         // Event, Notification...
         $this->notificationService->notifyPaymentFailed($pitch, $failureReason);
         return $pitch;
    }
    ```

**C. Refactor `PitchPaymentController`**

1.  Inject `InvoiceService` and `PitchWorkflowService`.
2.  Refactor `store` / `processPayment` method (or similar action handling payment submission):
    *   Use a Form Request (`ProcessPitchPaymentRequest`) for validation (payment method ID, pitch ID) and authorization (ensure user is project owner).
    *   Fetch the `Pitch` model.
    *   Call `invoiceService->createPitchInvoice(...)` to get/create the Stripe invoice if not already done.
    *   Call `invoiceService->processInvoicePayment(...)` with the invoice object and payment method.
    *   **Crucially:** Handle the result from `processInvoicePayment`:
        *   On success: Call `pitchWorkflowService->markPitchAsPaid(...)` with details from the successful Stripe payment result.
        *   On failure: Call `pitchWorkflowService->markPitchPaymentFailed(...)`. Handle specific Stripe exceptions caught from the service.
    *   Return appropriate JSON responses or redirects with user feedback.

**D. Implement/Refactor Stripe Webhook Handling**

1.  Ensure a route exists (`/stripe/webhooks`) pointing to a `WebhookController` (likely `app/Http/Controllers/Billing/WebhookController.php`).
2.  Use `laravel/cashier`'s built-in webhook handling or implement manually.
3.  **Action:** In the method handling `invoice.payment_succeeded` (likely `handleInvoicePaymentSucceeded`):
    *   Verify the webhook signature (handled by Cashier's base controller).
    *   Extract the Stripe Invoice object from the event payload.
    *   Retrieve the `pitch_id` from the invoice metadata.
    *   Fetch the corresponding `Pitch` model.
    *   Inject `PitchWorkflowService` and call `markPitchAsPaid(...)` with details from the Stripe invoice/payment intent. **Note:** The existing handler likely *does not* perform these pitch-specific actions and must be modified.
    *   Return `200 OK` to Stripe.
4.  **Action:** In the method handling `invoice.payment_failed` (likely `handleInvoicePaymentFailed`):
    *   Verify signature.
    *   Extract invoice, get `pitch_id`, fetch `Pitch`.
    *   Inject `PitchWorkflowService` and call `markPitchPaymentFailed(...)`. **Note:** The existing handler likely *does not* perform these pitch-specific actions and must be modified.
    *   Return `200 OK`.

**E. Refactor Frontend (Payment Modals/Forms)**

1.  Ensure payment forms (likely using Stripe Elements/JS) submit payment method IDs correctly to the `PitchPaymentController` endpoint.
2.  Handle success/error responses from the payment endpoint, displaying user feedback (Toaster messages).
3.  Ensure the UI correctly reflects the `Pitch` `payment_status` (Pending, Paid, Failed), potentially disabling payment buttons if already paid or processing.
4.  Receipts: Ensure routes/views for displaying receipts (`PitchPaymentController::showReceipt`?) fetch the necessary invoice/pitch data and display it securely.

---

## Final Steps

1.  **Code Review:** Conduct thorough code reviews of all refactored components (Services, Controllers, Models, Form Requests, Livewire Components, Views). Ensure adherence to the new architecture and coding standards.
2.  **Testing Strategy:** Implement a robust testing strategy:
    *   **Unit Tests:** Focus on testing the logic within Service classes. Mock dependencies (Models, other Services, external APIs like S3/Stripe) to isolate the unit under test. Verify correct state changes, exception handling, and return values based on different inputs.
    *   **Feature Tests:** Test the integration points. Write tests that simulate HTTP requests to Controller actions or interact with Livewire components. Assert correct responses, redirects, database changes, event dispatches, and UI updates (for Livewire). Use database factories and potentially transaction rollbacks. Verify authorization and validation logic via Form Requests and Policies. Test key user flows end-to-end.
    *   **Existing Tests:** Update any existing tests that are affected by the refactoring.
3.  **Manual Testing:** Perform comprehensive manual testing of all affected user workflows (project creation/update, pitch submission/approval/denial/revision/completion, file management, payment flows). Test different user roles (project owner, producer).
4.  **Documentation:** Update any relevant user or developer documentation to reflect the changes in workflow or API, if applicable. Ensure READMEs are current.
5.  **Configuration Files:** Consider moving hardcoded values (e.g., file size limits, specific status codes used in logic, Stripe keys) into Laravel configuration files (`config/*.php`) for better maintainability and environment management in the future. This might be a follow-up task rather than part of the initial refactor.
6.  **Monitoring:** Ensure adequate logging is in place, especially within service methods and exception handlers. Set up monitoring tools to track application performance and errors, particularly around the refactored areas and external service interactions (S3, Stripe).

**(Previous content on Code Review, Testing, Documentation, Monitoring removed/merged into the points above)**
