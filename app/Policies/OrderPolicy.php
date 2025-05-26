<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Any authenticated user can potentially see their list of orders
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        // Allow the client or the producer to view the order
        return $user->id === $order->client_user_id || $user->id === $order->producer_user_id;
    }

    /**
     * Determine whether the user can create models.
     * Note: Order creation is handled via purchasing a ServicePackage, not direct creation.
     */
    public function create(User $user): bool
    {
        return false; // Orders are created through the ServicePackage purchase flow
    }

    /**
     * Determine whether the user can update the model.
     * Specific actions (e.g., submit requirements, deliver) will have their own policy methods.
     */
    public function update(User $user, Order $order): bool
    {
        // General updates might be restricted, use specific action policies
        return false; 
    }

    /**
     * Determine whether the user can delete the model.
     * Cancellation should likely have its own policy method/logic.
     */
    public function delete(User $user, Order $order): bool
    {
        return false; // Prevent direct deletion, use cancellation flow
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
        // Generally disallow restoring orders
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        // Generally disallow force deleting orders
        return false;
    }

    /**
     * Determine whether the user can submit requirements for the order.
     */
    public function submitRequirements(User $user, Order $order): bool
    {
        // Only the client can submit requirements, and only when pending
        return $user->id === $order->client_user_id && $order->status === Order::STATUS_PENDING_REQUIREMENTS;
    }

    /**
     * Determine whether the user can deliver the order.
     */
    public function deliverOrder(User $user, Order $order): bool
    {
        // Only the producer can deliver, and only when in progress or revisions requested
        return $user->id === $order->producer_user_id && 
               in_array($order->status, [Order::STATUS_IN_PROGRESS, Order::STATUS_REVISIONS_REQUESTED]);
    }

    /**
     * Determine whether the user can accept the delivery for the order.
     */
    public function acceptDelivery(User $user, Order $order): bool
    {
        // Only the client can accept delivery, and only when it's ready for review
        return $user->id === $order->client_user_id && 
               $order->status === Order::STATUS_READY_FOR_REVIEW;
    }

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancelOrder(User $user, Order $order): bool
    {
        // Client or Producer can cancel
        if ($user->id !== $order->client_user_id && $user->id !== $order->producer_user_id) {
            return false;
        }

        // Cannot cancel if already completed or cancelled
        return !in_array($order->status, [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED]);
    }

    /**
     * Determine whether the user can post a message to the order.
     */
    public function postMessage(User $user, Order $order): bool
    {
        // Client or Producer can post messages
        if ($user->id !== $order->client_user_id && $user->id !== $order->producer_user_id) {
            return false;
        }

        // Cannot post if completed or cancelled
        return !in_array($order->status, [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED]);
    }

    /**
     * Determine whether the user can request revisions for the order.
     */
    public function requestRevision(User $user, Order $order): bool
    {
        // Only the client can request revisions
        if ($user->id !== $order->client_user_id) {
            return false;
        }

        // Only possible when ready for review
        if ($order->status !== Order::STATUS_READY_FOR_REVIEW) {
            return false;
        }

        // Check if revisions are available based on package limits
        $revisionsAllowed = $order->servicePackage->revisions_included ?? 0;
        return $order->revision_count < $revisionsAllowed;
    }

    // TODO: Add policy methods for specific order actions like:
    // - uploadFile(User $user, Order $order)
}
