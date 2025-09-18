<?php

namespace App\Exceptions\GoogleDrive;

use Exception;

class GoogleDriveFileException extends Exception
{
    protected $message = 'Google Drive file operation failed.';
}
