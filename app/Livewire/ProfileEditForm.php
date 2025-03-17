<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProfileEditForm extends Component
{
    public $user;
    public $username;
    public $bio;
    public $website;
    public $location;
    public $social_links = [
        'twitter' => '',
        'instagram' => '',
        'facebook' => '',
        'soundcloud' => '',
        'spotify' => '',
        'youtube' => '',
    ];

    public function mount()
    {
        $this->user = Auth::user();
        $this->username = $this->user->username;
        $this->bio = $this->user->bio;
        $this->website = $this->user->website;
        $this->location = $this->user->location;
        
        // Extract usernames from social links
        if (!empty($this->user->social_links)) {
            foreach ($this->user->social_links as $platform => $url) {
                if (!empty($url)) {
                    $this->social_links[$platform] = $this->getSocialUsername($url, $platform);
                }
            }
        }
    }
    
    /**
     * Extract username from social media URL
     *
     * @param string $url
     * @param string $platform
     * @return string
     */
    public function getSocialUsername($url, $platform)
    {
        if (empty($url)) {
            return '';
        }
        
        // If it's not a URL, return as is (might be just a username)
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        
        $parsedUrl = parse_url($url);
        
        if (!isset($parsedUrl['host'])) {
            return $url;
        }
        
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $path = trim($path, '/');
        $pathParts = explode('/', $path);
        
        switch ($platform) {
            case 'twitter':
            case 'instagram':
            case 'facebook':
            case 'soundcloud':
                return !empty($pathParts[0]) ? $pathParts[0] : '';
            
            case 'spotify':
                // For Spotify, we want the ID after /artist/
                if (count($pathParts) >= 2 && $pathParts[0] === 'artist') {
                    return $pathParts[1];
                }
                return !empty($pathParts[0]) ? $pathParts[0] : '';
            
            case 'youtube':
                // For YouTube, check for channel or user format
                if (count($pathParts) >= 2 && ($pathParts[0] === 'c' || $pathParts[0] === 'channel' || $pathParts[0] === 'user')) {
                    return $pathParts[1];
                }
                return !empty($pathParts[0]) ? $pathParts[0] : '';
            
            default:
                return !empty($pathParts[0]) ? $pathParts[0] : '';
        }
    }

    public function updateProfile()
    {
        $this->validate([
            'username' => 'required|alpha_dash|min:3|max:30|unique:users,username,' . $this->user->id,
            'bio' => 'nullable|string|max:1000',
            'website' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:100',
            'social_links.twitter' => 'nullable|string|max:255',
            'social_links.instagram' => 'nullable|string|max:255',
            'social_links.facebook' => 'nullable|string|max:255',
            'social_links.soundcloud' => 'nullable|string|max:255',
            'social_links.spotify' => 'nullable|string|max:255',
            'social_links.youtube' => 'nullable|string|max:255',
        ]);
        
        try {
            // Format website URL if provided
            if (!empty($this->website)) {
                // Add http:// prefix if not present
                if (!preg_match("~^(?:f|ht)tps?://~i", $this->website)) {
                    $this->website = "https://" . $this->website;
                }
            }
            
            // Format social media links
            $formattedSocialLinks = [];
            
            // Twitter
            if (!empty($this->social_links['twitter'])) {
                $username = ltrim($this->social_links['twitter'], '@');
                $formattedSocialLinks['twitter'] = "https://twitter.com/" . $username;
            }
            
            // Instagram
            if (!empty($this->social_links['instagram'])) {
                $username = ltrim($this->social_links['instagram'], '@');
                $formattedSocialLinks['instagram'] = "https://instagram.com/" . $username;
            }
            
            // Facebook
            if (!empty($this->social_links['facebook'])) {
                $username = ltrim($this->social_links['facebook'], '@');
                $formattedSocialLinks['facebook'] = "https://facebook.com/" . $username;
            }
            
            // SoundCloud
            if (!empty($this->social_links['soundcloud'])) {
                $username = ltrim($this->social_links['soundcloud'], '@');
                $formattedSocialLinks['soundcloud'] = "https://soundcloud.com/" . $username;
            }
            
            // Spotify
            if (!empty($this->social_links['spotify'])) {
                $username = ltrim($this->social_links['spotify'], '@');
                $formattedSocialLinks['spotify'] = "https://open.spotify.com/artist/" . $username;
            }
            
            // YouTube
            if (!empty($this->social_links['youtube'])) {
                $username = ltrim($this->social_links['youtube'], '@');
                $formattedSocialLinks['youtube'] = "https://youtube.com/c/" . $username;
            }
            
            // Update the user record
            $this->user->update([
                'username' => $this->username,
                'bio' => $this->bio,
                'website' => $this->website,
                'location' => $this->location,
                'social_links' => $formattedSocialLinks,
            ]);
            
            session()->flash('success', 'Profile updated successfully!');
            
        } catch (\Exception $e) {
            Log::error('Error updating user profile', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);
            
            session()->flash('error', 'An error occurred while updating your profile.');
        }
    }

    public function render()
    {
        return view('livewire.profile-edit-form');
    }
}
