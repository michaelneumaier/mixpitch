<?php

namespace App\Policies;

use App\Models\PortfolioItem;
use App\Models\User;

class PortfolioItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Generally, users might see lists of items on profiles, so allow.
        // Specific filtering for public items should happen in the controller/component.
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PortfolioItem $portfolioItem): bool
    {
        // Allow viewing if the item is public or if the user owns it.
        return $portfolioItem->is_public || $user->id === $portfolioItem->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Allow all authenticated users to create portfolio items
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PortfolioItem $portfolioItem): bool
    {
        // Only the owner can update their portfolio item.
        return $user->id === $portfolioItem->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PortfolioItem $portfolioItem): bool
    {
        // Only the owner can delete their portfolio item.
        return $user->id === $portfolioItem->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PortfolioItem $portfolioItem): bool
    {
        // Only the owner can restore (if using SoftDeletes, which we aren't currently).
        return $user->id === $portfolioItem->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PortfolioItem $portfolioItem): bool
    {
        // Only the owner can force delete.
        return $user->id === $portfolioItem->user_id;
    }
}
