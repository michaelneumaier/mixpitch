<?php

namespace App\Exceptions;

use Exception;

class FileUploadException extends Exception
{
    /**
     * Create a new file upload exception instance.
     *
     * @return void
     */
    public function __construct(string $message = 'File upload failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
