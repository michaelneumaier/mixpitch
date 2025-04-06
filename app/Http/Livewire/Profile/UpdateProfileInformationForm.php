<?php

namespace App\Http\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use App\Models\Tag;
use Illuminate\Support\Collection;

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
     * User's selected skill tags (array of IDs).
     * @var array
     */
    public $skillTags = [];

    /**
     * User's selected equipment tags (array of IDs).
     * @var array
     */
    public $equipmentTags = [];

    /**
     * User's selected specialty tags (array of IDs).
     * @var array
     */
    public $specialtyTags = [];

    /**
     * All available tags, grouped by type.
     * @var Collection
     */
    public Collection $allTags;

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
        $this->state = $user->withoutRelations()->toArray();

        // Load all tags grouped by type for the select inputs
        $this->allTags = Tag::all()->groupBy('type');

        // Load user's current tags and populate the respective arrays
        $userTags = $user->tags()->get()->groupBy('type');
        $this->skillTags = $userTags->get('skill', collect())->pluck('id')->toArray();
        $this->equipmentTags = $userTags->get('equipment', collect())->pluck('id')->toArray();
        $this->specialtyTags = $userTags->get('specialty', collect())->pluck('id')->toArray();
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
            // Merge state with photo and tag data
            $updateData = $this->state;
            if ($this->photo) {
                $updateData['photo'] = $this->photo;
            }
            $updateData['skillTags'] = $this->skillTags;
            $updateData['equipmentTags'] = $this->equipmentTags;
            $updateData['specialtyTags'] = $this->specialtyTags;

            Log::info('Updating profile with data:', $updateData); // Debugging

            $updater->update(Auth::user(), $updateData);

            if (isset($this->photo)) {
                return redirect()->route('profile.show');
            }

            $this->dispatch('saved');
            $this->dispatch('refresh-navigation-menu');

            // Refresh tag data after save
            $this->mount(); 

        } catch (\Exception $e) {
            Log::error('Error updating profile information', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(), // Add trace for debugging
                'user_id' => Auth::id(),
                'photo_present' => isset($this->photo)
            ]);
            
            // Add specific error handling if validation fails in the action
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                 $this->setErrorBag($e->validator->errors());
            } else {
                session()->flash('error', 'An unexpected error occurred while updating your profile.');
                // Potentially add a more specific error for photo uploads if needed
                 $this->addError('general', 'An unexpected error occurred. Please try again.');
            }
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