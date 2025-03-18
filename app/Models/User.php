<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

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
     * Check if user has a specific role
     * 
     * This is a temporary implementation until proper role management is set up.
     * For a more complete implementation, consider using Spatie's Laravel Permission package:
     * https://spatie.be/docs/laravel-permission/
     * 
     * @param string $role The role to check for
     * @return bool
     */
    public function hasRole($role)
    {
        // TEMPORARY IMPLEMENTATION
        // For now, we're defining the admin user as either:
        // 1. The user with ID 1 (typically the first user)
        // 2. A user with a specific email (you should change this to your admin email)
        
        if ($role === 'admin') {
            // Change 'admin@example.com' to your actual admin email
            return $this->id === 1 || $this->email === 'admin@example.com';
        }
        
        return false;
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
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // For now, using the existing hasRole method as a bridge
        // Later this should be updated to use the proper Spatie permissions
        return $this->hasRole('admin') || $this->hasPermissionTo('access_filament');
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
}
