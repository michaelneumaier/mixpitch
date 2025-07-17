<?php

namespace App\Http\Controllers;

use App\Models\Pitch;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserProfileController extends Controller
{
    /**
     * Display the user's public profile
     *
     * @param  string  $username
     * @return \Illuminate\View\View
     */
    public function show($username)
    {
        // Remove the @ symbol if it's at the beginning of the username
        $username = ltrim($username, '@');

        // Find the user with the given username, eager load tags
        $user = User::where('username', $username)->with('tags')->firstOrFail();

        // Fetch user's projects
        $projects = Project::where('user_id', $user->id)
            ->where('is_published', true)
            ->latest()
            ->take(5)
            ->get();

        // Fetch completed pitches that are submitted by this user
        $completedPitches = Pitch::where('status', Pitch::STATUS_COMPLETED)
            ->where('user_id', $user->id) // Only pitches submitted by the user
            ->with(['project', 'project.user', 'user'])
            ->latest()
            ->take(5)
            ->get();

        // Group the user's tags by type for easier access in the view
        $userTagsGrouped = $user->tags->groupBy('type');

        // Check if the logged-in user can edit this profile
        $canEdit = false;
        if (Auth::check()) {
            $canEdit = Auth::id() === $user->id;
        }

        // Calculate average rating for all users
        $ratingData = $user->calculateAverageRating();

        // Get the portfolio layout preference
        $layout = $user->portfolio_layout ?? 'standard';

        // Fetch portfolio items for the user
        $portfolioItems = $user->portfolioItems()
            ->where('is_public', true)
            ->orderBy('display_order')
            ->get();

        // TODO: Fetch published service packages for the user (when servicePackages relationship is implemented)
        // $servicePackages = $user->servicePackages()
        //     ->published()
        //     ->with('user')
        //     ->orderBy('created_at', 'desc')
        //     ->get();
        $servicePackages = collect(); // Empty collection for now

        return view('user-profile.show', [
            'user' => $user,
            'projects' => $projects,
            'completedPitches' => $completedPitches,
            'canEdit' => $canEdit,
            'ratingData' => $ratingData,
            'layout' => $layout,
            'portfolioItems' => $portfolioItems,
            'userTagsGrouped' => $userTagsGrouped,
            'servicePackages' => $servicePackages,
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        // Username validation rules vary depending on whether username is already locked
        $usernameRules = 'required|alpha_dash|min:3|max:30|unique:users,username,'.$user->id;

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
            'skills.*' => 'nullable|exists:tags,id,type,skill',
            'equipment' => 'nullable|array',
            'equipment.*' => 'nullable|exists:tags,id,type,equipment',
            'specialties' => 'nullable|array',
            'specialties.*' => 'nullable|exists:tags,id,type,specialty',
            'featured_work' => 'nullable|string',
            'portfolio_layout' => 'nullable|string|in:standard,grid,timeline',
        ]);

        try {
            // Check if username is being changed and was previously locked
            if ($user->username_locked && $user->username !== $validated['username']) {
                return redirect()->back()->with('error', 'Your username has been locked and cannot be changed.');
            }

            // Format website URL if provided
            if (! empty($validated['website'])) {
                // Add http:// prefix if not present
                if (! preg_match('~^(?:f|ht)tps?://~i', $validated['website'])) {
                    $validated['website'] = 'https://'.$validated['website'];
                }
            }

            // Format social media links
            if (! empty($validated['social_links'])) {
                $socialLinks = [];

                // Twitter
                if (! empty($validated['social_links']['twitter'])) {
                    $username = $this->extractUsername($validated['social_links']['twitter'], 'twitter');
                    $socialLinks['twitter'] = $username;
                }

                // Instagram
                if (! empty($validated['social_links']['instagram'])) {
                    $username = $this->extractUsername($validated['social_links']['instagram'], 'instagram');
                    $socialLinks['instagram'] = $username;
                }

                // Facebook
                if (! empty($validated['social_links']['facebook'])) {
                    $username = $this->extractUsername($validated['social_links']['facebook'], 'facebook');
                    $socialLinks['facebook'] = $username;
                }

                // SoundCloud
                if (! empty($validated['social_links']['soundcloud'])) {
                    $username = $this->extractUsername($validated['social_links']['soundcloud'], 'soundcloud');
                    $socialLinks['soundcloud'] = $username;
                }

                // Spotify
                if (! empty($validated['social_links']['spotify'])) {
                    $username = $this->extractUsername($validated['social_links']['spotify'], 'spotify');
                    $socialLinks['spotify'] = $username;
                }

                // YouTube
                if (! empty($validated['social_links']['youtube'])) {
                    $username = $this->extractUsername($validated['social_links']['youtube'], 'youtube');
                    $socialLinks['youtube'] = $username;
                }

                $validated['social_links'] = $socialLinks;
            }

            // If user has set a username for the first time, lock it after saving
            $lockUsername = false;
            if (! $user->username_locked && ! empty($validated['username'])) {
                $lockUsername = true;
            }

            // Get the tag data
            $skillIds = isset($validated['skills']) ? array_filter($validated['skills']) : [];
            $equipmentIds = isset($validated['equipment']) ? array_filter($validated['equipment']) : [];
            $specialtyIds = isset($validated['specialties']) ? array_filter($validated['specialties']) : [];

            // Merge all tag IDs for syncing
            $allTagIds = array_merge(
                array_map('intval', $skillIds),
                array_map('intval', $equipmentIds),
                array_map('intval', $specialtyIds)
            );

            // Remove tag data from the validated array (as we'll update them separately)
            unset($validated['skills']);
            unset($validated['equipment']);
            unset($validated['specialties']);

            // Check if the profile can be considered complete
            $profileComplete = ! empty($validated['username']) &&
                ! empty($validated['bio']) &&
                (
                    ! empty($skillIds) ||
                    ! empty($equipmentIds) ||
                    ! empty($specialtyIds)
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

            // Sync the tags using the polymorphic relationship
            $user->tags()->sync($allTagIds);

            return redirect()->back()->with('success', 'Profile updated successfully.'.($lockUsername ? ' Your username has been locked and cannot be changed in the future.' : ''));
        } catch (\Exception $e) {
            \Log::error('Error updating user profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'There was a problem updating your profile. Please try again.');
        }
    }

    /**
     * Extract username from social media URL or handle
     *
     * @param  string  $input
     * @param  string  $platform
     * @return string
     */
    private function extractUsername($input, $platform)
    {
        // Remove @ symbol if present
        $input = ltrim($input, '@');

        // Extract username from URL if it's a URL
        if (filter_var($input, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($input);

            if (! isset($parsedUrl['host'])) {
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
                    return ! empty($pathParts[0]) ? $pathParts[0] : $input;

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
