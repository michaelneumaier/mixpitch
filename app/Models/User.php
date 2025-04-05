<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Laravel\Cashier\Billable;
use App\Models\Pitch;
use App\Models\PitchEvent;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use Billable;

    /**
     * Define user roles as constants.
     * This makes role checks consistent across the application.
     */
    const ROLE_CLIENT = 'client';
    const ROLE_PRODUCER = 'producer';
    const ROLE_ADMIN = 'admin'; // Assuming you might need an admin role too

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'bio',
        'website',
        'tipjar_link',
        'location',
        'social_links',
        'username_locked',
        'skills',
        'equipment',
        'specialties',
        'featured_work',
        'headline',
        'portfolio_layout', 
        'profile_completed',
        'provider',
        'provider_id',
        'provider_token',
        'provider_refresh_token',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'skills' => 'array',
        'equipment' => 'array',
        'specialties' => 'array',
        'social_links' => 'array',
        'is_username_locked' => 'boolean',
        'profile_completed' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Update the user's profile photo.
     * This method overrides the one from HasProfilePhoto to fix S3 storage issues.
     *
     * @param  \Illuminate\Http\UploadedFile  $photo
     * @return void
     */
    public function updateProfilePhoto(UploadedFile $photo, $storagePath = 'profile-photos')
    {
        $disk = $this->profilePhotoDisk();

        // Delete the previous photo if one exists
        if ($this->profile_photo_path) {
            try {
                Storage::disk($disk)->delete($this->profile_photo_path);
            } catch (\Exception $e) {
                \Log::warning('Failed to delete old profile photo: ' . $e->getMessage(), [
                    'user_id' => $this->id,
                    'path' => $this->profile_photo_path
                ]);
            }
        }

        // Generate a unique name for the photo
        $fileName = $storagePath . '/' . Str::uuid() . '.' . $photo->getClientOriginalExtension();

        // Special handling for Livewire temporary uploads
        if (method_exists($photo, 'getRealPath') && 
            ($photo->getPath() !== '' || $photo->getRealPath() !== '') && 
            file_exists($photo->getRealPath())) {
            // This is a real uploaded file, not just a path reference
            try {
                // Store the file directly to S3 in the profile-photos directory
                $stream = fopen($photo->getRealPath(), 'r');
                Storage::disk($disk)->put($fileName, $stream, [
                    'visibility' => 'public'
                ]);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            } catch (\Exception $e) {
                \Log::error('Error uploading profile photo to S3', [
                    'user_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                throw $e; // Re-throw to handle in the component
            }
        } else {
            // This might be a Livewire temp path reference
            $tempPath = null;
            
            // Check if this is a string path to a temp file in livewire-tmp
            if (is_string($photo->getPathname()) && Str::contains($photo->getPathname(), 'livewire-tmp')) {
                $tempPath = $photo->getPathname();
                
                try {
                    // Copy from the temp S3 location to the final profile-photos location
                    if (Storage::disk($disk)->exists($tempPath)) {
                        Storage::disk($disk)->copy($tempPath, $fileName);
                    } else {
                        \Log::error('Livewire temp file not found on S3', [
                            'path' => $tempPath
                        ]);
                        throw new \Exception('Temporary file not found');
                    }
                } catch (\Exception $e) {
                    \Log::error('Error copying S3 temp file to profile-photos', [
                        'user_id' => $this->id,
                        'error' => $e->getMessage(),
                        'tempPath' => $tempPath,
                        'finalPath' => $fileName
                    ]);
                    throw $e; // Re-throw to handle in the component
                }
            } else {
                // Fallback to standard upload in case it's not working the way we expect
                try {
                    $fileName = $photo->storePubliclyAs($storagePath, Str::uuid() . '.' . $photo->getClientOriginalExtension(), [
                        'disk' => $disk
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error in fallback profile photo upload', [
                        'user_id' => $this->id,
                        'error' => $e->getMessage()
                    ]);
                    throw $e; // Re-throw to handle in the component
                }
            }
        }

        // Update the user
        $this->forceFill([
            'profile_photo_path' => $fileName,
        ])->save();
        
        \Log::info('Profile photo updated successfully', [
            'user_id' => $this->id,
            'path' => $fileName
        ]);
    }

    /**
     * Get the URL to the user's profile photo.
     * This method overrides the one from the HasProfilePhoto trait
     * to ensure proper S3 signed URLs are used.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function profilePhotoUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if (!$this->profile_photo_path) {
                return $this->defaultProfilePhotoUrl();
            }
            
            try {
                // Generate a temporary URL with a 1 hour expiration
                return Storage::disk($this->profilePhotoDisk())->temporaryUrl(
                    $this->profile_photo_path,
                    now()->addHour()
                );
            } catch (\Exception $e) {
                \Log::error('Error getting signed profile photo URL', [
                    'user_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                return $this->defaultProfilePhotoUrl();
            }
        });
    }

    /**
     * Check if user has a specific role.
     *
     * This overrides the temporary hasRole method previously in place.
     * It now directly checks the 'role' column.
     *
     * If you were using Spatie permissions before, you might need to adjust
     * or remove this method depending on your setup.
     *
     * @param string $role The role to check for (e.g., User::ROLE_CLIENT)
     * @return bool
     */
    public function hasRole($role): bool
    {
        // Check if the user's role matches the provided role constant
        return $this->role === $role;

        // If you decide to use Spatie Permissions later, you would replace
        // the above line with something like:
        // return parent::hasRole($role);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function pitches()
    {
        return $this->hasMany(Pitch::class);
    }
    public function mixes()
    {
        return $this->hasMany(Mix::class);
    }

    /**
     * Check if the user has completed their profile setup
     * 
     * @return bool
     */
    public function hasCompletedProfile()
    {
        // Consider a profile complete if the user has set their username and bio
        return !empty($this->username) && !empty($this->bio);
    }

    /**
     * Determine if the user can access the given Filament panel.
     *
     * @param Panel $panel
     * @return bool
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Allow access if the user has the admin role OR if it's the main app panel
        // For tests, handle the case when Panel could be missing an ID
        if ($panel === null) {
            return $this->role === self::ROLE_ADMIN;
        }
        
        // Get panel ID safely
        try {
            $panelId = $panel->getId();
            return $this->role === self::ROLE_ADMIN || $panelId === 'app';
        } catch (\Exception $e) {
            // If there's an issue getting the panel ID, only allow admins
            return $this->role === self::ROLE_ADMIN;
        }
    }

    /**
     * Get the user's name.
     */
    public function getFilamentName(): string
    {
        return $this->name;
    }

    /**
     * Get the user's avatar for Filament.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->profile_photo_url;
    }

    /**
     * Scope a query to only include clients.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClients($query)
    {
        return $query->where('role', self::ROLE_CLIENT);
    }

    /**
     * Scope a query to only include producers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProducers($query)
    {
        return $query->where('role', self::ROLE_PRODUCER);
    }

    /**
     * Calculate the average rating for the user based on completed pitches they created.
     * This shows the ratings a user received for their submitted work, regardless of role.
     *
     * @return array{average: float|null, count: int}
     */
    public function calculateAverageRating(): array
    {
        // Get IDs of completed pitches created by this user
        $completedPitchIds = $this->pitches()
            ->where('status', Pitch::STATUS_COMPLETED)
            ->pluck('id');

        \Log::debug('User: ' . $this->id . ' (' . $this->name . ') - Completed pitch IDs:', $completedPitchIds->toArray());
        
        if ($completedPitchIds->isEmpty()) {
            \Log::debug('User: ' . $this->id . ' - No completed pitches found');
            return ['average' => null, 'count' => 0];
        }

        // Get the ratings from the completion events for those pitches
        $ratings = PitchEvent::whereIn('pitch_id', $completedPitchIds)
            ->where('event_type', 'status_change')
            ->where('status', Pitch::STATUS_COMPLETED)
            ->whereNotNull('rating')
            ->get(['id', 'pitch_id', 'rating', 'created_at']);
            
        \Log::debug('User: ' . $this->id . ' - Ratings found:', $ratings->toArray());

        if ($ratings->isEmpty()) {
            \Log::debug('User: ' . $this->id . ' - No ratings found for completed pitches');
            return ['average' => null, 'count' => 0];
        }

        $average = $ratings->avg('rating');
        $count = $ratings->count();

        \Log::debug('User: ' . $this->id . ' - Calculated ratings:', [
            'average' => $average,
            'count' => $count
        ]);

        return [
            'average' => round($average, 1), // Round to one decimal place
            'count' => $count
        ];
    }
}
