<?php

namespace App\Exceptions\Pitch;

/**
 * Exception thrown when trying to perform an invalid status transition
 */
class InvalidStatusTransitionException extends PitchException
{
    /**
     * @var string
     */
    protected $currentStatus;

    /**
     * @var string
     */
    protected $targetStatus;

    /**
     * The HTTP status code for this exception
     *
     * @var int
     */
    protected $statusCode = 422;

    /**
     * Create a new instance of the exception
     *
     * @param  string  $currentStatus  The current status of the pitch
     * @param  string  $targetStatus  The target status that was attempted
     * @param  string  $message  The error message
     * @param  int  $code  The error code
     * @param  \Throwable|null  $previous  The previous exception
     */
    public function __construct(
        string $currentStatus,
        string $targetStatus,
        string $message = 'Invalid status transition',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->currentStatus = $currentStatus;
        $this->targetStatus = $targetStatus;

        $message = $message.": Cannot transition from '$currentStatus' to '$targetStatus'";

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the current status
     *
     * @return string
     */
    public function getCurrentStatus()
    {
        return $this->currentStatus;
    }

    /**
     * Get the target status
     *
     * @return string
     */
    public function getTargetStatus()
    {
        return $this->targetStatus;
    }
}
