<?php

namespace App\Http\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;

class UpdateProfileInformationForm extends Component
{
    use WithFileUploads;

    /**
     * The component's state.
     *
     * @var array
     */
    public $state = [];

    /**
     * The new avatar for the user.
     *
     * @var mixed
     */
    public $photo;

    /**
     * Determine if the verification email was sent.
     *
     * @var bool
     */
    public $verificationLinkSent = false;

    /**
     * Prepare the component.
     *
     * @return void
     */
    public function mount()
    {
        $user = Auth::user();

        $this->state = array_merge([
            'email' => $user->email,
        ], $user->withoutRelations()->toArray());
    }

    /**
     * Update the user's profile information.
     *
     * @param  \Laravel\Fortify\Contracts\UpdatesUserProfileInformation  $updater
     * @return void
     */
    public function updateProfileInformation(UpdatesUserProfileInformation $updater)
    {
        $this->resetErrorBag();

        try {
            if ($this->photo) {
                // Log information about the photo for debugging
                Log::info('Profile photo upload processing', [
                    'user_id' => Auth::id(),
                    'photo_pathname' => $this->photo->getPathname(),
                    'photo_extension' => $this->photo->getClientOriginalExtension(),
                    'photo_type' => get_class($this->photo)
                ]);
            }

            $updater->update(
                Auth::user(),
                $this->photo
                    ? array_merge($this->state, ['photo' => $this->photo])
                    : $this->state
            );

            if (isset($this->photo)) {
                // Redirect to refresh the page
                return redirect()->route('profile.show');
            }

            $this->dispatch('saved');
            $this->dispatch('refresh-navigation-menu');
        } catch (\Exception $e) {
            Log::error('Error updating profile information', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'photo_present' => isset($this->photo)
            ]);
            
            $this->addError('photo', 'There was an error uploading your photo. Please try again.');
        }
    }

    /**
     * Delete user's profile photo.
     *
     * @return void
     */
    public function deleteProfilePhoto()
    {
        Auth::user()->deleteProfilePhoto();

        $this->dispatch('refresh-navigation-menu');
    }

    /**
     * Sent the email verification.
     *
     * @return void
     */
    public function sendEmailVerification()
    {
        Auth::user()->sendEmailVerificationNotification();

        $this->verificationLinkSent = true;
    }

    /**
     * Get the current user of the application.
     *
     * @return mixed
     */
    public function getUserProperty()
    {
        return Auth::user();
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('profile.update-profile-information-form');
    }
} 