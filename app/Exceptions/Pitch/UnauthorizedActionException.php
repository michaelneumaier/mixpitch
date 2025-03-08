<?php

namespace App\Exceptions\Pitch;

/**
 * Exception thrown when a user tries to perform an unauthorized action on a pitch
 */
class UnauthorizedActionException extends PitchException
{
    /**
     * The HTTP status code for this exception
     *
     * @var int
     */
    protected $statusCode = 403;
    
    /**
     * @var string
     */
    protected $action;
    
    /**
     * Create a new instance of the exception
     *
     * @param string $action The action that was attempted
     * @param string $message The error message
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(
        string $action,
        string $message = "Unauthorized action",
        int $code = 0,
        \Throwable $previous = null
    ) {
        $this->action = $action;
        
        $message = $message . ": You are not authorized to $action this pitch";
        
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Get the attempted action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }
}
