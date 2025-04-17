<?php

namespace App\Http\Controllers;

use App\Models\PortfolioItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AudioFileController extends Controller
{
    /**
     * Generate a pre-signed URL for an audio file
     *
     * @param string $filePath
     * @return \Illuminate\Http\Response
     */
    public function getPreSignedUrl($filePath)
    {
        try {
            Log::info('Getting pre-signed URL for file', ['path' => $filePath]);
            
            // Create pre-signed S3 URL
            $s3Client = Storage::disk('s3')->getClient();
            $bucket = config('filesystems.disks.s3.bucket');
            
            $command = $s3Client->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key' => $filePath,
            ]);
            
            // Generate a pre-signed URL that expires in 15 minutes (900 seconds)
            $presignedRequest = $s3Client->createPresignedRequest($command, '+15 minutes');
            $presignedUrl = (string) $presignedRequest->getUri();
            
            return response()->json([
                'url' => $presignedUrl
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating pre-signed URL', [
                'error' => $e->getMessage(), 
                'path' => $filePath
            ]);
            
            return response()->json([
                'error' => 'Could not generate URL for this file'
            ], 500);
        }
    }
    
    /**
     * Get pre-signed URL for a portfolio audio file
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function getPortfolioAudioUrl($id)
    {
        try {
            $portfolioItem = PortfolioItem::findOrFail($id);
            
            if ($portfolioItem->item_type !== PortfolioItem::TYPE_AUDIO || !$portfolioItem->file_path) {
                Log::warning('Attempted to get URL for non-audio item or item missing path', [
                    'item_id' => $id,
                    'item_type' => $portfolioItem->item_type,
                    'has_path' => !empty($portfolioItem->file_path)
                ]);
                return response()->json([
                    'error' => 'Not an audio file or no file found'
                ], 404);
            }
            
            Log::info('Generating pre-signed URL for portfolio audio', ['item_id' => $id, 'path' => $portfolioItem->file_path]);
            return $this->getPreSignedUrl($portfolioItem->file_path);
        } catch (\Exception $e) {
            Log::error('Error generating pre-signed URL for portfolio item', [
                'error' => $e->getMessage(), 
                'item_id' => $id
            ]);
            
            return response()->json([
                'error' => 'Could not access this audio file'
            ], 500);
        }
    }
} 