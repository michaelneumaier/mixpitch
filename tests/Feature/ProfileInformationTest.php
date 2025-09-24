<?php

namespace Tests\Feature;

use App\Http\Livewire\Profile\UpdateProfileInformationForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_profile_information_is_available(): void
    {
        $this->actingAs($user = User::factory()->create([
            'username' => 'testuser',
        ]));

        $component = Livewire::test(UpdateProfileInformationForm::class);

        $this->assertEquals($user->name, $component->state['name']);
        $this->assertEquals($user->email, $component->state['email']);
        $this->assertEquals($user->username, $component->state['username']);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $this->actingAs($user = User::factory()->create([
            'username' => 'originaluser',
        ]));

        Livewire::test(UpdateProfileInformationForm::class)
            ->set('state', [
                'name' => 'Test Name',
                'email' => 'test@example.com',
                'username' => 'testuser',
                'bio' => null,
                'headline' => null,
                'website' => null,
                'location' => null,
                'social_links' => [],
                'tipjar_link' => null,
            ])
            ->call('updateProfileInformation');

        $this->assertEquals('Test Name', $user->fresh()->name);
        $this->assertEquals('test@example.com', $user->fresh()->email);
        $this->assertEquals('testuser', $user->fresh()->username);
    }
}
