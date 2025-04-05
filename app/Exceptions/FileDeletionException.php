<?php

namespace App\Exceptions;

use Exception;

class FileDeletionException extends Exception
{
    /**
     * Create a new file deletion exception instance.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @return void
     */
    public function __construct(string $message = 'File deletion failed', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 