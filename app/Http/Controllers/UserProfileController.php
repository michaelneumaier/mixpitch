<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UserProfileController extends Controller
{
    /**
     * Display the user's public profile
     *
     * @param string $username
     * @return \Illuminate\View\View
     */
    public function show($username)
    {
        // Remove the @ symbol if it's at the beginning of the username
        $username = ltrim($username, '@');

        // Find the user with the given username
        $user = User::where('username', $username)->firstOrFail();

        // Fetch user's projects
        $projects = Project::where('user_id', $user->id)
            ->where('is_published', true)
            ->latest()
            ->take(5)
            ->get();

        // Fetch completed pitches that are either:
        // 1. Submitted by this user and have been completed
        // 2. For projects owned by this user and have been completed
        $completedPitches = Pitch::where('status', Pitch::STATUS_COMPLETED)
            ->where(function($query) use ($user) {
                $query->where('user_id', $user->id) // Pitches submitted by the user
                      ->orWhereHas('project', function($subQuery) use ($user) {
                          $subQuery->where('user_id', $user->id); // Pitches for projects owned by the user
                      });
            })
            ->with(['project', 'project.user', 'user'])
            ->latest()
            ->take(5)
            ->get();

        // Check if the logged-in user can edit this profile
        $canEdit = false;
        if (Auth::check()) {
            $canEdit = Auth::id() === $user->id;
        }

        // Get the portfolio layout preference
        $layout = $user->portfolio_layout ?? 'standard';

        return view('user-profile.show', [
            'user' => $user,
            'projects' => $projects,
            'completedPitches' => $completedPitches,
            'canEdit' => $canEdit,
            'layout' => $layout
        ]);
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'username';
    }

    /**
     * Display the form for editing the user's profile
     *
     * @return \Illuminate\View\View
     */
    public function edit()
    {
        return view('user-profile.edit-livewire', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update the user's profile
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        // Username validation rules vary depending on whether username is already locked
        $usernameRules = 'required|alpha_dash|min:3|max:30|unique:users,username,' . $user->id;

        $validated = $request->validate([
            'username' => $usernameRules,
            'bio' => 'nullable|string|max:1000',
            'headline' => 'nullable|string|max:160',
            'website' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:100',
            'social_links' => 'nullable|array',
            'social_links.twitter' => 'nullable|string|max:255',
            'social_links.instagram' => 'nullable|string|max:255',
            'social_links.facebook' => 'nullable|string|max:255',
            'social_links.soundcloud' => 'nullable|string|max:255',
            'social_links.spotify' => 'nullable|string|max:255',
            'social_links.youtube' => 'nullable|string|max:255',
            'skills' => 'nullable|array',
            'skills.*' => 'nullable|string|max:100',
            'equipment' => 'nullable|array',
            'equipment.*' => 'nullable|string|max:255',
            'specialties' => 'nullable|array',
            'specialties.*' => 'nullable|string|max:255',
            'featured_work' => 'nullable|string',
            'portfolio_layout' => 'nullable|string|in:standard,grid,timeline',
        ]);

        try {
            // Check if username is being changed and was previously locked
            if ($user->username_locked && $user->username !== $validated['username']) {
                return redirect()->back()->with('error', 'Your username has been locked and cannot be changed.');
            }

            // Format website URL if provided
            if (!empty($validated['website'])) {
                // Add http:// prefix if not present
                if (!preg_match("~^(?:f|ht)tps?://~i", $validated['website'])) {
                    $validated['website'] = "https://" . $validated['website'];
                }
            }

            // Format social media links
            if (!empty($validated['social_links'])) {
                $socialLinks = [];

                // Twitter
                if (!empty($validated['social_links']['twitter'])) {
                    $username = $this->extractUsername($validated['social_links']['twitter'], 'twitter');
                    $socialLinks['twitter'] = $username;
                }

                // Instagram
                if (!empty($validated['social_links']['instagram'])) {
                    $username = $this->extractUsername($validated['social_links']['instagram'], 'instagram');
                    $socialLinks['instagram'] = $username;
                }

                // Facebook
                if (!empty($validated['social_links']['facebook'])) {
                    $username = $this->extractUsername($validated['social_links']['facebook'], 'facebook');
                    $socialLinks['facebook'] = $username;
                }

                // SoundCloud
                if (!empty($validated['social_links']['soundcloud'])) {
                    $username = $this->extractUsername($validated['social_links']['soundcloud'], 'soundcloud');
                    $socialLinks['soundcloud'] = $username;
                }

                // Spotify
                if (!empty($validated['social_links']['spotify'])) {
                    $username = $this->extractUsername($validated['social_links']['spotify'], 'spotify');
                    $socialLinks['spotify'] = $username;
                }

                // YouTube
                if (!empty($validated['social_links']['youtube'])) {
                    $username = $this->extractUsername($validated['social_links']['youtube'], 'youtube');
                    $socialLinks['youtube'] = $username;
                }

                $validated['social_links'] = $socialLinks;
            }

            // If user has set a username for the first time, lock it after saving
            $lockUsername = false;
            if (!$user->username_locked && !empty($validated['username'])) {
                $lockUsername = true;
            }

            // Check if the profile can be considered complete
            $profileComplete = !empty($validated['username']) &&
                !empty($validated['bio']) &&
                (
                    !empty($validated['skills']) ||
                    !empty($validated['specialties']) ||
                    !empty($validated['equipment'])
                );

            // Update the user profile
            $user->fill($validated);

            // Lock username if needed
            if ($lockUsername) {
                $user->username_locked = true;
            }

            // Set profile completion status
            $user->profile_completed = $profileComplete;

            $user->save();

            return redirect()->back()->with('success', 'Profile updated successfully.' . ($lockUsername ? ' Your username has been locked and cannot be changed in the future.' : ''));
        } catch (\Exception $e) {
            \Log::error('Error updating user profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'There was a problem updating your profile. Please try again.');
        }
    }

    /**
     * Extract username from social media URL or handle
     *
     * @param string $input
     * @param string $platform
     * @return string
     */
    private function extractUsername($input, $platform)
    {
        // Remove @ symbol if present
        $input = ltrim($input, '@');

        // Extract username from URL if it's a URL
        if (filter_var($input, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($input);

            if (!isset($parsedUrl['host'])) {
                return $input;
            }

            $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
            $path = trim($path, '/');
            $pathParts = explode('/', $path);

            switch ($platform) {
                case 'twitter':
                case 'instagram':
                case 'facebook':
                case 'soundcloud':
                    return !empty($pathParts[0]) ? $pathParts[0] : $input;

                case 'spotify':
                    // For Spotify, we want the ID after /artist/
                    if (count($pathParts) >= 2 && $pathParts[0] === 'artist') {
                        return $pathParts[1];
                    }
                    return $input;

                case 'youtube':
                    // For YouTube, check for channel or user format
                    if (count($pathParts) >= 2 && ($pathParts[0] === 'c' || $pathParts[0] === 'channel' || $pathParts[0] === 'user')) {
                        return $pathParts[1];
                    }
                    return $input;

                default:
                    return $input;
            }
        }

        return $input;
    }
}
