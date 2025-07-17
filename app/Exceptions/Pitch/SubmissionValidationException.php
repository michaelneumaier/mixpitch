<?php

namespace App\Exceptions\Pitch;

use Exception;

class SubmissionValidationException extends PitchException
{
    /**
     * Create a new submission validation exception.
     */
    public function __construct(string $message = 'Pitch submission validation failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
