<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Pitch;
use App\Models\ProjectFile;
use App\Models\PitchFile;
use App\Services\FileManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ClientFileUploadTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $producer;
    protected Project $clientProject;
    protected Pitch $pitch;
    protected string $signedUrl;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake S3 storage for testing
        Storage::fake('s3');
        
        // Create test producer
        $this->producer = User::factory()->create([
            'email' => 'producer@test.com',
            'name' => 'Test Producer'
        ]);
        
        // Create client management project
        $this->clientProject = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_name' => 'Test Client',
            'client_email' => 'client@test.com'
        ]);
        
        // Create pitch for the project
        $this->pitch = Pitch::factory()->create([
            'project_id' => $this->clientProject->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_IN_PROGRESS
        ]);
        
        // Generate signed URL for upload
        $this->signedUrl = URL::temporarySignedRoute(
            'client.portal.upload_file',
            now()->addHours(24),
            ['project' => $this->clientProject->id]
        );
    }

    /** @test */
    public function client_can_upload_files_to_project_files()
    {
        Storage::fake('s3');
        
        // Create a test file
        $uploadedFile = UploadedFile::fake()->create('client-ref.pdf', 1024);
        
        // Upload file as client (no authentication, using signed URL)
        $response = $this->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
            ->post($this->signedUrl, [
                'file' => $uploadedFile
            ], [
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
            ]
        );
        
        // Assert successful upload
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'File uploaded successfully.'
        ]);
        
        // Assert file is stored in project_files table
        $this->assertDatabaseHas('project_files', [
            'project_id' => $this->clientProject->id,
            'file_name' => 'client-ref.pdf',
            'user_id' => null // Client uploads have no user_id
        ]);
        
        // Assert file is stored in S3
        Storage::disk('s3')->assertExists("projects/{$this->clientProject->id}/client-ref.pdf");
    }

    /** @test */
    public function client_uploaded_files_have_proper_metadata()
    {
        $file = UploadedFile::fake()->create('reference.mp3', 2048); // 2MB audio
        
        $response = $this->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
            ->post(
                $this->signedUrl,
                ['file' => $file],
                ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
            );
        
        $response->assertStatus(200);
        
        // Get the uploaded file
        $projectFile = ProjectFile::where('project_id', $this->clientProject->id)
            ->where('file_name', 'reference.mp3')
            ->first();
            
        $this->assertNotNull($projectFile);
        
        // Debug: Check what metadata actually contains
        $this->assertNotNull($projectFile->metadata, 'Metadata should not be null');
        
        // Assert metadata contains client upload information
        $metadata = json_decode($projectFile->metadata, true);
        $this->assertIsArray($metadata, 'Metadata should be a valid JSON array');
        $this->assertArrayHasKey('uploaded_by_client', $metadata, 'Metadata should have uploaded_by_client key');
        $this->assertTrue($metadata['uploaded_by_client']);
        $this->assertEquals('client@test.com', $metadata['client_email']);
        $this->assertEquals('client_portal', $metadata['upload_context']);
    }

    /** @test */
    public function client_can_download_their_uploaded_files()
    {
        // Create a project file as if uploaded by client
        $projectFile = ProjectFile::create([
            'project_id' => $this->clientProject->id,
            'file_name' => 'client-reference.pdf',
            'file_path' => "projects/{$this->clientProject->id}/client-reference.pdf",
            'storage_path' => "projects/{$this->clientProject->id}/client-reference.pdf",
            'size' => 1024,
            'mime_type' => 'application/pdf',
            'user_id' => null,
            'metadata' => json_encode([
                'uploaded_by_client' => true,
                'client_email' => 'client@test.com'
            ])
        ]);
        
        // Create the actual file in storage
        Storage::disk('s3')->put($projectFile->file_path, 'test content');
        
        // Generate signed URL for download
        $signedDownloadUrl = URL::temporarySignedRoute(
            'client.portal.download_project_file',
            now()->addHours(24),
            [
                'project' => $this->clientProject->id,
                'projectFile' => $projectFile->id
            ]
        );
        
        // Make request to download project file
        $response = $this->get($signedDownloadUrl);
        
        // Should redirect to temporary S3 URL
        $response->assertStatus(302);
    }

    /** @test */
    public function client_can_download_producer_files()
    {
        // Get the first pitch for the project (this is what the controller uses)
        $firstPitch = $this->clientProject->pitches()->first();
        
        // Create a pitch file (producer uploaded) for the first pitch
        $pitchFile = PitchFile::create([
            'pitch_id' => $firstPitch->id,
            'file_name' => 'producer-track.mp3',
            'file_path' => "pitches/{$firstPitch->id}/producer-track.mp3",
            'storage_path' => "pitches/{$firstPitch->id}/producer-track.mp3",
            'size' => 5120,
            'mime_type' => 'audio/mpeg',
            'user_id' => $this->producer->id,
        ]);
        
        // Create the actual file in storage
        Storage::disk('s3')->put($pitchFile->file_path, 'audio content');
        
        // Generate signed URL for download
        $signedDownloadUrl = URL::temporarySignedRoute(
            'client.portal.download_file',
            now()->addHours(24),
            [
                'project' => $this->clientProject->id,
                'pitchFile' => $pitchFile->id
            ]
        );
        
        // Make request to download pitch file
        $response = $this->get($signedDownloadUrl);
        
        // Should allow download
        $response->assertStatus(200);
    }

    /** @test */
    public function storage_tracking_includes_both_file_types()
    {
        // Upload project file (client)
        $projectFile = ProjectFile::create([
            'project_id' => $this->clientProject->id,
            'file_name' => 'client-file.pdf',
            'file_path' => "projects/{$this->clientProject->id}/client-file.pdf",
            'size' => 1024,
            'mime_type' => 'application/pdf',
            'user_id' => null,
        ]);
        
        // Upload pitch file (producer)
        $pitchFile = PitchFile::create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'producer-file.mp3',
            'file_path' => "pitches/{$this->pitch->id}/producer-file.mp3",
            'size' => 2048,
            'mime_type' => 'audio/mpeg',
            'user_id' => $this->producer->id,
        ]);
        
        // Test storage calculation methods
        $this->assertEquals(1024, $this->clientProject->getProjectFilesStorageUsed());
        $this->assertEquals(2048, $this->clientProject->getPitchFilesStorageUsed());
        $this->assertEquals(3072, $this->clientProject->getCombinedStorageUsed());
        
        // Test storage breakdown
        $breakdown = $this->clientProject->getStorageBreakdown();
        $this->assertEquals(3072, $breakdown['total']);
        $this->assertEquals(1024, $breakdown['project_files']);
        $this->assertEquals(2048, $breakdown['pitch_files']);
    }

    /** @test */
    public function file_upload_requires_valid_signed_url()
    {
        $file = UploadedFile::fake()->create('test.pdf', 1024);
        
        // Try upload without signed URL
        $response = $this->post(
            route('client.portal.upload_file', ['project' => $this->clientProject->id]),
            ['file' => $file]
        );
        
        // Should fail due to missing signature
        $response->assertStatus(403);
    }

    /** @test */
    public function file_upload_only_works_for_client_management_projects()
    {
        // Create a standard project
        $standardProject = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        ]);
        
        $file = UploadedFile::fake()->create('test.pdf', 1024);
        
        // Generate signed URL for standard project
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.upload_file',
            now()->addHours(24),
            ['project' => $standardProject->id]
        );
        
        // Make request with valid signature but wrong project type
        $response = $this->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
            ->post($signedUrl, ['file' => $file]);
        
        // Should return JSON error
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'File upload is only available for client management projects.'
        ]);
    }

    /** @test */
    public function file_upload_validates_file_size()
    {
        // Create file larger than 200MB limit
        $file = UploadedFile::fake()->create('huge-file.pdf', 250 * 1024); // 250MB
        
        $response = $this->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
            ->post(
                $this->signedUrl,
                ['file' => $file],
                ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
            );
        
        // Debug: Check what we actually got
        if ($response->status() !== 422) {
            dump('Status: ' . $response->status());
            dump('Content: ' . $response->getContent());
        }
        
        // Should fail validation
        $response->assertStatus(422);
    }

    /** @test */
    public function producer_can_access_both_file_types_in_management_interface()
    {
        // Create both file types
        $projectFile = ProjectFile::create([
            'project_id' => $this->clientProject->id,
            'file_name' => 'client-file.pdf',
            'file_path' => "projects/{$this->clientProject->id}/client-file.pdf",
            'size' => 1024,
            'mime_type' => 'application/pdf',
            'user_id' => null,
        ]);
        
        $pitchFile = PitchFile::create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'producer-file.mp3',
            'file_path' => "pitches/{$this->pitch->id}/producer-file.mp3",
            'size' => 2048,
            'mime_type' => 'audio/mpeg',
            'user_id' => $this->producer->id,
        ]);
        
        // Access producer management interface
        $response = $this->actingAs($this->producer)
            ->get(route('projects.manage-client', $this->clientProject));
        
        $response->assertStatus(200);
        
        // Should see both file types
        $response->assertSee('client-file.pdf');
        $response->assertSee('producer-file.mp3');
        $response->assertSee('Client Reference Files');
        $response->assertSee('Your Deliverables');
    }

    /** @test */
    public function producer_can_delete_client_uploaded_files()
    {
        // Create actual file in storage first
        Storage::fake('s3');
        
        $projectFile = ProjectFile::create([
            'project_id' => $this->clientProject->id,
            'file_name' => 'client-file.pdf',
            'file_path' => "projects/{$this->clientProject->id}/client-file.pdf",
            'size' => 1024,
            'mime_type' => 'application/pdf',
            'user_id' => null,
        ]);
        
        // Create actual file in storage
        Storage::disk('s3')->put($projectFile->file_path, 'test content');
        
        // Verify file exists before deletion
        $this->assertDatabaseHas('project_files', ['id' => $projectFile->id]);
        Storage::disk('s3')->assertExists($projectFile->file_path);
        
        // Producer should be able to delete client file
        $this->actingAs($this->producer);
        
        $fileService = app(FileManagementService::class);
        $result = $fileService->deleteProjectFile($projectFile);
        
        $this->assertTrue($result);
        
        // Check that the file was actually deleted within the current transaction
        $this->assertNull(ProjectFile::find($projectFile->id));
        
        // File should be removed from storage
        Storage::disk('s3')->assertMissing($projectFile->file_path);
    }

    /** @test */
    public function client_portal_shows_separated_file_sections()
    {
        // Create client reference file
        ProjectFile::create([
            'project_id' => $this->clientProject->id,
            'file_name' => 'client-brief.pdf',
            'file_path' => "projects/{$this->clientProject->id}/client-brief.pdf",
            'size' => 1024,
            'mime_type' => 'application/pdf',
            'user_id' => null,
        ]);
        
        // Generate signed URL for viewing portal (not uploading)
        $portalViewUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addHours(24),
            ['project' => $this->clientProject->id]
        );
        
        // Access client portal
        $response = $this->get($portalViewUrl);
        
        $response->assertStatus(200);
        
        // Should show separated sections
        $response->assertSee('Your Reference Files');
        $response->assertSee('Producer Deliverables');
        $response->assertSee('client-brief.pdf');
        
        // Should show upload area for client files
        $response->assertSee('Click to upload');
        $response->assertSee('drag and drop');
    }

    /** @test */
    public function authorization_prevents_cross_project_file_access()
    {
        // Create another project and file
        $otherProject = Project::factory()->create([
            'user_id' => User::factory()->create()->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
        ]);
        
        $otherFile = ProjectFile::create([
            'project_id' => $otherProject->id,
            'file_name' => 'other-file.pdf',
            'file_path' => "projects/{$otherProject->id}/other-file.pdf",
            'size' => 1024,
            'mime_type' => 'application/pdf',
            'user_id' => null,
        ]);
        
        // Try to download other project's file using our signed URL
        $response = $this->get(
            route('client.portal.download_project_file', [
                'project' => $this->clientProject->id,
                'projectFile' => $otherFile->id
            ])
        );
        
        // Should fail - file doesn't belong to this project
        $response->assertStatus(404);
    }
} 