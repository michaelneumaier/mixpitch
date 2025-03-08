<?php

namespace App\Exceptions\Pitch;

use Exception;

/**
 * Base exception class for pitch-related exceptions
 */
class PitchException extends Exception
{
    /**
     * The status code to be used in the HTTP response
     *
     * @var int
     */
    protected $statusCode = 400;

    /**
     * Get the HTTP status code for this exception
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
