<?php

namespace App\Exceptions\GoogleDrive;

use Exception;

class GoogleDriveQuotaException extends Exception
{
    protected $message = 'Google Drive operation failed due to quota limitations.';
}