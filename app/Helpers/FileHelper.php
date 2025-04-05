<?php

namespace App\Helpers;

class FileHelper
{
    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public static function formatFileSize($bytes, $precision = 2)
    {
        if ($bytes === null || $bytes <= 0) {
            return '0 bytes';
        }

        $units = ['bytes', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
} 