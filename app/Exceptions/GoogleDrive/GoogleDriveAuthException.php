<?php

namespace App\Exceptions\GoogleDrive;

use Exception;

class GoogleDriveAuthException extends Exception
{
    protected $message = 'Google Drive authentication failed.';
}