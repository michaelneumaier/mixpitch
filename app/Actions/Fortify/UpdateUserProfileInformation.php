<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  User $user
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
            'skillTags' => ['nullable', 'array'],
            'skillTags.*' => ['integer', 'exists:tags,id'],
            'equipmentTags' => ['nullable', 'array'],
            'equipmentTags.*' => ['integer', 'exists:tags,id'],
            'specialtyTags' => ['nullable', 'array'],
            'specialtyTags.*' => ['integer', 'exists:tags,id'],
            'username' => ['required', 'alpha_dash', 'min:3', 'max:30', Rule::unique('users')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:1000'],
            'headline' => ['nullable', 'string', 'max:160'],
            'website' => ['nullable', 'string', 'max:255', 'url'],
            'location' => ['nullable', 'string', 'max:100'],
            'social_links' => ['nullable', 'array'],
            'tipjar_link' => ['nullable', 'string', 'max:255', 'url'],
        ])->validateWithBag('updateProfileInformation');

        if (isset($input['photo'])) {
            $user->updateProfilePhoto($input['photo']);
        }

        $userData = [
            'name' => $input['name'],
            'email' => $input['email'],
            'username' => $input['username'],
            'bio' => $input['bio'] ?? null,
            'headline' => $input['headline'] ?? null,
            'website' => $input['website'] ?? null,
            'location' => $input['location'] ?? null,
            'social_links' => $input['social_links'] ?? [],
            'tipjar_link' => $input['tipjar_link'] ?? null,
        ];
        
        $needsVerification = $input['email'] !== $user->email && $user instanceof MustVerifyEmail;
        if ($needsVerification) {
            $userData['email_verified_at'] = null;
        }
        
        DB::transaction(function () use ($user, $userData, $input, $needsVerification) {
            Log::info('Updating user profile inside transaction', ['user_id' => $user->id, 'data' => $userData]);
            $user->forceFill($userData)->save();

            Log::info('Syncing tags', [
                'skillTags' => $input['skillTags'] ?? [],
                'equipmentTags' => $input['equipmentTags'] ?? [],
                'specialtyTags' => $input['specialtyTags'] ?? [],
            ]);
            $allTagIds = array_merge(
                $input['skillTags'] ?? [],
                $input['equipmentTags'] ?? [],
                $input['specialtyTags'] ?? []
            );
            $user->tags()->sync($allTagIds); 

            if ($needsVerification) {
                $user->sendEmailVerificationNotification();
            }
        });
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  User $user
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        // This method seems redundant now as the logic is handled within the transaction in update()
        // Kept for compatibility but the core logic is moved.
        // $user->forceFill([
        //     'name' => $input['name'],
        //     'email' => $input['email'],
        //     'email_verified_at' => null,
        // ])->save();

        // $user->sendEmailVerificationNotification();
    }
}
