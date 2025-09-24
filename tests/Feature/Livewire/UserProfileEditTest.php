<?php

namespace Tests\Feature\Livewire;

use App\Livewire\UserProfileEdit;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserProfileEditTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(UserProfileEdit::class)
            ->assertOk();
    }

    /** @test */
    public function loads_all_tags_on_mount()
    {
        // Create test tags
        $skillTag = Tag::create(['name' => 'Mixing', 'type' => 'skill']);
        $equipmentTag = Tag::create(['name' => 'Pro Tools', 'type' => 'equipment']);
        $specialtyTag = Tag::create(['name' => 'Rock', 'type' => 'specialty']);

        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(UserProfileEdit::class);

        // Assert that allTags property contains the test tags
        $allTags = $component->viewData('allTags');
        $this->assertNotNull($allTags);
        $this->assertTrue($allTags->has('skill'));
        $this->assertTrue($allTags->has('equipment'));
        $this->assertTrue($allTags->has('specialty'));

        // Verify each tag type contains the expected tag
        $this->assertTrue($allTags['skill']->contains('id', $skillTag->id));
        $this->assertTrue($allTags['equipment']->contains('id', $equipmentTag->id));
        $this->assertTrue($allTags['specialty']->contains('id', $specialtyTag->id));
    }

    /** @test */
    public function loads_users_existing_tags_on_mount()
    {
        // Create test tags
        $skillTag = Tag::create(['name' => 'Mixing', 'type' => 'skill']);
        $equipmentTag = Tag::create(['name' => 'Pro Tools', 'type' => 'equipment']);
        $specialtyTag = Tag::create(['name' => 'Rock', 'type' => 'specialty']);

        // Create user with existing tags
        $user = User::factory()->create();
        $user->tags()->attach([$skillTag->id, $equipmentTag->id, $specialtyTag->id]);

        $component = Livewire::actingAs($user)
            ->test(UserProfileEdit::class);

        // Verify the component loads user's existing tags
        $this->assertTrue(in_array($skillTag->id, $component->get('skills')));
        $this->assertTrue(in_array($equipmentTag->id, $component->get('equipment')));
        $this->assertTrue(in_array($specialtyTag->id, $component->get('specialties')));
    }

    /** @test */
    public function can_update_user_tags()
    {
        // Create test tags
        $skillTag1 = Tag::create(['name' => 'Mixing', 'type' => 'skill']);
        $skillTag2 = Tag::create(['name' => 'Mastering', 'type' => 'skill']);
        $equipmentTag = Tag::create(['name' => 'Pro Tools', 'type' => 'equipment']);
        $specialtyTag = Tag::create(['name' => 'Rock', 'type' => 'specialty']);

        $user = User::factory()->create();

        // Start with no tags
        $component = Livewire::actingAs($user)
            ->test(UserProfileEdit::class);

        // Set required fields + some tags and save
        $component->set('name', $user->name)
            ->set('email', $user->email)
            ->set('username', $user->username ?? 'testuser2')
            ->set('skills', array_map('strval', [$skillTag1->id, $skillTag2->id]))
            ->set('equipment', array_map('strval', [$equipmentTag->id]))
            ->set('specialties', array_map('strval', [$specialtyTag->id]))
            ->call('save');

        // Refresh user from database
        $user->refresh();

        // Check that tags were saved correctly
        $this->assertEquals(2, $user->tags()->where('type', 'skill')->count());
        $this->assertEquals(1, $user->tags()->where('type', 'equipment')->count());
        $this->assertEquals(1, $user->tags()->where('type', 'specialty')->count());
        $this->assertTrue($user->tags->contains('id', $skillTag1->id));
        $this->assertTrue($user->tags->contains('id', $skillTag2->id));
        $this->assertTrue($user->tags->contains('id', $equipmentTag->id));
        $this->assertTrue($user->tags->contains('id', $specialtyTag->id));
    }

    /** @test */
    public function passes_tags_to_view()
    {
        // Create test tags
        $skillTag = Tag::create(['name' => 'Mixing', 'type' => 'skill']);
        $equipmentTag = Tag::create(['name' => 'Pro Tools', 'type' => 'equipment']);
        $specialtyTag = Tag::create(['name' => 'Rock', 'type' => 'specialty']);

        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(UserProfileEdit::class);

        // Assert the render method passes the expected variables to the view
        $this->assertNotNull($component->viewData('allTags'));
        $this->assertNotNull($component->viewData('skills'));
        $this->assertNotNull($component->viewData('equipment'));
        $this->assertNotNull($component->viewData('specialties'));
        $this->assertNotNull($component->viewData('allTagsForJs'));
    }

    /** @test */
    public function validation_prevents_invalid_tags()
    {
        // Create valid tag
        $skillTag = Tag::create(['name' => 'Mixing', 'type' => 'skill']);

        // Invalid tag ID that doesn't exist
        $invalidTagId = 9999;

        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(UserProfileEdit::class);

        // Try to set an invalid tag ID
        $component->set('skills', [$invalidTagId])
            ->call('save')
            ->assertHasErrors(['skills.0']);

        // Set valid tag and verify no errors
        $component->set('skills', [$skillTag->id])
            ->call('save')
            ->assertHasNoErrors(['skills', 'skills.0']);
    }

    /** @test */
    public function can_save_profile_with_maximum_allowed_tags_per_category()
    {
        $user = User::factory()->create();
        $skills = Tag::factory()->count(6)->create(['type' => 'skill']);
        $equipment = Tag::factory()->count(6)->create(['type' => 'equipment']);
        $specialties = Tag::factory()->count(6)->create(['type' => 'specialty']);

        Livewire::actingAs($user)
            ->test(UserProfileEdit::class)
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('username', $user->username ?? 'testuser')
            ->set('skills', $skills->pluck('id')->map(fn ($id) => (string) $id)->toArray())
            ->set('equipment', $equipment->pluck('id')->map(fn ($id) => (string) $id)->toArray())
            ->set('specialties', $specialties->pluck('id')->map(fn ($id) => (string) $id)->toArray())
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertCount(6, $user->tags()->where('type', 'skill')->get());
        $this->assertCount(6, $user->tags()->where('type', 'equipment')->get());
        $this->assertCount(6, $user->tags()->where('type', 'specialty')->get());
    }

    /** @test */
    public function cannot_save_profile_with_more_than_maximum_allowed_tags_per_category()
    {
        $user = User::factory()->create();
        $skills = Tag::factory()->count(7)->create(['type' => 'skill']);
        $equipment = Tag::factory()->count(7)->create(['type' => 'equipment']);
        $specialties = Tag::factory()->count(7)->create(['type' => 'specialty']);

        // Test exceeding skills limit
        Livewire::actingAs($user)
            ->test(UserProfileEdit::class)
            ->set('skills', $skills->pluck('id')->toArray())
            ->set('equipment', Tag::factory()->count(6)->create(['type' => 'equipment'])->pluck('id')->toArray())
            ->set('specialties', Tag::factory()->count(6)->create(['type' => 'specialty'])->pluck('id')->toArray())
            ->call('save')
            ->assertHasErrors(['skills']);

        // Test exceeding equipment limit
        Livewire::actingAs($user)
            ->test(UserProfileEdit::class)
            ->set('skills', Tag::factory()->count(6)->create(['type' => 'skill'])->pluck('id')->toArray())
            ->set('equipment', $equipment->pluck('id')->toArray())
            ->set('specialties', Tag::factory()->count(6)->create(['type' => 'specialty'])->pluck('id')->toArray())
            ->call('save')
            ->assertHasErrors(['equipment']);

        // Test exceeding specialties limit
        Livewire::actingAs($user)
            ->test(UserProfileEdit::class)
            ->set('skills', Tag::factory()->count(6)->create(['type' => 'skill'])->pluck('id')->toArray())
            ->set('equipment', Tag::factory()->count(6)->create(['type' => 'equipment'])->pluck('id')->toArray())
            ->set('specialties', $specialties->pluck('id')->toArray())
            ->call('save')
            ->assertHasErrors(['specialties']);
    }
}
