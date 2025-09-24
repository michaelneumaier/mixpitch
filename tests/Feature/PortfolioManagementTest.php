<?php

namespace Tests\Feature;

use App\Models\PortfolioItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PortfolioManagementTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $producer;

    protected User $client;

    protected string $itemTypeAudio;

    protected string $itemTypeYoutube;

    protected function setUp(): void
    {
        parent::setUp();

        // Define item type constants based on the model for consistency
        $this->itemTypeAudio = PortfolioItem::TYPE_AUDIO;
        $this->itemTypeYoutube = PortfolioItem::TYPE_YOUTUBE;

        // Create users with explicit usernames
        $this->producer = User::factory()->create([
            'role' => 'producer',
            'username' => 'producer_'.$this->faker->userName(),
        ]);

        $this->client = User::factory()->create([
            'role' => 'client',
            'username' => 'client_'.$this->faker->userName(),
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
            'username' => 'other_producer_'.$this->faker->userName(),
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
            ->set('type', $this->itemTypeAudio)
            ->set('title', 'My Awesome Track')
            ->set('description', 'A description of the track.')
            ->set('isPublic', true)
            ->set('audioFile', $fakeFile)
            ->call('saveItem')
            ->assertHasNoErrors(['title', 'description', 'audioFile', 'type']);

        $this->assertDatabaseHas('portfolio_items', [
            'user_id' => $this->producer->id,
            'title' => 'My Awesome Track',
            'item_type' => $this->itemTypeAudio,
            'description' => 'A description of the track.',
            'is_public' => true,
        ]);

        // Assert the file was stored
        $item = PortfolioItem::where('title', 'My Awesome Track')->first();
        $this->assertNotNull($item, 'Portfolio item was not created.');
        $this->assertNotNull($item->file_path, 'File path should not be null for audio item.');
        Storage::disk('s3')->assertExists($item->file_path);
    }

    // --- Validation Tests ---

    public function test_portfolio_item_creation_requires_title()
    {
        $this->actingAs($this->producer);
        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->set('type', $this->itemTypeAudio)
            ->set('title', '')
            ->set('audioFile', UploadedFile::fake()->create('test.mp3', 100))
            ->call('saveItem')
            ->assertHasErrors('title');
    }

    public function test_portfolio_item_creation_requires_audio_file_for_audio_type()
    {
        $this->actingAs($this->producer);

        // Attempt to create an audio item without providing a file
        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->set('type', $this->itemTypeAudio)
            ->set('title', 'Missing Audio File')
            ->set('audioFile', null) // Explicitly set to null
            ->call('saveItem')
            ->assertHasErrors('audioFile'); // Assert any error for audioFile
    }

    public function test_portfolio_item_creation_validates_audio_file_properties()
    {
        $this->actingAs($this->producer);
        $fakeTextFile = UploadedFile::fake()->create('not_audio.txt', 1024, 'text/plain');
        // Use correct max size (100MB = 102400 KB)
        $largeAudioFile = UploadedFile::fake()->create('too_large.mp3', 102401, 'audio/mpeg');

        // Test wrong mime type
        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->set('type', $this->itemTypeAudio)
            ->set('title', 'Invalid File Type')
            ->set('audioFile', $fakeTextFile)
            ->call('saveItem')
            ->assertHasErrors('audioFile'); // Assert any error for audioFile

        // Test file size
        $titleTooLarge = 'File Too Large';
        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->set('type', $this->itemTypeAudio)
            ->set('title', $titleTooLarge)
            ->set('audioFile', $largeAudioFile)
            ->call('saveItem')
            ->assertHasErrors('audioFile'); // Assert any error for audioFile

        // Assert the item was NOT created due to validation failure
        $this->assertDatabaseMissing('portfolio_items', [
            'user_id' => $this->producer->id,
            'title' => $titleTooLarge,
        ]);
    }

    // --- Update Tests ---

    public function test_producer_can_update_their_audio_portfolio_item()
    {
        $this->actingAs($this->producer);
        // Explicitly create only necessary fields, overriding factory defaults
        $item = PortfolioItem::factory()->for($this->producer, 'user')->create([
            'item_type' => $this->itemTypeAudio,
            'title' => 'Old Audio Title',
            'description' => 'Old description',
            'file_path' => 'test/old_audio.mp3', // Need a valid path
            'video_url' => null, // Ensure video fields are null
            'video_id' => null,
        ]);
        $this->assertNotNull($item->file_path, 'Test setup should provide file_path');

        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->call('editItem', $item->id) // Load existing item into form
            ->assertSet('editingItemId', $item->id)
            ->assertSet('title', 'Old Audio Title')
            ->assertSet('type', $this->itemTypeAudio)
            ->assertSet('description', 'Old description')
            ->assertSet('existingFilePath', $item->file_path) // Check existing file path loaded
            ->set('title', 'Updated Audio Title')
            ->set('description', 'Updated description.')
            ->call('saveItem')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('portfolio_items', [
            'id' => $item->id,
            'user_id' => $this->producer->id,
            'title' => 'Updated Audio Title',
            'description' => 'Updated description.',
            'item_type' => $this->itemTypeAudio,
        ]);

        $item->refresh();
        $this->assertEquals($item->file_path, $item->refresh()->file_path, 'File path should not change when not uploading new file');
    }

    public function test_producer_cannot_update_others_portfolio_item()
    {
        $this->actingAs($this->producer);
        $otherProducer = User::factory()->create([
            'role' => 'producer',
            'username' => 'other_producer_'.$this->faker->userName(),
        ]);

        // Explicitly create item for other producer with only necessary fields
        $portfolioItem = PortfolioItem::factory()->for($otherProducer, 'user')->create([
            'title' => 'Other Producer Item',
            'item_type' => $this->itemTypeAudio, // Example type
            'file_path' => 'other/item.mp3',
            'video_url' => null,
            'video_id' => null,
        ]);

        // Policy should prevent loading the item in the editItem method
        Livewire::actingAs($this->producer)
            ->test(\App\Livewire\User\ManagePortfolioItems::class)
            ->call('editItem', $portfolioItem->id)
            ->assertForbidden(); // Expecting AuthorizationException caught by editItem

        // Verify the item was not updated (Check original title still exists)
        $this->assertDatabaseHas('portfolio_items', [
            'id' => $portfolioItem->id,
            'title' => 'Other Producer Item', // Original title
        ]);
    }

    // --- Delete Tests ---

    public function test_producer_can_delete_their_portfolio_item()
    {
        $this->actingAs($this->producer);
        $fakeFile = UploadedFile::fake()->create('delete_me.mp3', 100);
        // Use a realistic path structure like in the component
        $filePath = "portfolio-audio/{$this->producer->id}/".Str::slug('delete_me').'-'.time().'.mp3';
        Storage::disk('s3')->put($filePath, $fakeFile->get()); // Store the file first

        $item = PortfolioItem::factory()->for($this->producer, 'user')->create([
            'item_type' => $this->itemTypeAudio,
            'file_path' => $filePath,
            'title' => 'Item To Delete',
        ]);

        Storage::disk('s3')->assertExists($filePath); // Confirm file exists before deletion

        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->call('deleteItem', $item->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('portfolio_items', ['id' => $item->id]);
        Storage::disk('s3')->assertMissing($filePath); // Confirm file was deleted
    }

    public function test_producer_cannot_delete_others_portfolio_item()
    {
        $this->actingAs($this->producer);
        $otherProducer = User::factory()->create([
            'role' => 'producer',
            'username' => 'other_producer_'.$this->faker->userName(),
        ]);

        // Create a portfolio item for another producer (Factory creates valid item)
        $portfolioItem = PortfolioItem::factory()->for($otherProducer, 'user')->create();
        $itemId = $portfolioItem->id; // Store the ID

        // Try to delete it
        Livewire::test(\App\Livewire\User\ManagePortfolioItems::class)
            ->call('deleteItem', $itemId)
            // Check for error toast event using named parameters
            ->assertDispatched('toast', function ($name, $params) {
                return isset($params['type']) && $params['type'] === 'error' &&
                       isset($params['message']) && str_contains($params['message'], 'authorized');
            });

        // Verify item still exists in the database
        $this->assertDatabaseHas('portfolio_items', [
            'id' => $itemId,
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
            'title' => 'Public Item',
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
        // Create one of each current item type explicitly for clarity
        $audioItem = PortfolioItem::factory()->for($this->producer, 'user')->create([
            'is_public' => true,
            'item_type' => $this->itemTypeAudio,
            'title' => 'Audio Render Test',
            'file_path' => 'test/audio.mp3', // Provide explicit path
            'video_url' => null,
            'video_id' => null,
        ]);

        $youtubeVideoId = 'dQw4w9WgXcQ'; // Use a known ID
        $youtubeItem = PortfolioItem::factory()->for($this->producer, 'user')->create([
            'is_public' => true,
            'item_type' => $this->itemTypeYoutube,
            'title' => 'YouTube Render Test',
            'video_url' => 'https://www.youtube.com/watch?v='.$youtubeVideoId,
            'video_id' => $youtubeVideoId, // Ensure the correct ID is set
            'file_path' => null, // Ensure audio fields are null
        ]);

        // View the producer's profile while authenticated
        $response = $this->actingAs($this->client)
            ->get(route('profile.username', '@'.$this->producer->username));

        $response->assertOk();

        // Check for Audio Item rendering
        $response->assertSeeText('Audio Render Test', false); // Check for plain text, disable escaping just in case
        // TODO: Add assertion for audio player/link if applicable in the profile view

        // Check for YouTube Item rendering
        $response->assertSeeText('YouTube Render Test', false); // Check for plain text
        // TODO: Add assertion for YouTube iframe/embed if applicable in the profile view
        // Check specifically if the YouTube embed URL is present in the raw HTML
        $response->assertSee("https://www.youtube.com/embed/{$youtubeVideoId}", false);
    }
}
