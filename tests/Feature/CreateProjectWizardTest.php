<?php

namespace Tests\Feature;

use App\Livewire\CreateProject;
use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateProjectWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create and authenticate a user
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Seed project types for tests that need them
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\ProjectTypeSeeder']);
    }

    /** @test */
    public function wizard_mode_is_enabled_for_new_projects()
    {
        Livewire::test(CreateProject::class)
            ->assertSet('useWizard', true)
            ->assertSet('currentStep', 1)
            ->assertSet('totalSteps', 4)
            ->assertSee('Choose Your Project Workflow');
    }

    /** @test */
    public function wizard_mode_is_disabled_for_edit_mode()
    {
        $project = Project::factory()->create(['user_id' => auth()->id()]);
        
        Livewire::test(CreateProject::class, ['project' => $project])
            ->assertSet('useWizard', false)
            ->assertSet('isEdit', true)
            ->assertSee('Edit Project');
    }

    /** @test */
    public function can_navigate_through_wizard_steps()
    {
        Livewire::test(CreateProject::class)
            ->assertSet('currentStep', 1)
            ->set('workflow_type', Project::WORKFLOW_TYPE_STANDARD)
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->assertSee('Project Details');
    }

    /** @test */
    public function can_go_back_to_previous_step()
    {
        Livewire::test(CreateProject::class)
            ->set('workflow_type', Project::WORKFLOW_TYPE_STANDARD)
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->call('previousStep')
            ->assertSet('currentStep', 1)
            ->assertSee('Choose Your Project Workflow');
    }

    /** @test */
    public function step_validation_prevents_progression_without_required_fields()
    {
        Livewire::test(CreateProject::class)
            ->assertSet('currentStep', 1)
            ->set('workflow_type', '') // Set empty to trigger validation
            ->call('nextStep')
            ->assertHasErrors(['workflow_type'])
            ->assertSet('currentStep', 1); // Should stay on step 1
    }

    /** @test */
    public function workflow_types_are_properly_configured()
    {
        $component = Livewire::test(CreateProject::class);
        
        $workflowTypes = $component->get('workflowTypes');
        
        // Should now have 3 workflow types (Direct Hire is hidden)
        $this->assertCount(3, $workflowTypes);
        $this->assertEquals(Project::WORKFLOW_TYPE_STANDARD, $workflowTypes[0]['value']);
        $this->assertEquals(Project::WORKFLOW_TYPE_CONTEST, $workflowTypes[1]['value']);
        $this->assertEquals(Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT, $workflowTypes[2]['value']);
        
        // Verify Direct Hire is not in the available options
        $workflowValues = array_column($workflowTypes, 'value');
        $this->assertNotContains(Project::WORKFLOW_TYPE_DIRECT_HIRE, $workflowValues);
    }

    /** @test */
    public function can_complete_standard_workflow_project()
    {
        Livewire::test(CreateProject::class)
            // Step 1: Workflow Selection
            ->set('workflow_type', Project::WORKFLOW_TYPE_STANDARD)
            ->call('nextStep')
            
            // Step 2: Basic Details
            ->set('form.name', 'Test Project')
            ->set('form.artistName', 'Test Artist')
            ->set('form.projectType', 'single')
            ->set('form.description', 'This is a test project description')
            ->set('form.genre', 'Rock')
            ->set('form.collaborationTypeMixing', true)
            ->call('nextStep')
            
            // Step 3: Configuration
            ->set('form.budgetType', 'free')
            ->call('nextStep')
            
            // Step 4: Review
            ->assertSee('Review Your Project')
            ->assertSee('Test Project')
            ->assertSee('Test Artist')
            ->call('save')
            ->assertHasNoErrors();
    }

    /** @test */
    public function contest_workflow_shows_specific_fields()
    {
        Livewire::test(CreateProject::class)
            ->set('workflow_type', Project::WORKFLOW_TYPE_CONTEST)
            ->call('nextStep')
            ->set('form.name', 'Contest Project')
            ->set('form.description', 'Contest description')
            ->set('form.genre', 'Pop')
            ->set('form.collaborationTypeMixing', true)
            ->call('nextStep')
            ->assertSee('Contest Settings')
            ->assertSee('Submission Deadline')
            ->assertSee('Contest Prizes');
    }

    /** @test */
    // Direct Hire workflow is temporarily disabled
    // public function direct_hire_workflow_shows_producer_search()
    // {
    //     Livewire::test(CreateProject::class)
    //         ->set('workflow_type', Project::WORKFLOW_TYPE_DIRECT_HIRE)
    //         ->call('nextStep')
    //         ->set('form.name', 'Direct Hire Project')
    //         ->set('form.description', 'Direct hire description')
    //         ->set('form.genre', 'Jazz')
    //         ->set('form.collaborationTypeMixing', true)
    //         ->call('nextStep')
    //         ->assertSee('Direct Hire Settings')
    //         ->assertSee('Target Producer');
    // }

    /** @test */
    public function client_management_workflow_shows_client_fields()
    {
        Livewire::test(CreateProject::class)
            ->set('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->call('nextStep')
            ->set('form.name', 'Client Project')
            ->call('nextStep') // Now on step 3
            ->assertSee('Client Management Settings')
            ->assertSee('Client Email')
            ->assertSee('Client Payment Amount');
    }

    /** @test */
    public function client_management_only_requires_project_name_in_step_2()
    {
        Livewire::test(CreateProject::class)
            ->set('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->call('nextStep')
            ->set('form.name', 'Client Project')
            // Don't set description, genre, or projectType - they should be optional
            ->call('nextStep')
            ->assertSet('currentStep', 3) // Should advance to step 3
            ->assertSee('Client Management Settings');
    }

    /** @test */
    public function standard_workflow_requires_all_fields_in_step_2()
    {
        $component = Livewire::test(CreateProject::class)
            ->set('workflow_type', Project::WORKFLOW_TYPE_STANDARD)
            ->call('nextStep')
            ->set('form.name', 'Standard Project')
            // Don't set required fields: description, projectType, genre
            // Don't set any collaboration types
            ->call('nextStep');
            
        // Should have validation error for collaboration types (which is checked first)
        $component->assertHasErrors(['collaboration_type'])
                  ->assertSet('currentStep', 2); // Should stay on step 2
    }

    /** @test */
    public function standard_workflow_requires_form_fields_when_collaboration_types_set()
    {
        $component = Livewire::test(CreateProject::class)
            ->set('workflow_type', Project::WORKFLOW_TYPE_STANDARD)
            ->call('nextStep')
            ->set('form.name', 'Standard Project')
            ->set('form.collaborationTypeMixing', true) // Set collaboration type
            // Don't set required fields: description, genre
            // Note: projectType has a default value of 'single' so it won't be in validation errors
            ->call('nextStep');
            
        // Should have validation errors for required form fields (excluding projectType which has default)
        $component->assertHasErrors(['form.description', 'form.genre'])
                  ->assertSet('currentStep', 2); // Should stay on step 2
    }

    /** @test */
    public function step_2_content_changes_based_on_workflow()
    {
        $component = Livewire::test(CreateProject::class);
        
        // Test Standard workflow content
        $component->set('workflow_type', Project::WORKFLOW_TYPE_STANDARD)
                  ->call('nextStep');
        $step2Content = $component->get('step2Content');
        $this->assertEquals('Project Details', $step2Content['title']);
        $this->assertStringContainsString('attract the right collaborators', $step2Content['subtitle']);
        
        // Test Client Management workflow content
        $component->set('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                  ->call('previousStep')
                  ->call('nextStep');
        $step2Content = $component->get('step2Content');
        $this->assertEquals('Client Project Setup', $step2Content['title']);
        $this->assertStringContainsString('Only the project name is required', $step2Content['subtitle']);
    }

    /** @test */
    public function client_management_workflow_does_not_show_budget_selector()
    {
        Livewire::test(CreateProject::class)
            ->set('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->call('nextStep')
            ->set('form.name', 'Client Project')
            ->call('nextStep') // Now on step 3
            ->assertDontSee('Project Budget')
            ->assertDontSee('Free Collaboration')
            ->assertSee('Client Payment Amount');
    }

    /** @test */
    public function project_summary_displays_correct_information()
    {
        $component = Livewire::test(CreateProject::class)
            ->set('workflow_type', Project::WORKFLOW_TYPE_STANDARD)
            ->call('nextStep')
            ->set('form.name', 'Summary Test Project')
            ->set('form.artistName', 'Summary Artist')
            ->set('form.projectType', 'album')
            ->set('form.description', 'Summary description')
            ->set('form.genre', 'Hip Hop')
            ->set('form.collaborationTypeMixing', true)
            ->set('form.collaborationTypeMastering', true)
            ->call('nextStep')
            ->set('form.budgetType', 'paid')
            ->set('form.budget', 500)
            ->call('nextStep');

        $summary = $component->get('projectSummary');
        
        $this->assertEquals('Summary Test Project', $summary['name']);
        $this->assertEquals('Summary Artist', $summary['artist_name']);
        $this->assertEquals('album', $summary['project_type']);
        $this->assertEquals('Hip Hop', $summary['genre']);
        $this->assertEquals(500, $summary['budget']);
        $this->assertContains('mixing', $summary['collaboration_types']);
        $this->assertContains('mastering', $summary['collaboration_types']);
    }
} 