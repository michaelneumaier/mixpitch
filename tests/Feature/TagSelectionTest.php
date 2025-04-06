<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagSelectionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_profile_edit_page_loads_with_tag_selectors()
    {
        // Create test tags
        Tag::create(['name' => 'Mixing', 'type' => 'skill']);
        Tag::create(['name' => 'Pro Tools', 'type' => 'equipment']);
        Tag::create(['name' => 'Rock', 'type' => 'specialty']);
        
        // Create and authenticate user
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Request the profile edit page
        $response = $this->get(route('profile.edit'));
        
        // Check the response is successful
        $response->assertStatus(200);
        
        // Check for essential elements in the HTML
        $response->assertSee('Skills, Equipment &amp; Specialties', false);
        $response->assertSee('skills-select', false);
        $response->assertSee('equipment-select', false);
        $response->assertSee('specialties-select', false);
        
        // Check for Alpine.js initialization
        $response->assertSee('x-data="tagSelects', false);
    }
    
    /** @test */
    public function profile_edit_loads_correct_view_with_tag_data()
    {
        // Create test tags
        $skillTag = Tag::create(['name' => 'Mixing', 'type' => 'skill']);
        $equipmentTag = Tag::create(['name' => 'Pro Tools', 'type' => 'equipment']);
        $specialtyTag = Tag::create(['name' => 'Rock', 'type' => 'specialty']);
        
        // Create and authenticate user with tags
        $user = User::factory()->create();
        $user->tags()->attach([$skillTag->id, $equipmentTag->id, $specialtyTag->id]);
        $this->actingAs($user);
        
        // Request the profile edit page
        $response = $this->get(route('profile.edit'));
        
        // Check that the JSON data is included in the HTML response for Alpine.js
        $response->assertSee($skillTag->name, false);
        $response->assertSee($equipmentTag->name, false);
        $response->assertSee($specialtyTag->name, false);
    }
    
    /** @test */
    public function tag_collection_is_properly_formatted_for_javascript()
    {
        // Create test tags
        $skillTag = Tag::create(['name' => 'Mixing', 'type' => 'skill']);
        
        // Create and authenticate user
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Request the profile edit page
        $response = $this->get(route('profile.edit'));
        
        // Get the response content
        $content = $response->getContent();
        
        // Check that the allTags JSON data in x-data includes the skill tag
        $this->assertStringContainsString('"allTags":', $content);
        $this->assertStringContainsString('"skill":', $content);
        $this->assertStringContainsString($skillTag->name, $content);
    }
    
    /** @test */
    public function user_can_submit_profile_with_tags()
    {
        // Create test tags
        $skillTag = Tag::create(['name' => 'Mixing', 'type' => 'skill']);
        $equipmentTag = Tag::create(['name' => 'Pro Tools', 'type' => 'equipment']);
        $specialtyTag = Tag::create(['name' => 'Rock', 'type' => 'specialty']);
        
        // Create and authenticate user
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Prepare form data with tags
        $formData = [
            'name' => 'Test User',
            'email' => $user->email,
            'username' => $user->username,
            'skills' => [$skillTag->id],
            'equipment' => [$equipmentTag->id],
            'specialties' => [$specialtyTag->id]
        ];
        
        // Submit the form
        $response = $this->post(route('user-profile.update'), $formData);
        
        // Check redirect
        $response->assertRedirect();
        
        // Verify tags were saved
        $user->refresh();
        $this->assertTrue($user->tags->contains('id', $skillTag->id));
        $this->assertTrue($user->tags->contains('id', $equipmentTag->id));
        $this->assertTrue($user->tags->contains('id', $specialtyTag->id));
    }
} 