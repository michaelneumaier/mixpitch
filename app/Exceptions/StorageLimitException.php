<?php

namespace App\Exceptions;

use Exception;

class StorageLimitException extends Exception
{
    /**
     * Create a new storage limit exception instance.
     *
     * @return void
     */
    public function __construct(string $message = 'Storage limit exceeded', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
