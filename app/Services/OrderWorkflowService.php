<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\InvalidStatusTransitionException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Orders\OrderRequirementsSubmitted;
use App\Notifications\Orders\OrderDelivered;
use App\Notifications\Orders\RevisionRequested;
use App\Notifications\Orders\OrderCompleted;
use App\Notifications\Orders\OrderCancelled;
use App\Notifications\Orders\NewOrderMessage;
use App\Models\OrderFile;
// use App\Services\NotificationService; // Uncomment when notifications are implemented

class OrderWorkflowService
{
    // protected $notificationService; // Uncomment when notifications are implemented

    // public function __construct(NotificationService $notificationService)
    // {
    //     $this->notificationService = $notificationService;
    // }

    /**
     * Handles the client submitting requirements for an order.
     *
     * @param Order $order
     * @param User $client
     * @param string $requirementsText
     * @return Order
     * @throws AuthorizationException|InvalidStatusTransitionException
     */
    public function submitRequirements(Order $order, User $client, string $requirementsText): Order
    {
        // Authorization: Ensure the user is the client
        if ($client->id !== $order->client_user_id) {
            throw new AuthorizationException('Only the client can submit requirements for this order.');
        }

        // Validation: Ensure the order is in the correct status
        if ($order->status !== Order::STATUS_PENDING_REQUIREMENTS) {
            throw new InvalidStatusTransitionException(
                $order->status,
                Order::STATUS_IN_PROGRESS,
                'Requirements can only be submitted when the order is pending requirements.'
            );
        }

        return DB::transaction(function () use ($order, $client, $requirementsText) {
            $fromStatus = $order->status;
            $toStatus = Order::STATUS_IN_PROGRESS;

            // Update Order
            $order->requirements_submitted = $requirementsText;
            $order->status = $toStatus;
            $order->save();

            // Create Event
            $order->events()->create([
                'user_id' => $client->id,
                'event_type' => OrderEvent::EVENT_REQUIREMENTS_SUBMITTED,
                'comment' => 'Client submitted order requirements.',
                'status_from' => $fromStatus,
                'status_to' => $toStatus,
                // Optionally store requirements in metadata too, though it's redundant
                // 'metadata' => ['requirements' => $requirementsText] 
            ]);

            // Trigger notification to producer
            try {
                if ($order->producer) {
                    Log::info('Attempting to send OrderRequirementsSubmitted notification', ['order_id' => $order->id, 'producer_id' => $order->producer->id]);
                    Notification::send($order->producer, new OrderRequirementsSubmitted($order));
                } else {
                    Log::warning('Producer relationship not loaded or null when trying to send OrderRequirementsSubmitted notification', ['order_id' => $order->id]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send OrderRequirementsSubmitted notification', [
                    'order_id' => $order->id,
                    'producer_id' => $order->producer_user_id,
                    'error' => $e->getMessage()
                ]);
                // Do not rollback transaction for notification failure
            }

            Log::info('Order requirements submitted.', ['order_id' => $order->id, 'client_id' => $client->id]);

            return $order;
        });
    }

    /**
     * Handles the producer delivering the order.
     *
     * @param Order $order
     * @param User $producer
     * @param array $filesData Array of file metadata [['path' => ..., 'name' => ..., 'mime' => ..., 'size' => ...]]
     * @param string|null $message Optional delivery message
     * @return Order
     * @throws AuthorizationException|InvalidStatusTransitionException
     */
    public function deliverOrder(Order $order, User $producer, array $filesData, ?string $message = null): Order
    {
        // Authorization: Ensure the user is the producer
        if ($producer->id !== $order->producer_user_id) {
            throw new AuthorizationException('Only the producer can deliver this order.');
        }

        // Validation: Ensure the order is in the correct status
        $allowedStatuses = [Order::STATUS_IN_PROGRESS, Order::STATUS_REVISIONS_REQUESTED];
        if (!in_array($order->status, $allowedStatuses)) {
            throw new InvalidStatusTransitionException(
                $order->status,
                Order::STATUS_READY_FOR_REVIEW,
                'Order can only be delivered when it is in progress or revisions are requested.'
            );
        }
        
        // Basic validation for files data
        if (empty($filesData)) {
            throw new \InvalidArgumentException('At least one delivery file is required.');
        }

        return DB::transaction(function () use ($order, $producer, $filesData, $message) {
            $fromStatus = $order->status;
            $toStatus = Order::STATUS_READY_FOR_REVIEW;

            // Update Order
            $order->status = $toStatus;
            $order->delivered_at = now(); // Set delivery timestamp
            $order->save();

            // Create Delivery Event
            $eventComment = 'Producer delivered the order.';
            if ($message) {
                $eventComment .= "\n\nDelivery Message:\n" . $message;
            }
            $orderEvent = $order->events()->create([
                'user_id' => $producer->id,
                'event_type' => OrderEvent::EVENT_DELIVERY_SUBMITTED,
                'comment' => $eventComment,
                'status_from' => $fromStatus,
                'status_to' => $toStatus,
            ]);

            // Create OrderFile records for delivered files
            foreach ($filesData as $fileData) {
                $order->files()->create([
                    'uploader_user_id' => $producer->id,
                    'file_path' => $fileData['path'],
                    'file_name' => $fileData['name'],
                    'mime_type' => $fileData['mime'],
                    'size' => $fileData['size'],
                    'type' => OrderFile::TYPE_DELIVERY,
                ]);
            }

            // Trigger notification to client
            try {
                if ($order->client) {
                    Notification::send($order->client, new OrderDelivered($order, $message));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send OrderDelivered notification', [
                    'order_id' => $order->id,
                    'client_id' => $order->client_user_id,
                    'error' => $e->getMessage()
                ]);
                // Do not rollback transaction for notification failure
            }

            Log::info('Order delivered.', ['order_id' => $order->id, 'producer_id' => $producer->id, 'file_count' => count($filesData)]);

            return $order;
        });
    }

    /**
     * Handles the client requesting revisions for an order.
     *
     * @param Order $order
     * @param User $client
     * @param string $feedback
     * @return Order
     * @throws AuthorizationException|InvalidStatusTransitionException|\LogicException
     */
    public function requestRevision(Order $order, User $client, string $feedback): Order
    {
        // Authorization: Ensure the user is the client
        if ($client->id !== $order->client_user_id) {
            throw new AuthorizationException('Only the client can request revisions for this order.');
        }

        // Validation: Ensure the order is in the correct status
        if ($order->status !== Order::STATUS_READY_FOR_REVIEW) {
            throw new InvalidStatusTransitionException(
                $order->status,
                Order::STATUS_REVISIONS_REQUESTED,
                'Revisions can only be requested when the order is ready for review.'
            );
        }

        // Validation: Check revision limits
        if ($order->revision_count >= $order->servicePackage->revisions_included) {
            throw new \LogicException('No more revisions allowed for this order.'); // Use LogicException as it's a business rule violation
        }
        
        if (empty(trim($feedback))) {
             throw new \InvalidArgumentException('Revision feedback cannot be empty.');
        }

        return DB::transaction(function () use ($order, $client, $feedback) {
            $fromStatus = $order->status;
            $toStatus = Order::STATUS_REVISIONS_REQUESTED;

            // Update Order
            $order->status = $toStatus;
            $order->revision_count += 1;
            // Clear delivered_at? Maybe not necessary, status indicates it needs rework.
            // $order->delivered_at = null; 
            $order->save();

            // Create Revision Request Event
            $order->events()->create([
                'user_id' => $client->id,
                'event_type' => OrderEvent::EVENT_REVISIONS_REQUESTED,
                'comment' => "Client requested revisions.\n\nFeedback:\n" . $feedback,
                'status_from' => $fromStatus,
                'status_to' => $toStatus,
                'metadata' => ['feedback' => $feedback] // Store feedback structurally too
            ]);

            // Trigger notification to producer
            try {
                if ($order->producer) {
                     Notification::send($order->producer, new RevisionRequested($order, $feedback));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send RevisionRequested notification', [
                    'order_id' => $order->id,
                    'producer_id' => $order->producer_user_id,
                    'error' => $e->getMessage()
                ]);
                // Re-throw the exception to ensure the transaction rolls back
                // and the controller handles the failure properly.
                throw $e; 
            }

            Log::info('Order revision requested.', ['order_id' => $order->id, 'client_id' => $client->id]);

            return $order;
        });
    }

    /**
     * Handles the client accepting the final delivery.
     *
     * @param Order $order
     * @param User $client
     * @return Order
     * @throws AuthorizationException|InvalidStatusTransitionException
     */
    public function acceptDelivery(Order $order, User $client): Order
    {
        // Authorization: Ensure the user is the client
        if ($client->id !== $order->client_user_id) {
            throw new AuthorizationException('Only the client can accept the delivery.');
        }

        // Validation: Ensure the order is in the correct status
        if ($order->status !== Order::STATUS_READY_FOR_REVIEW) {
            throw new InvalidStatusTransitionException(
                $order->status,
                Order::STATUS_COMPLETED,
                'Delivery can only be accepted when the order is ready for review.'
            );
        }

        return DB::transaction(function () use ($order, $client) {
            $fromStatus = $order->status;
            $toStatus = Order::STATUS_COMPLETED;

            // Update Order
            $order->status = $toStatus;
            $order->completed_at = now(); // Mark completion time
            // payment_status should already be PAID from initial order
            $order->save();

            // Create Order Completion Event
            $order->events()->create([
                'user_id' => $client->id,
                'event_type' => OrderEvent::EVENT_DELIVERY_ACCEPTED,
                'comment' => 'Client accepted the delivery and completed the order.',
                'status_from' => $fromStatus,
                'status_to' => $toStatus,
            ]);

            // Trigger notification to producer
            try {
                 if ($order->producer) {
                    Notification::send($order->producer, new OrderCompleted($order));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send OrderCompleted notification', [
                    'order_id' => $order->id,
                    'producer_id' => $order->producer_user_id,
                    'error' => $e->getMessage()
                ]);
                // Do not rollback transaction for notification failure
            }
            
            // TODO: Trigger payout process for the producer
            // PayoutService::schedulePayoutForOrder($order);
            
            // TODO: Trigger notification to Admin/Finance?
            // $this->notificationService->notifyAdminOrderCompleted($order);

            Log::info('Order delivery accepted and completed.', ['order_id' => $order->id, 'client_id' => $client->id]);

            return $order;
        });
    }

    /**
     * Handles the cancellation of an order by either client or producer.
     *
     * @param Order $order
     * @param User $canceller The user initiating the cancellation.
     * @param string $reason Cancellation reason provided by the user.
     * @return Order
     * @throws AuthorizationException|InvalidStatusTransitionException
     */
    public function cancelOrder(Order $order, User $canceller, string $reason): Order
    {
        // Authorization: Ensure the user is the client or the producer
        if ($canceller->id !== $order->client_user_id && $canceller->id !== $order->producer_user_id) {
            throw new AuthorizationException('Only the client or producer can cancel this order.');
        }

        // Validation: Ensure the order is not already completed or cancelled
        $forbiddenStatuses = [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED];
        if (in_array($order->status, $forbiddenStatuses)) {
            throw new InvalidStatusTransitionException(
                $order->status,
                Order::STATUS_CANCELLED,
                'Order cannot be cancelled once it is completed or already cancelled.'
            );
        }
        
        if (empty(trim($reason))) {
             throw new \InvalidArgumentException('A reason for cancellation is required.');
        }

        return DB::transaction(function () use ($order, $canceller, $reason) {
            $fromStatus = $order->status;
            $toStatus = Order::STATUS_CANCELLED;
            $wasPendingPayment = ($fromStatus === Order::STATUS_PENDING_PAYMENT);

            // Update Order
            $order->status = $toStatus;
            $order->cancelled_at = now(); // Mark cancellation time
            if ($wasPendingPayment) {
                // If cancelled before payment, mark payment as failed/cancelled too
                 $order->payment_status = Order::PAYMENT_STATUS_CANCELLED;
            }
            $order->save();

            // Create Order Cancellation Event
            $cancellerRole = ($canceller->id === $order->client_user_id) ? 'Client' : 'Producer';
            $order->events()->create([
                'user_id' => $canceller->id,
                'event_type' => OrderEvent::EVENT_ORDER_CANCELLED,
                'comment' => "{$cancellerRole} cancelled the order.\n\nReason: {$reason}",
                'status_from' => $fromStatus,
                'status_to' => $toStatus,
                'metadata' => ['reason' => $reason]
            ]);

            // Trigger notification to the other party
            try {
                $recipient = ($canceller->id === $order->client_user_id) ? $order->producer : $order->client;
                if ($recipient) {
                    Notification::send($recipient, new OrderCancelled($order, $canceller, $reason));
                }
            } catch (\Exception $e) {
                 Log::error('Failed to send OrderCancelled notification', [
                    'order_id' => $order->id,
                    'canceller_id' => $canceller->id,
                    'recipient_id' => $recipient->id ?? null,
                    'error' => $e->getMessage()
                ]);
                // Do not rollback transaction for notification failure
            }
            
            // TODO: If applicable, initiate refund process or notify admin
            // if ($order->payment_status === Order::PAYMENT_STATUS_PAID) {
                 // RefundService::processRefundForCancelledOrder($order) or notifyAdmin
            // }

            Log::info('Order cancelled.', ['order_id' => $order->id, 'canceller_id' => $canceller->id, 'role' => $cancellerRole]);

            return $order;
        });
    }

    /**
     * Posts a message to the order's activity log.
     *
     * @param Order $order
     * @param User $sender The user sending the message.
     * @param string $messageContent The message content.
     * @return OrderEvent The created event.
     * @throws AuthorizationException If the user is not the client or producer.
     * @throws \InvalidArgumentException If the message is empty.
     * @throws InvalidStatusTransitionException If order is completed or cancelled.
     */
    public function postMessage(Order $order, User $sender, string $messageContent): OrderEvent
    {
        // Authorization: Ensure the user is the client or the producer
        if ($sender->id !== $order->client_user_id && $sender->id !== $order->producer_user_id) {
            throw new AuthorizationException('Only the client or producer can post messages to this order.');
        }

        // Prevent posting messages on completed or cancelled orders
        $forbiddenStatuses = [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED];
        if (in_array($order->status, $forbiddenStatuses)) {
            // Using InvalidStatusTransitionException might be slightly misleading here,
            // but it fits the pattern of preventing actions based on status.
            // Alternatively, could use a LogicException.
            throw new InvalidStatusTransitionException(
                $order->status,
                $order->status, // No status change
                'Messages cannot be posted to completed or cancelled orders.'
            );
        }

        // Validation: Ensure message is not empty
        if (empty(trim($messageContent))) {
            throw new \InvalidArgumentException('Message content cannot be empty.');
        }

        // Create Message Event (No transaction needed for just creating an event)
        $senderRole = ($sender->id === $order->client_user_id) ? 'Client' : 'Producer';
        $event = $order->events()->create([
            'user_id' => $sender->id,
            'event_type' => OrderEvent::EVENT_MESSAGE,
            'comment' => $messageContent, // Store raw message here
            // No status change for messages
            'status_from' => null,
            'status_to' => null, 
             // Optionally add metadata like sender role if needed elsewhere
             // 'metadata' => ['sender_role' => $senderRole]
        ]);

        // Trigger notification to the other party about the new message
         try {
             $recipient = ($sender->id === $order->client_user_id) ? $order->producer : $order->client;
             if ($recipient) {
                 Notification::send($recipient, new NewOrderMessage($order, $sender, $messageContent));
            }
        } catch (\Exception $e) {
             Log::error('Failed to send NewOrderMessage notification', [
                'order_id' => $order->id,
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id ?? null,
                'error' => $e->getMessage()
            ]);
            // Do not fail operation for notification error
        }

        Log::info('Message posted to order.', ['order_id' => $order->id, 'sender_id' => $sender->id, 'role' => $senderRole]);

        return $event;
    }

    // Methods for order state transitions will be added here...
    
} 