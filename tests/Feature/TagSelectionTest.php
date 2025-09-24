<?php

namespace Tests\Feature;

use App\Livewire\UserProfileEdit;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TagSelectionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_profile_edit_component_loads_with_tag_selectors()
    {
        // Create test tags
        Tag::create(['name' => 'Mixing', 'type' => 'skill']);
        Tag::create(['name' => 'Pro Tools', 'type' => 'equipment']);
        Tag::create(['name' => 'Rock', 'type' => 'specialty']);

        // Create and authenticate user
        $user = User::factory()->create();

        // Test the Livewire component directly
        $component = Livewire::actingAs($user)
            ->test(UserProfileEdit::class);

        // Check the component loads successfully
        $component->assertOk();

        // Check that allTagsForJs data is available
        $allTagsForJs = $component->viewData('allTagsForJs');
        $this->assertNotNull($allTagsForJs);
        $this->assertIsArray($allTagsForJs);

        // Check that we have the expected tag types
        $this->assertArrayHasKey('skill', $allTagsForJs);
        $this->assertArrayHasKey('equipment', $allTagsForJs);
        $this->assertArrayHasKey('specialty', $allTagsForJs);
    }

    /** @test */
    public function profile_edit_component_loads_correct_tag_data()
    {
        // Create test tags
        $skillTag = Tag::create(['name' => 'Mixing', 'type' => 'skill']);
        $equipmentTag = Tag::create(['name' => 'Pro Tools', 'type' => 'equipment']);
        $specialtyTag = Tag::create(['name' => 'Rock', 'type' => 'specialty']);

        // Create and authenticate user with tags
        $user = User::factory()->create();
        $user->tags()->attach([$skillTag->id, $equipmentTag->id, $specialtyTag->id]);

        // Test the Livewire component
        $component = Livewire::actingAs($user)
            ->test(UserProfileEdit::class);

        // Check that user's existing tags are loaded
        $this->assertContains((string) $skillTag->id, $component->get('skills'));
        $this->assertContains((string) $equipmentTag->id, $component->get('equipment'));
        $this->assertContains((string) $specialtyTag->id, $component->get('specialties'));
    }

    /** @test */
    public function tag_collection_is_properly_formatted_for_javascript()
    {
        // Create test tags
        $skillTag = Tag::create(['name' => 'Mixing', 'type' => 'skill']);

        // Create and authenticate user
        $user = User::factory()->create();

        // Test the Livewire component
        $component = Livewire::actingAs($user)
            ->test(UserProfileEdit::class);

        // Get the allTagsForJs data
        $allTagsForJs = $component->viewData('allTagsForJs');

        // Check that the skill tag is properly formatted
        $this->assertArrayHasKey('skill', $allTagsForJs);
        $this->assertIsArray($allTagsForJs['skill']);

        // Find our test tag in the skills array
        $foundTag = collect($allTagsForJs['skill'])->firstWhere('name', 'Mixing');
        $this->assertNotNull($foundTag);
        $this->assertEquals($skillTag->name, $foundTag['name']);
        $this->assertEquals((string) $skillTag->id, $foundTag['id']);
    }

    /** @test */
    public function user_can_update_profile_with_tags()
    {
        // Create test tags
        $skillTag = Tag::create(['name' => 'Mixing', 'type' => 'skill']);
        $equipmentTag = Tag::create(['name' => 'Pro Tools', 'type' => 'equipment']);
        $specialtyTag = Tag::create(['name' => 'Rock', 'type' => 'specialty']);

        // Create and authenticate user (username might be null from factory)
        $user = User::factory()->create();

        // Test the Livewire component
        $component = Livewire::actingAs($user)
            ->test(UserProfileEdit::class);

        // Update the profile with tags (handle null username like the working test)
        $component
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('username', $user->username ?? 'testuser'.uniqid())
            ->set('skills', array_map('strval', [$skillTag->id]))
            ->set('equipment', array_map('strval', [$equipmentTag->id]))
            ->set('specialties', array_map('strval', [$specialtyTag->id]))
            ->call('save')
            ->assertHasNoErrors(); // Check for validation errors

        // Verify tags were saved
        $user->refresh();

        // Debug: Check what tags are actually saved
        $savedTags = $user->tags()->get();
        $this->assertGreaterThan(0, $savedTags->count(), 'No tags were saved to the user');

        $this->assertTrue($user->tags->contains('id', $skillTag->id));
        $this->assertTrue($user->tags->contains('id', $equipmentTag->id));
        $this->assertTrue($user->tags->contains('id', $specialtyTag->id));
    }
}
