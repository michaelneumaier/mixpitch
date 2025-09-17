<?php

namespace Tests\Feature;

use App\Livewire\LinkImporter;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LinkImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_successfully()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => 'client_management',
        ]);

        $this->actingAs($user);

        Livewire::test(LinkImporter::class, ['project' => $project])
            ->assertStatus(200)
            ->assertSee('Import from Link');
    }

    public function test_can_show_import_modal()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => 'client_management',
        ]);

        $this->actingAs($user);

        Livewire::test(LinkImporter::class, ['project' => $project])
            ->call('showImportModal')
            ->assertSet('showModal', true)
            ->assertSee('Import from Sharing Link');
    }

    public function test_validates_url_format()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => 'client_management',
        ]);

        $this->actingAs($user);

        Livewire::test(LinkImporter::class, ['project' => $project])
            ->set('importUrl', 'not-a-url')
            ->call('importFromLink')
            ->assertHasErrors(['importUrl']);
    }

    public function test_can_submit_valid_wetransfer_url()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => 'client_management',
        ]);

        $this->actingAs($user);

        Livewire::test(LinkImporter::class, ['project' => $project])
            ->set('importUrl', 'https://wetransfer.com/downloads/test123')
            ->call('importFromLink')
            ->assertHasNoErrors()
            ->assertSet('showModal', false)
            ->assertSet('importProgress.active', true);

        $this->assertDatabaseHas('link_imports', [
            'project_id' => $project->id,
            'source_url' => 'https://wetransfer.com/downloads/test123',
        ]);
    }
}
