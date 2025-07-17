<?php

namespace App\Policies;

use App\Models\ServicePackage;
use App\Models\User;

class ServicePackagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Any authenticated user can view the list (controller will filter to own packages)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServicePackage $servicePackage): bool
    {
        // User can view their own service package
        return $user->id === $servicePackage->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Any authenticated user (presumably producers) can create packages
        // Add role check later if needed: return $user->hasRole('producer');
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServicePackage $servicePackage): bool
    {
        // User can update their own service package
        return $user->id === $servicePackage->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServicePackage $servicePackage): bool
    {
        // User can delete their own service package
        return $user->id === $servicePackage->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ServicePackage $servicePackage): bool
    {
        // User can restore their own soft-deleted package
        return $user->id === $servicePackage->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ServicePackage $servicePackage): bool
    {
        // Generally restrict force deletion, or allow only for owners
        return $user->id === $servicePackage->user_id;
    }
}
