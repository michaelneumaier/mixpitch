# MixPitch Backend Refactoring - Testing Guide

**Version:** 1.0
**Date:** 2024-07-26

## Introduction

This guide complements the `REFACTORING_GUIDE.md` by outlining a testing strategy to be implemented *alongside* the backend refactoring process. As MixPitch currently lacks an automated test suite, implementing tests during refactoring is crucial for:

*   **Verifying Correctness:** Ensuring the refactored code behaves as expected.
*   **Preventing Regressions:** Catching unintended side effects or broken functionality introduced by changes.
*   **Building Confidence:** Providing assurance that the application remains stable throughout the refactoring.
*   **Improving Maintainability:** Creating a safety net for future development.

We will focus on two primary types of automated tests:

1.  **Unit Tests:** Test individual components (primarily Service classes) in isolation. Dependencies are mocked to focus solely on the unit's logic.
2.  **Feature Tests:** Test the integration of components by simulating user interactions (HTTP requests, Livewire component interactions). These tests ensure different parts of the application work together correctly, including routing, controllers, services, validation, authorization, and database interactions.

**Goal:** To have a reasonable test suite covering the core functionality being refactored by the end of the process.

---

## Step 0: Testing Environment Setup

Before writing tests for specific features, set up the basic testing environment.

1.  **`phpunit.xml` Configuration:**
    *   Locate the `phpunit.xml` file in the project root.
    *   Ensure it's configured for testing. Pay attention to the `<php>` environment variables, especially for database connections.
    *   **Recommendation:** Configure it to use an in-memory SQLite database for faster test execution.
        ```xml
        <!-- phpunit.xml -->
        <php>
            <env name="APP_ENV" value="testing"/>
            <env name="BCRYPT_ROUNDS" value="4"/>
            <env name="CACHE_DRIVER" value="array"/>
            <!-- <env name="DB_CONNECTION" value="sqlite"/> -->
            <!-- <env name="DB_DATABASE" value=":memory:"/> -->
            <env name="MAIL_MAILER" value="array"/>
            <env name="QUEUE_CONNECTION" value="sync"/>
            <env name="SESSION_DRIVER" value="array"/>
            <env name="TELESCOPE_ENABLED" value="false"/>
            <!-- Add your testing DB connection details here if not using SQLite in-memory -->
             <env name="DB_CONNECTION" value="testing"/> <!-- Example: use a separate 'testing' connection -->

        </php>
        ```
    *   **Action:** Define a `testing` database connection in `config/database.php` (e.g., pointing to a separate test database or SQLite file/memory).
    *   Make sure you have a corresponding `.env.testing` file or configure testing environment variables appropriately.

2.  **Base Test Case:**
    *   Laravel provides `tests/TestCase.php`. Ensure it's suitable for your needs.
    *   Consider adding traits like `Illuminate\\Foundation\\Testing\\RefreshDatabase` to your feature tests or the base class. This trait automatically handles migrating the database before each test and rolling back changes afterward, ensuring a clean state.
        ```php
        // tests/TestCase.php
        <?php

        namespace Tests;

        use Illuminate\\Foundation\\Testing\\TestCase as BaseTestCase;

        abstract class TestCase extends BaseTestCase
        {
            use CreatesApplication;
        }
        ```
        ```php
        // Example Feature Test using RefreshDatabase
        // tests/Feature/ProjectManagementTest.php
        <?php
        namespace Tests\Feature;

        use Illuminate\Foundation\Testing\RefreshDatabase;
        use Tests\TestCase;
        // ... other imports

        class ProjectManagementTest extends TestCase
        {
            use RefreshDatabase; // Add this trait

            // ... tests ...
        }

        ```

3.  **Database Factories:**
    *   Automated tests rely heavily on quickly creating model instances with realistic data. Laravel Factories are used for this.
    *   **Action:** Create database factories for all core models involved in the refactoring if they don't exist:
        *   `database/factories/UserFactory.php` (Likely exists)
        *   `database/factories/ProjectFactory.php`
        *   `database/factories/PitchFactory.php`
        *   `database/factories/ProjectFileFactory.php`
        *   `database/factories/PitchFileFactory.php`
        *   `database/factories/PitchSnapshotFactory.php`
        *   `database/factories/PitchEventFactory.php`
        *   (Add others as needed)
    *   Define sensible default states and potentially named states for different scenarios (e.g., a `published` state for `ProjectFactory`).
        ```php
        // Example: database/factories/ProjectFactory.php
        <?php
        namespace Database\Factories;

        use App\Models\Project;
        use App\Models\User;
        use Illuminate\Database\Eloquent\Factories\Factory;

        class ProjectFactory extends Factory
        {
            protected $model = Project::class;

            public function definition(): array
            {
                return [
                    'user_id' => User::factory(),
                    'name' => $this->faker->sentence(3),
                    'description' => $this->faker->paragraph,
                    'genre' => $this->faker->randomElement(['Pop', 'Rock', 'Hip Hop']), // Use valid genres
                    'artist_name' => $this->faker->name,
                    'project_type' => $this->faker->word,
                    'collaboration_type' => [$this->faker->word],
                    'budget' => $this->faker->numberBetween(100, 5000),
                    'status' => Project::STATUS_UNPUBLISHED, // Default status
                    'deadline' => $this->faker->dateTimeBetween('+1 week', '+3 months'),
                    'total_storage_used' => 0,
                    // Add other necessary fields
                ];
            }

            // Example state for a published project
            public function published(): Factory
            {
                return $this->state(function (array $attributes) {
                    return [
                        'status' => Project::STATUS_OPEN, // Or relevant published status
                        'published_at' => now(),
                    ];
                });
            }
             // Example state for a completed project
            public function completed(): Factory
            {
                return $this->state(function (array $attributes) {
                    return [
                        'status' => Project::STATUS_COMPLETED,
                        'completed_at' => now(),
                    ];
                });
            }
        }
        ```

4.  **Running Tests:**
    *   Use the command line: `php artisan test`
    *   To run specific tests: `php artisan test --filter=YourTestClassName` or `php artisan test --filter=your_test_method_name`.

---

## Step 2: Testing Project Management Refactoring

**Refactored Components:** `ProjectManagementService`, `ProjectController`, `StoreProjectRequest`, `UpdateProjectRequest`, `Project` Model, `CreateProject`/`ManageProject` Livewire Components.

**A. Unit Tests (`ProjectManagementService`)**

*   Create `tests/Unit/Services/ProjectManagementServiceTest.php`.
*   Mock dependencies: `DB` facade (for transactions), `Storage` facade, `Log` facade, `Project` model (if complex interactions are tested).
*   **Test Cases:**
    *   `test_create_project_success()`: Verify project creation with valid data, correct status, user assignment, and transaction commit.
    *   `test_create_project_with_image_success()`: Verify image upload (`Storage::disk('s3')->assertExists(...)`), path storage, and project creation.
    *   `test_create_project_db_failure()`: Mock `DB::transaction` to throw an exception, assert `ProjectCreationException` is thrown.
    *   `test_create_project_image_upload_failure()`: Mock `Storage::disk('s3')->put()` to fail, assert appropriate exception/handling.
    *   `test_update_project_success()`: Verify successful update of fields.
    *   `test_update_project_with_new_image()`: Verify old image deletion (`Storage::disk('s3')->assertMissing(...)`), new image upload (`Storage::disk('s3')->assertExists(...)`), and path update.
    *   `test_update_project_without_deleting_old_image()`: Verify old image is *not* deleted when flag is false or no new image provided.
    *   `test_update_project_db_failure()`: Mock `DB::transaction` to throw exception, assert `ProjectUpdateException`.
    *   `test_publish_project_success()`: Verify status change and save call (or mock model method).
    *   `test_unpublish_project_success()`: Verify status change and save call.
    *   `test_complete_project_success()`: Verify status change, `completed_at` timestamp, and save call.
    *   `test_complete_project_idempotency()`: Verify calling `completeProject` on an already completed project doesn't change it.

```php
// Example Unit Test Snippet (ProjectManagementServiceTest.php)
use Tests\TestCase;
use App\Services\ProjectManagementService;
use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase; // May not be needed for pure unit tests if mocking DB
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\Project\ProjectCreationException;

class ProjectManagementServiceTest extends TestCase
{
    // use RefreshDatabase; // Optional, depending on mocking strategy

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3'); // Fake the S3 disk
    }

    /** @test */
    public function it_can_create_a_project_successfully()
    {
        $user = User::factory()->create();
        $data = Project::factory()->make()->toArray(); // Get factory data without saving
        unset($data['user_id'], $data['status']); // Remove fields set by service

        $service = new ProjectManagementService();

        // Mock DB transaction to just execute the callback
        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $project = $service->createProject($user, $data, null);

        $this->assertInstanceOf(Project::class, $project);
        $this->assertEquals($user->id, $project->user_id);
        $this->assertEquals($data['name'], $project->name);
        $this->assertEquals(Project::STATUS_UNPUBLISHED, $project->status);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => $data['name']]);
    }

     /** @test */
    public function it_can_create_a_project_with_an_image()
    {
        $user = User::factory()->create();
        $data = Project::factory()->make()->toArray();
        unset($data['user_id'], $data['status']);
        $file = UploadedFile::fake()->image('project.jpg');

        $service = new ProjectManagementService();
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        $project = $service->createProject($user, $data, $file);

        $this->assertNotNull($project->image_path);
        Storage::disk('s3')->assertExists($project->image_path);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'image_path' => $project->image_path]);
    }

     /** @test */
    public function it_throws_exception_on_create_project_db_error()
    {
        $user = User::factory()->create();
        $data = Project::factory()->make()->toArray();
         unset($data['user_id'], $data['status']);

        $service = new ProjectManagementService();

        DB::shouldReceive('transaction')->once()->andThrow(new \\Exception('DB Error'));

        $this->expectException(ProjectCreationException::class);

        $service->createProject($user, $data, null);
    }

    // ... more tests for update, publish, unpublish, complete etc.
}
```

**B. Feature Tests (Controllers / Livewire)**

*   Create `tests/Feature/ProjectManagementTest.php`.
*   Use `RefreshDatabase` trait.
*   Use factories to create users and projects.
*   Use `actingAs($user)` to simulate logged-in users.
*   **Test Cases (Controllers - if applicable):**
    *   `test_guest_cannot_create_project()`: Assert redirect to login or 403.
    *   `test_authenticated_user_can_view_create_project_form()`: Assert 200 status for GET request.
    *   `test_project_creation_validation_errors()`: Post invalid data, assert redirect back with errors.
    *   `test_project_creation_success()`: Post valid data, assert redirect, `assertDatabaseHas` project.
    *   `test_project_creation_with_image_success()`: Post valid data with file, assert DB record, `Storage::disk('s3')->assertExists`.
    *   `test_unauthorized_user_cannot_update_project()`: Assert 403.
    *   `test_authorized_user_can_view_edit_project_form()`: Assert 200.
    *   `test_project_update_validation_errors()`: Put invalid data, assert errors.
    *   `test_project_update_success()`: Put valid data, assert redirect, `assertDatabaseHas` updated data.
    *   `test_project_update_with_image_success()`: Put valid data with new image, assert old image missing, new exists, DB updated.
    *   `test_project_publish_success()`: Post to publish route, assert status change in DB.
    *   `test_project_unpublish_success()`: Post to unpublish route, assert status change.
    *   `test_unauthorized_user_cannot_publish_project()`: Assert 403.

*   **Test Cases (Livewire - `CreateProject`, `ManageProject`):**
    *   Use `Livewire::test(CreateProject::class)`.
    *   `test_create_project_component_renders()`: Assert view loads.
    *   `test_create_project_validation()`: Set invalid properties, call save action, `assertHasErrors()`.
    *   `test_create_project_unauthorized()`: Test authorization check within the save method.
    *   `test_create_project_success()`: Set valid properties, call save, `assertRedirect()`, `assertDatabaseHas()`. Mock `ProjectManagementService` if testing component interaction in isolation, or let it run for full feature test.
    *   `test_create_project_with_image_success()`: Simulate file upload (`WithFileUploads` trait), call save, assert redirect, DB, storage.
    *   `test_manage_project_renders_correct_data()`: Pass existing project, assert data displayed.
    *   `test_manage_project_update_success()`: Set properties, call update action, `assertDatabaseHas()`.
    *   `test_manage_project_publish_unpublish_actions()`: Call publish/unpublish actions, assert service methods are called (if mocking) or DB status changes.

```php
// Example Feature Test Snippet (ProjectManagementTest.php)
use Livewire\Livewire;
use App\Livewire\CreateProject; // Adjust namespace if needed
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function create_project_livewire_component_can_create_project()
    {
        Storage::fake('s3');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('project.jpg');

        Livewire::actingAs($user)
            ->test(CreateProject::class)
            ->set('name', 'Test Project Name')
            ->set('description', 'Test Description')
            ->set('genre', 'Pop')
            ->set('artist_name', 'Test Artist')
            ->set('project_type', 'Mixing')
            ->set('collaboration_type', ['Vocalist'])
            ->set('budget', 500)
            ->set('project_image', $file) // Use the WithFileUploads trait features
            ->call('save')
            ->assertRedirectContains('/projects/'); // Adjust expected redirect

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'name' => 'Test Project Name',
            'genre' => 'Pop',
            'budget' => 500,
            'status' => Project::STATUS_UNPUBLISHED,
        ]);

        // Find the created project to check image path
        $project = Project::where('name', 'Test Project Name')->first();
        $this->assertNotNull($project->image_path);
        Storage::disk('s3')->assertExists($project->image_path);
    }

    /** @test */
    public function create_project_livewire_component_shows_validation_errors()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateProject::class)
            ->set('name', '') // Invalid name
            ->set('budget', -100) // Invalid budget
            ->call('save')
            ->assertHasErrors(['name', 'budget']);
    }

    // ... other controller and livewire tests ...
}

```

---

## Step 3: Testing Pitch Creation & Basic Management

**Refactored Components:** `PitchWorkflowService`, `PitchController`, `StorePitchRequest`, `Pitch` Model, `Project` Model, `ManagePitch` Livewire Component.

**A. Unit Tests (`PitchWorkflowService`)**

*   Create `tests/Unit/Services/PitchWorkflowServiceTest.php`.
*   Mock `DB`, `Log`, `NotificationService`, `Pitch`, `Project` models.
*   **Test Cases:**
    *   `test_create_pitch_success()`: Valid project/user, assert Pitch created with correct status/relations, event created, notification sent.
    *   `test_create_pitch_fails_if_project_not_open()`: Mock `project->isOpenForPitches()` to return false, assert `PitchCreationException`.
    *   `test_create_pitch_fails_if_user_already_pitched()`: Mock `project->userPitch()` to return existing pitch, assert `PitchCreationException`.
    *   `test_create_pitch_fails_on_db_error()`: Mock `DB::transaction` to throw exception, assert `PitchCreationException`.
    *   `test_create_pitch_authorization_failure()`: (If service handles auth) Assert `UnauthorizedActionException`.

**B. Feature Tests (Controllers / Livewire)**

*   Create `tests/Feature/PitchCreationTest.php`.
*   Use `RefreshDatabase`.
*   Create users (project owner, producer), projects (open/closed).
*   **Test Cases (Controllers):**
    *   `test_producer_can_view_create_pitch_form_for_open_project()`: GET request, assert 200.
    *   `test_producer_cannot_view_create_form_if_already_pitched()`: Assert redirect.
    *   `test_producer_cannot_view_create_form_for_closed_project()`: Assert redirect/403.
    *   `test_project_owner_cannot_view_create_pitch_form()`: Assert redirect/403 (based on policy).
    *   `test_pitch_creation_validation_errors()`: Post invalid data (e.g., terms not accepted), assert errors.
    *   `test_pitch_creation_success()`: Post valid data, assert redirect, `assertDatabaseHas` (pitch, event), `Notification::assertSentTo`.
    *   `test_pitch_creation_fails_if_project_closed_during_submit()`: (Edge case) Simulate project closing between form view and submit, assert error/redirect.
    *   `test_view_pitch_authorization()`: Test project owner sees manage view, producer sees show view, other users get 403.

*   **Test Cases (Livewire - `ManagePitch` if editing exists):**
    *   `test_manage_pitch_renders_correct_data()`: Pass pitch, assert data displayed.
    *   `test_manage_pitch_update_details_validation()`: Set invalid data, call update, assert errors.
    *   `test_manage_pitch_update_details_unauthorized()`: Try updating as wrong user, assert 403/error.
    *   `test_manage_pitch_update_details_success()`: Set valid data, call update, assert DB updated.

---

## Step 4: Testing Pitch Status Updates

**Refactored Components:** `PitchWorkflowService`, `UpdatePitchStatus` Livewire, `ManagePitch` Livewire (for cancel), `PitchPolicy`, `Pitch` Model.

**A. Unit Tests (`PitchWorkflowService`)**

*   Add tests to `tests/Unit/Services/PitchWorkflowServiceTest.php`.
*   Mock `DB`, `Log`, `NotificationService`, `Pitch`, `PitchSnapshot`, `User` models.
*   **Test Cases:**
    *   `test_approve_initial_pitch_success()`: Pending pitch, correct user, assert status change, event, notification.
    *   `test_approve_initial_pitch_fails_for_wrong_user()`: Assert `UnauthorizedActionException`.
    *   `test_approve_initial_pitch_fails_for_wrong_status()`: Non-pending pitch, assert `InvalidStatusTransitionException`.
    *   `test_approve_submitted_pitch_success()`: ReadyForReview pitch, valid snapshot, correct user, assert pitch/snapshot status change, event, notification.
    *   `test_approve_submitted_pitch_fails_for_wrong_user()`: Assert `UnauthorizedActionException`.
    *   `test_approve_submitted_pitch_fails_for_wrong_status()`: Assert `InvalidStatusTransitionException`.
    *   `test_approve_submitted_pitch_fails_for_invalid_snapshot()`: Assert `SnapshotException`.
    *   `test_approve_submitted_pitch_fails_if_paid_or_completed()`: Assert `InvalidStatusTransitionException`.
    *   `test_deny_submitted_pitch_success()`: Test with and without reason, assert status, event, notification.
    *   `test_deny_submitted_pitch_fails_...()`: Test auth, status, snapshot, paid/completed checks.
    *   `test_request_pitch_revisions_success()`: Assert status, event, notification, feedback stored.
    *   `test_request_pitch_revisions_fails_without_feedback()`: Assert `InvalidArgumentException`.
    *   `test_request_pitch_revisions_fails_...()`: Test auth, status, snapshot, paid/completed checks.
    *   `test_cancel_pitch_submission_success()`: Producer cancels ReadyForReview pitch, assert status change, snapshot status change, event.
    *   `test_cancel_pitch_submission_fails_for_wrong_user()`: Assert `UnauthorizedActionException`.
    *   `test_cancel_pitch_submission_fails_for_wrong_status()`: Assert `InvalidStatusTransitionException`.
    *   `test_cancel_pitch_submission_fails_for_invalid_snapshot()`: Assert `SnapshotException`.

**B. Feature Tests (Livewire)**

*   Create `tests/Feature/PitchStatusUpdateTest.php`.
*   Use `RefreshDatabase`, `actingAs`.
*   Setup: Create project owner, producer, project, pitch (in various statuses), pitch snapshots.
*   Use `Livewire::test()` for `UpdatePitchStatus` and `ManagePitch`.
*   Fake `NotificationService` (`Notification::fake()`).
*   **Test Cases (`UpdatePitchStatus` - acting as Project Owner):**
    *   `test_owner_can_approve_snapshot()`: Load component with ReadyForReview pitch, call approve action, assert DB changes, notification sent, component events dispatched.
    *   `test_owner_cannot_approve_snapshot_in_wrong_status()`: Load with Approved pitch, call approve, assert error message/toaster.
    *   `test_producer_cannot_approve_snapshot()`: Act as producer, load component, call approve, assert unauthorized/error.
    *   `test_owner_can_deny_snapshot()`: Call deny action, assert DB changes, notification.
    *   `test_owner_can_request_revisions()`: Call request revisions action with feedback, assert DB changes, notification.
    *   `test_request_revisions_requires_feedback()`: Call action without feedback, assert error.

*   **Test Cases (`ManagePitch` - acting as Producer):**
    *   `test_producer_can_cancel_submission()`: Load component with ReadyForReview pitch, call cancel action, assert DB changes.
    *   `test_producer_cannot_cancel_submission_in_wrong_status()`: Load with InProgress pitch, call cancel, assert error.
    *   `test_owner_cannot_cancel_submission()`: Act as owner, load component, call cancel, assert unauthorized/error.

---

## Step 5: Testing File Management

**Refactored Components:** `FileManagementService`, `ProjectController`/`PitchFileController` (if applicable), `ManageProject`/`ManagePitch` Livewire, `Project`/`Pitch` Models, `ProjectFile`/`PitchFile` Models, Policies.

**A. Unit Tests (`FileManagementService`)**

*   Create `tests/Unit/Services/FileManagementServiceTest.php`.
*   Mock `DB`, `Storage`, `Log`, `Project`, `Pitch`, `ProjectFile`, `PitchFile`, `User` models.
*   Use `Storage::fake('s3')`.
*   **Test Cases:**
    *   `test_upload_project_file_success()`: Assert `ProjectFile` created, `Storage::assertExists`, project `incrementStorageUsed` called, transaction commit.
    *   `test_upload_project_file_fails_size_limit()`: File too large, assert `FileUploadException`.
    *   `test_upload_project_file_fails_storage_limit()`: Mock `project->hasStorageCapacity()` false, assert `StorageLimitException`.
    *   `test_upload_project_file_fails_unauthorized()`: (If service checks auth) Assert `UnauthorizedActionException`.
    *   `test_upload_project_file_fails_storage_error()`: Mock `Storage::put` fails, assert `FileUploadException`.
    *   `test_upload_pitch_file_success()`: Assert `PitchFile` created, `Storage::assertExists`, pitch `incrementStorageUsed` called.
    *   `test_upload_pitch_file_fails_wrong_status()`: Pitch not InProgress/RevisionsRequested, assert `FileUploadException`.
    *   `test_upload_pitch_file_fails_unauthorized()`: Wrong user attempts upload, assert `UnauthorizedActionException`.
    *   `test_upload_pitch_file_fails_limits()`: Test size/storage limits for pitch.
    *   `test_delete_project_file_success()`: Assert file deleted from DB, `Storage::assertMissing`, project `decrementStorageUsed` called.
    *   `test_delete_project_file_fails_unauthorized()`: Wrong user, assert `UnauthorizedActionException`.
    *   `test_delete_project_file_handles_storage_deletion_failure()`: Mock `Storage::delete` fails, assert error logged but transaction completes (or throws, depending on desired behavior).
    *   `test_delete_pitch_file_success()`: Assert file deleted, storage missing, pitch `decrementStorageUsed`.
    *   `test_delete_pitch_file_fails_unauthorized()`: Wrong user.
    *   `test_delete_pitch_file_fails_wrong_status()`: Pitch status doesn't allow deletion.
    *   `test_get_temporary_download_url_success()`: Correct user, assert URL string returned. Mock `Storage::temporaryUrl`.
    *   `test_get_temporary_download_url_fails_unauthorized()`: Wrong user/policy fails, assert `UnauthorizedActionException`.

**B. Feature Tests (Livewire / Controllers)**

*   Create `tests/Feature/FileManagementTest.php`.
*   Use `RefreshDatabase`, `actingAs`, `Storage::fake('s3')`.
*   Setup users, projects, pitches, files.
*   Use Livewire testing utilities, including file upload simulation.
*   **Test Cases (Livewire - `ManageProject`, `ManagePitch`):**
    *   `test_project_owner_can_upload_project_file()`: `Livewire::test`, simulate upload, assert `assertDatabaseHas('project_files')`, `Storage::assertExists`.
    *   `test_project_upload_fails_storage_limit()`: Pre-fill storage, upload, assert error message.
    *   `test_unauthorized_user_cannot_upload_project_file()`: Act as wrong user, attempt upload, assert unauthorized/error.
    *   `test_project_owner_can_delete_project_file()`: Call delete action, `assertDatabaseMissing('project_files')`, `Storage::assertMissing`.
    *   `test_unauthorized_user_cannot_delete_project_file()`.
    *   `test_pitch_owner_can_upload_pitch_file()`: `Livewire::test(ManagePitch::class)`, simulate upload, assert DB/Storage.
    *   `test_pitch_owner_cannot_upload_file_in_wrong_status()`: Pitch Approved, attempt upload, assert error.
    *   `test_project_owner_cannot_upload_pitch_file()`: Act as owner, attempt upload to producer's pitch, assert unauthorized/error.
    *   `test_pitch_owner_can_delete_pitch_file()`: Call delete, assert DB/Storage missing.
    *   `test_pitch_owner_cannot_delete_file_in_wrong_status()`.
    *   `test_unauthorized_user_cannot_delete_pitch_file()`.
    *   `test_authorized_user_can_get_download_url()`: Call download action, assert browser event dispatched with URL.
    *   `test_unauthorized_user_cannot_get_download_url()`.

*   **Test Cases (Controllers - if `PitchFileController` is used directly):**
    *   Test `store`, `destroy`, `download` actions similarly, checking auth, validation, service calls, responses (JSON/redirects), DB/Storage state.

---

## Step 6: Testing Pitch Submission (Snapshots)

**Refactored Components:** `PitchWorkflowService`, `ManagePitch` Livewire, `Pitch` Model, `PitchSnapshot` Model, `PitchPolicy`.

**A. Unit Tests (`PitchWorkflowService`)**

*   Add tests to `tests/Unit/Services/PitchWorkflowServiceTest.php`.
*   Mock `DB`, `Log`, `NotificationService`, `Pitch`, `PitchFile`, `PitchSnapshot`, `User` models.
*   **Test Cases:**
    *   `test_submit_pitch_for_review_success_first_time()`: InProgress pitch, files exist, assert status change, snapshot created (version 1), event, notification.
    *   `test_submit_pitch_for_review_success_after_revisions()`: RevisionsRequested pitch, assert status change, snapshot created (version N+1), previous snapshot status updated, event, notification. Include test with/without feedback response.
    *   `test_submit_pitch_for_review_fails_unauthorized()`: Wrong user.
    *   `test_submit_pitch_for_review_fails_wrong_status()`: Pitch Approved/Pending etc.
    *   `test_submit_pitch_for_review_fails_no_files()`: Assert `SubmissionValidationException`.
    *   `test_submit_pitch_for_review_fails_db_error()`.

**B. Feature Tests (Livewire)**

*   Add tests to `tests/Feature/PitchSubmissionTest.php` or `PitchStatusUpdateTest.php`.
*   Use `RefreshDatabase`, `actingAs`.
*   Setup: Project owner, producer, project, pitch (InProgress/RevisionsRequested), pitch files.
*   Use `Livewire::test(ManagePitch::class)`. Fake `NotificationService`.
*   **Test Cases (Acting as Producer):**
    *   `test_producer_can_submit_pitch_for_review()`: Load component with InProgress pitch + files, call submit action, assert DB status, `assertDatabaseHas('pitch_snapshots')`, notification sent.
    *   `test_producer_can_resubmit_pitch_after_revisions()`: Load component with RevisionsRequested pitch + files, call submit, assert DB status, new snapshot, previous snapshot status, notification.
    *   `test_submit_fails_if_no_files_attached()`: Load component, no files, call submit, assert error message.
    *   `test_submit_fails_in_wrong_pitch_status()`: Load with Approved pitch, call submit, assert error.
    *   `test_project_owner_cannot_submit_pitch()`: Act as owner, load component, call submit, assert unauthorized/error.

---

## Step 7: Testing Pitch Completion

**Refactored Components:** `PitchCompletionService`, `CompletePitch` Livewire, `ProjectManagementService`, `PitchWorkflowService`, `Pitch`/`Project`/`PitchSnapshot` Models, `PitchPolicy`.

**A. Unit Tests (`PitchCompletionService`)**

*   Create `tests/Unit/Services/PitchCompletionServiceTest.php`.
*   Mock `DB`, `Log`, `ProjectManagementService`, `NotificationService`, `Pitch`, `Project`, `User` models.
*   **Test Cases:**
    *   `test_complete_pitch_success_free_project()`: Approved pitch, budget 0, correct user, assert pitch status/timestamp/payment_status (NotRequired), snapshot status, other pitches closed, `ProjectManagementService::completeProject` called, event, notifications (completed + closed).
    *   `test_complete_pitch_success_paid_project()`: Approved pitch, budget > 0, assert pitch status/payment_status (Pending).
    *   `test_complete_pitch_fails_unauthorized()`: Wrong user (not project owner).
    *   `test_complete_pitch_fails_wrong_status()`: Pitch not Approved.
    *   `test_complete_pitch_fails_if_already_paid_or_processing()`: Pitch payment status is Paid/Processing.
    *   `test_complete_pitch_closes_other_active_pitches()`: Setup project with multiple pitches (pending, in_progress), complete one, assert others are Closed.
    *   `test_complete_pitch_handles_db_error()`.

**B. Feature Tests (Livewire)**

*   Create `tests/Feature/PitchCompletionTest.php`.
*   Use `RefreshDatabase`, `actingAs`.
*   Setup: Owner, producer(s), project, multiple pitches (one Approved, others Pending/InProgress), snapshots.
*   Use `Livewire::test(CompletePitch::class)`. Fake `NotificationService`, potentially mock `ProjectManagementService`.
*   **Test Cases (Acting as Project Owner):**
    *   `test_owner_can_complete_approved_pitch()`: Load component with Approved pitch, call complete action, assert DB pitch status/payment_status, project status, other pitch statuses, notification sent.
    *   `test_owner_cannot_complete_pitch_in_wrong_status()`: Load with InProgress pitch, call complete, assert error.
    *   `test_producer_cannot_complete_pitch()`: Act as producer, load, call complete, assert unauthorized/error.
    *   `test_completion_triggers_payment_modal_event_if_paid()`: Budget > 0, call complete, `assertDispatched('openPaymentModal')`.

---

## Step 8: Testing Payment Processing

**Refactored Components:** `InvoiceService`, `PitchWorkflowService`, `PitchPaymentController`, `WebhookController`, `Pitch` Model.

**A. Unit Tests (`InvoiceService` & `PitchWorkflowService`)**

*   Create `tests/Unit/Services/InvoiceServiceTest.php`.
*   Mock Stripe client/API calls heavily. Use `laravel/cashier` test helpers if applicable.
*   **Test Cases (`InvoiceService`):**
    *   `test_create_pitch_invoice_success()`: Mock Stripe API success, assert invoice object returned with correct details/metadata.
    *   `test_create_pitch_invoice_handles_stripe_error()`: Mock Stripe API error, assert custom exception thrown.
    *   `test_process_invoice_payment_success()`: Mock Stripe API success, assert result indicates success.
    *   `test_process_invoice_payment_handles_card_error()`: Mock Stripe card error, assert specific exception.
    *   `test_process_invoice_payment_handles_api_error()`: Mock general Stripe API error.

*   Add tests to `tests/Unit/Services/PitchWorkflowServiceTest.php`.
*   **Test Cases (`PitchWorkflowService` Payment Methods):**
    *   `test_mark_pitch_as_paid_success()`: Completed pitch, assert status, invoice ID stored, timestamp, event, notification.
    *   `test_mark_pitch_as_paid_idempotency()`: Call on already paid pitch, assert no change/error.
    *   `test_mark_pitch_as_paid_fails_if_not_completed()`: Call on Approved pitch, assert no change or warning logged.
    *   `test_mark_pitch_payment_failed_success()`: Assert status, invoice ID, event, notification.

**B. Feature Tests (Controller / Webhooks)**

*   Create `tests/Feature/PaymentProcessingTest.php`.
*   Use `RefreshDatabase`, `actingAs`.
*   Setup: Owner, producer, project, completed pitch (PAYMENT_STATUS_PENDING).
*   Mock Stripe interactions where possible, or use Cashier's fakes. Fake `NotificationService`.
*   **Test Cases (`PitchPaymentController`):**
    *   `test_owner_can_process_payment_successfully()`: Post to payment route with mock payment method, assert pitch status updated to PAID, redirect/response ok. Requires mocking `InvoiceService` calls and their return values.
    *   `test_process_payment_fails_on_card_error()`: Mock `InvoiceService` to throw card exception, assert error response/redirect.
    *   `test_process_payment_fails_unauthorized()`: Producer tries to pay, assert 403.
    *   `test_process_payment_fails_wrong_pitch_status()`: Try paying for non-completed pitch.

*   **Test Cases (`WebhookController`):**
    *   These are harder to test directly via HTTP requests unless you perfectly craft Stripe event payloads.
    *   **Alternative:** Test the underlying service methods (`PitchWorkflowService::markPitchAsPaid/Failed`) thoroughly in unit tests.
    *   **Webhook Feature Test Strategy:**
        *   Create a fake Stripe event payload (`invoice.payment_succeeded`, `invoice.payment_failed`) with necessary pitch metadata.
        *   Post this payload directly to your webhook route (`/stripe/webhooks`). You might need to disable CSRF/webhook signature verification for this test route *only in the testing environment*.
        *   Assert that the `PitchWorkflowService` methods are called (using mocks/spies if needed).
        *   Assert the pitch status is updated correctly in the database (`assertDatabaseHas`).
    *   `test_webhook_handles_invoice_payment_succeeded()`: Post fake success event, assert pitch marked as PAID.
    *   `test_webhook_handles_invoice_payment_failed()`: Post fake failed event, assert pitch marked as FAILED.
    *   `test_webhook_ignores_event_for_unknown_pitch()`: Post event with bad metadata, assert no DB changes.
    *   `test_webhook_handles_already_paid_pitch_gracefully()`: Post success event for already PAID pitch, assert no error and state remains PAID.

---

## Conclusion

This testing guide provides a starting point. Adapt and expand these test cases based on the specific implementation details and edge cases encountered during refactoring. Running tests frequently (`php artisan test`) will be key to successfully completing the refactoring with confidence. Remember to keep factories updated and strive for clear, concise test methods.
