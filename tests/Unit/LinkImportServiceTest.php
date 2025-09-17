<?php

namespace Tests\Unit;

use App\Models\LinkImport;
use App\Models\Project;
use App\Models\User;
use App\Services\LinkImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LinkImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LinkImportService $service;

    protected User $user;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LinkImportService::class);

        // Create test user and project
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'client_management',
        ]);
    }

    public function test_can_create_link_import_with_valid_url()
    {
        $url = 'https://wetransfer.com/downloads/test123';

        $import = $this->service->createImport($this->project, $url, $this->user);

        $this->assertInstanceOf(LinkImport::class, $import);
        $this->assertEquals($this->project->id, $import->project_id);
        $this->assertEquals($this->user->id, $import->user_id);
        $this->assertEquals($url, $import->source_url);
        $this->assertEquals('wetransfer.com', $import->source_domain);
        $this->assertEquals(LinkImport::STATUS_PENDING, $import->status);

        $this->assertDatabaseHas('link_imports', [
            'id' => $import->id,
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'source_url' => $url,
            'status' => LinkImport::STATUS_PENDING,
        ]);
    }

    public function test_rejects_invalid_url()
    {
        $invalidUrl = 'not-a-url';

        $this->expectException(ValidationException::class);

        $this->service->createImport($this->project, $invalidUrl, $this->user);
    }

    public function test_rejects_unsupported_domain()
    {
        $unsupportedUrl = 'https://malicious-site.com/downloads/test';

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Domain not supported');

        $this->service->createImport($this->project, $unsupportedUrl, $this->user);
    }

    public function test_get_import_statistics()
    {
        // Create imports with different statuses
        LinkImport::factory()->create([
            'project_id' => $this->project->id,
            'status' => LinkImport::STATUS_COMPLETED,
            'imported_files' => [1, 2, 3], // 3 files
        ]);

        LinkImport::factory()->create([
            'project_id' => $this->project->id,
            'status' => LinkImport::STATUS_FAILED,
        ]);

        $stats = $this->service->getImportStatistics($this->project);

        $this->assertEquals(2, $stats['total_imports']);
        $this->assertEquals(1, $stats['completed_imports']);
        $this->assertEquals(1, $stats['failed_imports']);
        $this->assertEquals(3, $stats['total_files_imported']);
        $this->assertEquals(50.0, $stats['success_rate']); // 1 out of 2 completed
    }
}
