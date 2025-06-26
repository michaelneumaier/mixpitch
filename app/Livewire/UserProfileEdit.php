<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Masmerise\Toaster\Toaster;

class UserProfileEdit extends Component
{
    use WithFileUploads;

    public $name;
    public $email;
    public $username;
    public $headline;
    public $bio;
    public $location;
    public $timezone;
    public $website;
    public $tipjar_link;
    public $skills = [];
    public $equipment = [];
    public $specialties = [];
    public $social_links = [];
    public $username_locked = false;
    public $profile_completed = false;
    public $profile_completion_percentage = 0;
    public $profilePhoto;

    // Add a protected property for temporary URL validation
    protected $temporaryUploadDirectory = 'livewire-tmp';

    // Set a longer expiration time for temporary URLs
    protected $temporaryUrlLifetime = 300; // 5 minutes

    protected $listeners = ['refreshProfilePhoto' => '$refresh'];

    protected function rules()
    {
        $usernameRule = 'required|string|alpha_dash|max:30|unique:users,username';
        // If username is locked, we don't need to validate uniqueness except for current user
        if ($this->username_locked) {
            $usernameRule = 'required|string|alpha_dash|max:30|unique:users,username,' . auth()->id();
        }

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
            'username' => $usernameRule,
            'headline' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:5000',
            'location' => 'nullable|string|max:255',
            'timezone' => 'required|string|in:' . implode(',', array_keys(config('timezone.user_selectable', []))),
            'website' => 'nullable|string|max:255',
            'tipjar_link' => 'nullable|string|max:255|allowed_tipjar_domain',
            'profilePhoto' => 'nullable|image|max:1024',
            'skills' => [
                'nullable', 'array',
                function ($attribute, $value, $fail) {
                    if (count($value) > 6) {
                        $fail("You can select a maximum of 6 skills.");
                    }
                },
            ],
            'skills.*' => 'exists:tags,id',
            'equipment' => [
                'nullable', 'array',
                function ($attribute, $value, $fail) {
                    if (count($value) > 6) {
                        $fail("You can select a maximum of 6 equipment items.");
                    }
                },
            ],
            'equipment.*' => 'exists:tags,id',
            'specialties' => [
                'nullable', 'array',
                function ($attribute, $value, $fail) {
                    if (count($value) > 6) {
                        $fail("You can select a maximum of 6 specialties.");
                    }
                },
            ],
            'specialties.*' => 'exists:tags,id',
            'social_links' => 'nullable|array',
            'social_links.instagram' => 'nullable|string|max:255',
            'social_links.twitter' => 'nullable|string|max:255',
            'social_links.facebook' => 'nullable|string|max:255',
            'social_links.soundcloud' => 'nullable|string|max:255',
            'social_links.spotify' => 'nullable|string|max:255',
            'social_links.youtube' => 'nullable|string|max:255',
        ];
    }

    protected $messages = [
        'username.unique' => 'This username is already taken. Please choose another one.',
        'username.alpha_dash' => 'Username can only contain letters, numbers, dashes and underscores.',
        'social_links.*.max' => 'Social media handle is too long.',
        'website' => 'Please enter a valid URL (e.g., example.com)',
        'tipjar_link.allowed_tipjar_domain' => 'The tipjar link must be from an approved service like PayPal.me, Ko-fi, etc.',
        'profilePhoto.image' => 'The profile photo must be an image file.',
        'profilePhoto.max' => 'The profile photo must not be larger than 1MB.',
    ];

    public function mount()
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->username = $user->username;
        $this->headline = $user->headline;
        $this->bio = $user->bio;
        $this->location = $user->location;
        $this->timezone = $user->timezone ?? config('timezone.default');
        $this->website = $user->website;
        $this->tipjar_link = $user->tipjar_link;
        $this->social_links = $user->social_links ?? [];
        $this->username_locked = $user->username_locked;
        $this->profile_completed = $user->profile_completed ?? false;

        // Load user's tags from taggables relationship
        $userTags = $user->tags()->get()->groupBy('type');
        
        // Convert tag IDs to strings for proper comparison in the frontend
        $this->skills = $userTags->get('skill', collect())->pluck('id')->map(function($id) {
            return (string)$id;
        })->toArray();
        
        $this->equipment = $userTags->get('equipment', collect())->pluck('id')->map(function($id) {
            return (string)$id;
        })->toArray();
        
        $this->specialties = $userTags->get('specialty', collect())->pluck('id')->map(function($id) {
            return (string)$id;
        })->toArray();

        // Calculate profile completion percentage on initial load
        $this->calculateProfileCompletion();
    }

    /**
     * Calculate the profile completion percentage
     */
    private function calculateProfileCompletion()
    {
        // Define required and optional fields to check for completion
        $requiredFields = ['name', 'email', 'username'];
        $optionalFields = ['headline', 'bio', 'location', 'website'];
        $arrayFields = ['skills', 'equipment', 'specialties', 'social_links'];

        // Count required fields that are filled
        $completedRequired = 0;
        foreach ($requiredFields as $field) {
            if (!empty($this->$field)) {
                $completedRequired++;
            }
        }

        // Count optional fields that are filled
        $completedOptional = 0;
        foreach ($optionalFields as $field) {
            if (!empty($this->$field)) {
                $completedOptional++;
            }
        }

        // Count array fields that have at least one non-empty value
        $completedArrays = 0;
        foreach ($arrayFields as $field) {
            if (is_array($this->$field) && count(array_filter($this->$field)) > 0) {
                $completedArrays++;
            }
        }

        // Calculate total completed fields and maximum possible
        $totalCompleted = $completedRequired + $completedOptional + $completedArrays;
        $totalPossible = count($requiredFields) + count($optionalFields) + count($arrayFields);

        // Calculate percentage
        $this->profile_completion_percentage = intval(($totalCompleted / $totalPossible) * 100);

        // Profile is considered complete if at least 70% is filled
        $this->profile_completed = $this->profile_completion_percentage >= 70;
    }

    public function updatedProfilePhoto()
    {
        // Validate the profile photo when it's updated
        $this->validate([
            'profilePhoto' => 'image|max:1024',
        ]);
    }

    public function save()
    {
        $this->validate();

        $user = auth()->user();

        // Clean and gather non-tag data
        if (!empty($this->username) && !$user->username_locked) {
            $this->username_locked = true;
        }

        // Prepend http:// to website if it doesn't have a protocol
        if (!empty($this->website) && !preg_match("~^(?:f|ht)tps?://~i", $this->website)) {
            $this->website = "https://" . $this->website;
        }

        // Prepend http:// to tipjar link if it doesn't have a protocol
        if (!empty($this->tipjar_link) && !preg_match("~^(?:f|ht)tps?://~i", $this->tipjar_link)) {
            $this->tipjar_link = "https://" . $this->tipjar_link;
        }

        // Prepare all non-tag data for saving
        $userData = [
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'headline' => $this->headline,
            'bio' => $this->bio,
            'location' => $this->location,
            'timezone' => $this->timezone,
            'website' => $this->website,
            'tipjar_link' => $this->tipjar_link,
            'social_links' => array_filter($this->social_links ?? []),
            'username_locked' => $this->username_locked,
        ];

        // Recalculate profile completion before saving
        $this->calculateProfileCompletion();
        $userData['profile_completed'] = $this->profile_completed;

        try {
            // Handle profile photo upload if needed
            if ($this->profilePhoto) {
                try {
                    $user->updateProfilePhoto($this->profilePhoto);
                    $this->profilePhoto = null;
                } catch (\Exception $e) {
                    Toaster::error('Failed to upload profile photo. Please try again.');
                    return;
                }
            }

            // Save user basic data
            $user->fill($userData);
            $user->save();
            
            // Handle tags - convert to integers for database
            $skillIds = array_map('intval', $this->skills ?? []);
            $equipmentIds = array_map('intval', $this->equipment ?? []);
            $specialtyIds = array_map('intval', $this->specialties ?? []);
            
            // Merge all tag IDs into one array
            $allTagIds = array_merge($skillIds, $equipmentIds, $specialtyIds);
            
            // Sync tags with the user
            $user->tags()->sync($allTagIds);

            Toaster::success('Profile updated successfully!');
            return redirect()->route('profile.edit');
        } catch (\Exception $e) {
            Toaster::error('An error occurred while updating your profile. Please try again.');
        }
    }

    public function getTimezoneOptions(): array
    {
        return config('timezone.user_selectable');
    }

    public function render()
    {
        // Get all tags from the database, grouped by type
        $allTags = \App\Models\Tag::all()->groupBy('type');
        
        // Convert the tag collection to arrays for JavaScript
        $allTagsForJs = $allTags->map(function($tags) {
            return $tags->map(function($tag) {
                return [
                    'id' => (string)$tag->id,  // Convert to string for JavaScript consistency
                    'name' => $tag->name
                ];
            })->values()->toArray();
        })->toArray();
        
        return view('livewire.user-profile-edit', [
            'allTagsForJs' => $allTagsForJs
        ]);
    }
}
