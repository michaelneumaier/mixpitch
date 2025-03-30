<?php

namespace App\Exceptions\Pitch;

use Exception;

class CompletionValidationException extends PitchException
{
    /**
     * Create a new completion validation exception.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "Pitch completion validation failed", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 