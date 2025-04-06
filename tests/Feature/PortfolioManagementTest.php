<?php

namespace Tests\Feature;

use App\Models\PortfolioItem;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PortfolioManagementTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $producer;
    protected User $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with explicit usernames
        $this->producer = User::factory()->create([
            'role' => 'producer',
            'username' => 'producer_' . $this->faker->userName()
        ]);
        
        $this->client = User::factory()->create([
            'role' => 'client',
            'username' => 'client_' . $this->faker->userName()
        ]);

        // Use fake S3 storage
        Storage::fake('s3');
    }

    public function test_guest_cannot_access_portfolio_management()
    {
        $this->get(route('profile.portfolio'))
            ->assertRedirect(route('login'));
    }

    public function test_client_can_access_portfolio_management()
    {
        $this->actingAs($this->client)
            ->get(route('profile.portfolio'))
            ->assertSuccessful()
            ->assertSeeLivewire(\App\Livewire\User\ManagePortfolioItems::class);
    }

    public function test_producer_can_access_portfolio_management()
    {
        $this->actingAs($this->producer)
            ->get(route('profile.portfolio'))
            ->assertSuccessful()
            ->assertSeeLivewire(\App\Livewire\User\ManagePortfolioItems::class);
    }

    // Basic structure for testing actions against other users' items
    public function test_producer_cannot_manage_others_portfolio()
    {
        $otherProducer = User::factory()->create([
            'role' => 'producer',
            'username' => 'other_producer_' . $this->faker->userName()
        ]);
        $portfolioItem = PortfolioItem::factory()->for($otherProducer, 'user')->create();

        // Attempting to view the edit form or call update/delete actions
        // should be tested within the specific action tests (update/delete)
        $this->assertTrue(true); // Placeholder, real tests below
    }

    // --- Creation Tests ---

    public function test_producer_can_create_audio_portfolio_item()
    {
        $this->actingAs($this->producer);
        $fakeFile = UploadedFile::fake()->create('test_audio.mp3', 1024, 'audio/mpeg');

        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->set('itemType', 'audio_upload')
            ->set('title', 'My Awesome Track')
            ->set('description', 'A description of the track.')
            ->set('isPublic', true)
            ->set('audioFile', $fakeFile)
            ->call('saveItem')
            ->assertHasNoErrors(['title', 'description', 'audioFile', 'itemType']);

        $this->assertDatabaseHas('portfolio_items', [
            'user_id' => $this->producer->id,
            'title' => 'My Awesome Track',
            'item_type' => 'audio_upload',
            'description' => 'A description of the track.',
            'is_public' => true,
        ]);

        // Assert the file was stored
        $item = PortfolioItem::where('title', 'My Awesome Track')->first();
        Storage::disk('s3')->assertExists($item->file_path);
    }

    public function test_producer_can_create_external_link_portfolio_item()
    {
        $this->actingAs($this->producer);

        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->set('itemType', 'external_link')
            ->set('title', 'My Portfolio Website')
            ->set('description', 'Link to my external site.')
            ->set('isPublic', true)
            ->set('externalUrl', 'https://example.com')
            ->call('saveItem')
            ->assertHasNoErrors(['title', 'description', 'externalUrl', 'itemType']);

        $this->assertDatabaseHas('portfolio_items', [
            'user_id' => $this->producer->id,
            'title' => 'My Portfolio Website',
            'item_type' => 'external_link',
            'external_url' => 'https://example.com',
            'is_public' => true,
        ]);
    }

    public function test_producer_can_create_mixpitch_project_link_portfolio_item()
    {
        $this->actingAs($this->producer);
        $project = Project::factory()->create(['user_id' => $this->client->id]); // Project owned by someone else

        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->set('itemType', 'mixpitch_project_link')
            ->set('title', 'Mixpitch Project Showcase') // Optional title
            ->set('description', 'Worked on this cool project.')
            ->set('isPublic', true)
            ->set('linkedProjectId', $project->id)
            ->call('saveItem')
            ->assertHasNoErrors(['title', 'description', 'linkedProjectId', 'itemType']);

        $this->assertDatabaseHas('portfolio_items', [
            'user_id' => $this->producer->id,
            'title' => 'Mixpitch Project Showcase',
            'item_type' => 'mixpitch_project_link',
            'linked_project_id' => $project->id,
            'is_public' => true,
        ]);
    }

    // --- Validation Tests ---

    public function test_portfolio_item_creation_requires_title()
    {
        $this->actingAs($this->producer);

        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->set('itemType', 'external_link')
            ->set('title', '') // Empty title
            ->call('saveItem')
            ->assertHasErrors(['title' => 'required']);
    }

    public function test_portfolio_item_creation_validates_audio_file()
    {
        $this->actingAs($this->producer);
        $fakeTextFile = UploadedFile::fake()->create('not_audio.txt', 1024, 'text/plain');
        $largeAudioFile = UploadedFile::fake()->create('too_large.mp3', 102500, 'audio/mpeg'); // Larger than 102400 (100MB)

        // Test wrong mime type
        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->set('itemType', 'audio_upload')
            ->set('title', 'Invalid File Type')
            ->set('audioFile', $fakeTextFile)
            ->call('saveItem')
            ->assertHasErrors(['audioFile' => 'mimes']);

        // Test file size (exceeds max size of 102400 KB = 100MB)
        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->set('itemType', 'audio_upload')
            ->set('title', 'File Too Large')
            ->set('audioFile', $largeAudioFile)
            ->call('saveItem')
            ->assertHasErrors(['audioFile']);
    }

    public function test_portfolio_item_creation_validates_external_url()
    {
        $this->actingAs($this->producer);

        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->set('itemType', 'external_link')
            ->set('title', 'Invalid URL')
            ->set('externalUrl', 'not-a-valid-url')
            ->call('saveItem')
            ->assertHasErrors(['externalUrl' => 'url']);

         Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->set('itemType', 'external_link')
            ->set('title', 'Missing URL')
            ->set('externalUrl', '') // Required if type is external_link
            ->call('saveItem')
            ->assertHasErrors(['externalUrl' => 'required_if']);
    }

    public function test_portfolio_item_creation_validates_project_id()
    {
        $this->actingAs($this->producer);

        // Test non-existent project ID
        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->set('itemType', 'mixpitch_project_link')
            ->set('title', 'Invalid Project Link')
            ->set('linkedProjectId', 999) // Non-existent ID
            ->call('saveItem')
            ->assertHasErrors(['linkedProjectId' => 'exists']);

        // Test missing project ID
        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->set('itemType', 'mixpitch_project_link')
            ->set('title', 'Missing Project Link')
            ->set('linkedProjectId', null) // Required if type is mixpitch_project_link
            ->call('saveItem')
            ->assertHasErrors(['linkedProjectId' => 'required_if']);
    }

    // --- Update Tests ---

    public function test_producer_can_update_their_portfolio_item()
    {
        $this->actingAs($this->producer);
        $item = PortfolioItem::factory()->for($this->producer, 'user')->create([
            'title' => 'Old Title',
            'item_type' => 'external_link',
            'external_url' => 'https://example.com'
        ]);

        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->call('editItem', $item->id)
            ->assertSet('editingItemId', $item->id)
            ->assertSet('title', $item->title)
            ->set('title', 'Updated Title')
            ->set('description', 'Updated description.')
            ->set('linkedProjectId', null)
            ->call('saveItem')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('portfolio_items', [
            'id' => $item->id,
            'user_id' => $this->producer->id,
            'title' => 'Updated Title',
            'description' => 'Updated description.',
        ]);
    }

    public function test_producer_cannot_update_others_portfolio_item()
    {
        $this->actingAs($this->producer);
        $otherProducer = User::factory()->create([
            'role' => 'producer',
            'username' => 'other_producer_' . $this->faker->userName()
        ]);
        
        // Portfolio item created by another producer
        $portfolioItem = PortfolioItem::factory()->for($otherProducer, 'user')->create([
            'item_type' => 'external_link',
            'external_url' => 'https://old-example.com'
        ]);

        // Try to actually perform the update and expect it to fail gracefully
        Livewire::actingAs($this->producer)
            ->test(\App\Livewire\User\ManagePortfolioItems::class)
            ->set('editingItemId', $portfolioItem->id)
            ->set('title', 'Unauthorized Update')
            ->set('externalUrl', 'https://hacked.com')
            ->call('saveItem');

        // Verify the item was not updated
        $this->assertDatabaseMissing('portfolio_items', [
            'id' => $portfolioItem->id,
            'title' => 'Unauthorized Update',
            'external_url' => 'https://hacked.com'
        ]);
    }

    // --- Delete Tests ---

    public function test_producer_can_delete_their_portfolio_item()
    {
        $this->actingAs($this->producer);
        $fakeFile = UploadedFile::fake()->create('delete_me.mp3', 100);
        $path = $fakeFile->store('portfolio-audio', 's3'); // Store the file first
        $item = PortfolioItem::factory()->for($this->producer, 'user')->create([
            'item_type' => 'audio_upload',
            'file_path' => $path,
        ]);

        Storage::disk('s3')->assertExists($path); // Confirm file exists before deletion

        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->call('deleteItem', $item->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('portfolio_items', ['id' => $item->id]);
        Storage::disk('s3')->assertMissing($path); // Confirm file was deleted
    }

    public function test_producer_cannot_delete_others_portfolio_item()
    {
        $this->actingAs($this->producer);
        $otherProducer = User::factory()->create([
            'role' => 'producer',
            'username' => 'other_producer_' . $this->faker->userName()
        ]);
        
        // Create a portfolio item for another producer
        $portfolioItem = PortfolioItem::factory()->for($otherProducer, 'user')->create();

        // Try to delete it
        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->call('deleteItem', $portfolioItem->id);

        // Verify item still exists
        $this->assertDatabaseHas('portfolio_items', [
            'id' => $portfolioItem->id
        ]);
    }

     // --- Sorting Test ---

     public function test_producer_can_reorder_portfolio_items()
     {
         $this->actingAs($this->producer);
         $item1 = PortfolioItem::factory()->for($this->producer, 'user')->create(['display_order' => 1]);
         $item2 = PortfolioItem::factory()->for($this->producer, 'user')->create(['display_order' => 2]);
         $item3 = PortfolioItem::factory()->for($this->producer, 'user')->create(['display_order' => 3]);

         // Simulate the data structure sent by livewire-sortable
         // Example: Moving item3 to the top
         $newOrder = [
             ['order' => 1, 'value' => $item3->id], // item3 moved to position 1
             ['order' => 2, 'value' => $item1->id], // item1 moved to position 2
             ['order' => 3, 'value' => $item2->id], // item2 moved to position 3
         ];


         Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
             ->call('updateSort', $newOrder)
             ->assertHasNoErrors();

         // Refresh models from DB and assert new order
         $this->assertEquals(1, $item3->refresh()->display_order);
         $this->assertEquals(2, $item1->refresh()->display_order);
         $this->assertEquals(3, $item2->refresh()->display_order);
     }

    // --- Public Profile Display Tests ---

    public function test_public_portfolio_items_are_displayed_on_profile()
    {
        // Create a public portfolio item
        $item = PortfolioItem::factory()->for($this->producer, 'user')->create([
            'is_public' => true,
            'title' => 'Public Item'
        ]);

        // View the producer's profile while authenticated
        $this->actingAs($this->client) // Login as client to view producer's profile
            ->get(route('profile.username', '@'.$this->producer->username))
            ->assertOk()
            ->assertSeeText('Public Item');
    }

    public function test_private_portfolio_items_are_not_displayed_on_profile()
    {
        $itemPrivate = PortfolioItem::factory()->for($this->producer, 'user')->create([
            'title' => 'Private Item',
            'is_public' => false,
        ]);

        // Acting as another user (or guest) to view the profile
        $this->actingAs($this->client)
             ->get(route('profile.username', '@'.$this->producer->username))
             ->assertOk()
             ->assertDontSeeText('Private Item');

        // Also check as guest
        $this->get(route('profile.username', '@'.$this->producer->username))
             ->assertOk()
             ->assertDontSeeText('Private Item');
    }

    public function test_portfolio_items_render_correctly_based_on_type()
    {
        // Create one of each item type
        
        // Audio upload item
        $audioItem = PortfolioItem::factory()->for($this->producer, 'user')->create([
            'is_public' => true,
            'item_type' => 'audio_upload',
            'title' => 'Audio Test Item',
            'file_path' => 'portfolio-audio/some_audio.mp3'
        ]);
        
        // External link item
        $linkItem = PortfolioItem::factory()->for($this->producer, 'user')->create([
            'is_public' => true,
            'item_type' => 'external_link',
            'title' => 'External Link Test',
            'external_url' => 'https://example.com'
        ]);
        
        // Mixpitch project link item
        $project = Project::factory()->create();
        $projectItem = PortfolioItem::factory()->for($this->producer, 'user')->create([
            'is_public' => true,
            'item_type' => 'mixpitch_project_link',
            'title' => 'Project Link Test',
            'linked_project_id' => $project->id
        ]);
        
        // Ensure audio URL will work in test
        $expectedAudioUrl = Storage::disk('s3')->url($audioItem->file_path);
        $expectedProjectUrl = route('projects.show', $project); // Assuming this route exists
        
        // View the producer's profile while authenticated
        $response = $this->actingAs($this->client)
            ->get(route('profile.username', '@'.$this->producer->username));
        
        $response->assertOk();
        
        // Check for Audio Item rendering
        $response->assertSee('Audio Test Item');
        
        // Check for External Link rendering
        $response->assertSee('External Link Test');
        $response->assertSee('https://example.com');
        
        // Check for Project Link rendering
        $response->assertSee('Project Link Test');
    }
} 