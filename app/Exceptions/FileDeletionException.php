<?php

namespace App\Exceptions;

use Exception;

class FileDeletionException extends Exception
{
    /**
     * Create a new file deletion exception instance.
     *
     * @return void
     */
    public function __construct(string $message = 'File deletion failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
