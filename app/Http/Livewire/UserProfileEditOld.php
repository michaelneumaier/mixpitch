<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Tag;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserProfileEditOld extends Component
{
    use WithFileUploads;

    public $name;
    public $email;
    public $username;
    public $headline;
    public $bio;
    public $location;
    public $website;
    public $tipjar_link;
    public $skills = [];
    public $equipment = [];
    public $specialties = [];

    public Collection $allTags;

    public $social_links = [];
    public $is_username_locked = false;
    public $profile_completed = false;
    public $profile_completion_percentage = 0;
    public $profilePhoto;

    protected $temporaryUploadDirectory = 'livewire-tmp';

    protected $temporaryUrlLifetime = 300;

    protected $listeners = ['refreshProfilePhoto' => '$refresh'];

    // Ensure arrays are cast properly
    protected $casts = [
        'skills' => 'array',
        'equipment' => 'array',
        'specialties' => 'array',
        'social_links' => 'array',
    ];

    protected function rules()
    {
        $userId = auth()->id();
        $usernameRule = 'required|string|alpha_dash|max:30|unique:users,username';
        if ($this->is_username_locked) {
            $usernameRule = 'required|string|alpha_dash|max:30|unique:users,username,' . $userId;
        }

        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'username' => $usernameRule,
            'headline' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:5000',
            'location' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'tipjar_link' => 'nullable|string|max:255|allowed_tipjar_domain',
            'profilePhoto' => 'nullable|image|max:1024',
            'skills' => 'nullable|array',
            'skills.*' => ['string', 'exists:tags,id,type,skill'],
            'equipment' => 'nullable|array',
            'equipment.*' => ['string', 'exists:tags,id,type,equipment'],
            'specialties' => 'nullable|array',
            'specialties.*' => ['string', 'exists:tags,id,type,specialty'],
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
        'skills.*.exists' => 'Invalid skill selected.',
        'equipment.*.exists' => 'Invalid equipment selected.',
        'specialties.*.exists' => 'Invalid specialty selected.',
    ];

    public function mount()
    {
        // Get the authenticated user and make sure we refresh from the database
        $user = User::find(auth()->id());

        // If no user is found, log error and bail
        if (!$user) {
            Log::error('UserProfileEdit mount() - No authenticated user found');
            return redirect()->route('login');
        }

        // Log authentication info
        Log::info('UserProfileEdit mount() - User authentication:', [
            'auth_id' => auth()->id(),
            'user_id' => $user->id,
            'username' => $user->username
        ]);

        $this->name = $user->name;
        $this->email = $user->email;
        $this->username = $user->username;
        $this->headline = $user->headline;
        $this->bio = $user->bio;
        $this->location = $user->location;
        $this->website = $user->website;
        $this->tipjar_link = $user->tipjar_link;
        $this->social_links = $user->social_links ?? [];
        $this->is_username_locked = $user->is_username_locked;
        $this->profile_completed = $user->profile_completed ?? false;

        // DEBUG: Log the current user ID
        Log::info('UserProfileEdit - mount() for user:', [
            'user_id' => $user->id,
            'username' => $user->username
        ]);

        // Load all available tags, grouped by type
        $this->allTags = Tag::all()->groupBy('type');
        Log::info('UserProfileEdit - All Tags Loaded: ', $this->allTags->toArray());

        // DEBUG: Log the raw user->tags relationship
        Log::info('UserProfileEdit - Raw user tags relationship:', [
            'count' => $user->tags()->count(),
            'raw_tags' => $user->tags()->get()->toArray()
        ]);

        // Load user's current tags and set the ID arrays
        $userTags = $user->tags()->get()->groupBy('type');

        // DEBUG: Log the user tags grouped by type
        Log::info('UserProfileEdit - User tags grouped by type:', [
            'userTags' => $userTags->toArray(),
            'skill_count' => $userTags->get('skill', collect())->count(),
            'equipment_count' => $userTags->get('equipment', collect())->count(),
            'specialty_count' => $userTags->get('specialty', collect())->count()
        ]);

        // Convert IDs to strings to ensure proper JSON encoding
        $this->skills = $userTags->get('skill', collect())->pluck('id')->map(function ($id) {
            return (string) $id;
        })->toArray();

        $this->equipment = $userTags->get('equipment', collect())->pluck('id')->map(function ($id) {
            return (string) $id;
        })->toArray();

        $this->specialties = $userTags->get('specialty', collect())->pluck('id')->map(function ($id) {
            return (string) $id;
        })->toArray();

        // DEBUG: Log the final arrays being passed to the view
        Log::info('UserProfileEdit - Final tag arrays for view:', [
            'skills' => $this->skills,
            'equipment' => $this->equipment,
            'specialties' => $this->specialties
        ]);

        $this->calculateProfileCompletion();
    }

    private function calculateProfileCompletion()
    {
        $requiredFields = ['name', 'email', 'username'];
        $optionalFields = ['headline', 'bio', 'location', 'website'];
        $arrayFields = ['skills', 'equipment', 'specialties', 'social_links'];

        $completedRequired = 0;
        foreach ($requiredFields as $field) {
            if (!empty($this->$field)) {
                $completedRequired++;
            }
        }

        $completedOptional = 0;
        foreach ($optionalFields as $field) {
            if (!empty($this->$field)) {
                $completedOptional++;
            }
        }

        $completedArrays = 0;
        foreach ($arrayFields as $field) {
            if ($field === 'social_links' && is_array($this->$field) && count(array_filter($this->$field)) > 0) {
                $completedArrays++;
            } elseif (in_array($field, ['skills', 'equipment', 'specialties']) && is_array($this->$field) && count($this->$field) > 0) {
                $completedArrays++;
            }
        }

        $totalCompleted = $completedRequired + $completedOptional + $completedArrays;
        $totalPossible = count($requiredFields) + count($optionalFields) + count($arrayFields);

        $this->profile_completion_percentage = ($totalPossible > 0) ? intval(($totalCompleted / $totalPossible) * 100) : 0;

        $this->profile_completed = $this->profile_completion_percentage >= 70;
    }

    public function updatedProfilePhoto()
    {
        $this->validate([
            'profilePhoto' => 'image|max:1024',
        ]);
    }

    public function updatedSkills($value)
    {
        if (is_array($value)) {
            $this->skills = array_map('strval', $value);
            Log::info('Skills updated to strings:', ['skills' => $this->skills]);
        }
    }

    public function updatedEquipment($value)
    {
        if (is_array($value)) {
            $this->equipment = array_map('strval', $value);
            Log::info('Equipment updated to strings:', ['equipment' => $this->equipment]);
        }
    }

    public function updatedSpecialties($value)
    {
        if (is_array($value)) {
            $this->specialties = array_map('strval', $value);
            Log::info('Specialties updated to strings:', ['specialties' => $this->specialties]);
        }
    }

    public function save()
    {
        $validatedData = $this->validate();
        $user = auth()->user();

        // Log the tag data before saving
        Log::info('UserProfileEdit save() - Tag data before saving:', [
            'user_id' => $user->id,
            'username' => $user->username,
            'skills' => $this->skills,
            'skills_type' => gettype($this->skills),
            'equipment' => $this->equipment,
            'equipment_type' => gettype($this->equipment),
            'specialties' => $this->specialties,
            'specialties_type' => gettype($this->specialties)
        ]);

        $userData = [
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'headline' => $this->headline,
            'bio' => $this->bio,
            'location' => $this->location,
            'website' => $this->website,
            'tipjar_link' => $this->tipjar_link,
            'social_links' => array_filter($this->social_links ?? []),
            'is_username_locked' => $user->is_username_locked || !empty($this->username),
        ];

        if (!empty($userData['website']) && !preg_match("~^(?:f|ht)tps?://~i", $userData['website'])) {
            $userData['website'] = "https://" . $userData['website'];
        }

        if (!empty($userData['tipjar_link']) && !preg_match("~^(?:f|ht)tps?://~i", $userData['tipjar_link'])) {
            $userData['tipjar_link'] = "https://" . $userData['tipjar_link'];
        }

        $this->calculateProfileCompletion();
        $userData['profile_completed'] = $this->profile_completed;

        DB::transaction(function () use ($user, $userData) {
            try {
                if ($this->profilePhoto) {
                    $user->updateProfilePhoto($this->profilePhoto);
                    $this->profilePhoto = null;
                }

                $user->fill($userData);
                $user->save();

                // Ensure all tag arrays are properly initialized 
                $skills = is_array($this->skills) ? $this->skills : [];
                $equipment = is_array($this->equipment) ? $this->equipment : [];
                $specialties = is_array($this->specialties) ? $this->specialties : [];

                // Log pre-conversion values
                Log::info('UserProfileEdit save() - Pre-conversion tag values:', [
                    'skills' => $skills,
                    'equipment' => $equipment,
                    'specialties' => $specialties
                ]);

                // Make sure we have integer values for database operations
                $skillIds = array_map('intval', $skills);
                $equipmentIds = array_map('intval', $equipment);
                $specialtyIds = array_map('intval', $specialties);

                $allTagIds = array_merge($skillIds, $equipmentIds, $specialtyIds);

                // Log the merged tag IDs being synced
                Log::info('UserProfileEdit save() - Syncing tags:', [
                    'allTagIds' => $allTagIds,
                    'count' => count($allTagIds)
                ]);

                // Log before and after counts for comparison
                $beforeCount = $user->tags()->count();
                $beforeTags = $user->tags()->pluck('id')->toArray();

                // DEBUG: Verify user ID and relationship query
                Log::info('UserProfileEdit save() - About to sync tags with user:', [
                    'user_id' => $user->id,
                    'relationship_sql' => $user->tags()->toSql()
                ]);

                // Sync tags with the user
                if (!empty($allTagIds)) {
                    $user->tags()->sync($allTagIds);
                    Log::info('UserProfileEdit save() - Tags synced successfully');

                    $afterCount = $user->tags()->count();
                    $afterTags = $user->tags()->pluck('id')->toArray();

                    Log::info('UserProfileEdit save() - Tags synced:', [
                        'beforeCount' => $beforeCount,
                        'afterCount' => $afterCount,
                        'beforeTags' => $beforeTags,
                        'afterTags' => $afterTags
                    ]);
                } else {
                    Log::warning('UserProfileEdit save() - No tag IDs to sync!');
                }

                Toaster::success('Profile updated successfully!');
            } catch (\Exception $e) {
                Log::error('Error updating user profile: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                Toaster::error('An error occurred while updating your profile. Please try again.');
                throw $e;
            }
        });
    }

    public function render()
    {
        Log::info('UserProfileEdit render() - Tags data: ', [
            'allTags_count' => $this->allTags->count(),
            'skills_count' => count($this->skills),
            'equipment_count' => count($this->equipment),
            'specialties_count' => count($this->specialties),
        ]);

        return view('livewire.user-profile-edit', [
            'allTags' => $this->allTags,
            'skills' => $this->skills,
            'equipment' => $this->equipment,
            'specialties' => $this->specialties
        ]);
    }

    // Debug helper method
    public function debugTags()
    {
        Log::info('UserProfileEdit debugTags() - Current tag data:', [
            'skills' => $this->skills,
            'equipment' => $this->equipment,
            'specialties' => $this->specialties,
            'types' => [
                'skills_type' => gettype($this->skills),
                'equipment_type' => gettype($this->equipment),
                'specialties_type' => gettype($this->specialties),
            ]
        ]);

        // Output to toaster for UI visibility
        Toaster::info('Tag data logged. Check server logs.');
    }

    // Debug method to test tag selection
    public function selectTags()
    {
        // Force-set specific tag IDs 
        $this->skills = ['1']; // Mixing
        $this->equipment = ['2']; // Ableton
        $this->specialties = ['3']; // Rock

        Log::info('UserProfileEdit - Force set tag IDs:', [
            'skills' => $this->skills,
            'equipment' => $this->equipment,
            'specialties' => $this->specialties
        ]);

        Toaster::info('Tags have been force-selected. Check the dropdowns now.');
    }
}
