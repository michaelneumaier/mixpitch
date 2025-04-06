<?php

namespace Tests\Feature\Livewire;

use App\Livewire\UserProfileEdit;
use App\Models\User;
use App\Models\Tag;
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
        
        // Set some tags and save
        $component->set('skills', [$skillTag1->id, $skillTag2->id])
            ->set('equipment', [$equipmentTag->id])
            ->set('specialties', [$specialtyTag->id])
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
        $this->assertArrayHasKey('allTags', $component->payload['effects']['html']);
        $this->assertArrayHasKey('skills', $component->payload['effects']['html']);
        $this->assertArrayHasKey('equipment', $component->payload['effects']['html']);
        $this->assertArrayHasKey('specialties', $component->payload['effects']['html']);
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
            ->assertHasNoErrors(['skills.0']);
    }
} 