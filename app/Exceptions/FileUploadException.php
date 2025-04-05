<?php

namespace App\Exceptions;

use Exception;

class FileUploadException extends Exception
{
    /**
     * Create a new file upload exception instance.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @return void
     */
    public function __construct(string $message = 'File upload failed', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 