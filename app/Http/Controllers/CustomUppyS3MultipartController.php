<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tapp\LaravelUppyS3MultipartUpload\Http\Controllers\UppyS3MultipartController;

class CustomUppyS3MultipartController extends UppyS3MultipartController
{
    /**
     * Override the createMultipartUpload method to support dynamic folders
     */
    public function createMultipartUpload(Request $request)
    {
        $type = $request->input('type');
        $filenameRequest = $request->input('filename');
        $fileExtension = pathinfo($filenameRequest, PATHINFO_EXTENSION);

        // Get metadata from the request
        $metadata = $request->input('metadata', []);
        $modelType = $metadata['modelType'] ?? null;
        $modelId = $metadata['modelId'] ?? null;

        // Generate dynamic folder based on model type and ID
        $folder = $this->generateDynamicFolder($modelType, $modelId);
        $key = $folder.Str::ulid().'.'.$fileExtension;

        try {
            $result = $this->client->createMultipartUpload([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'ContentType' => $type,
                'ContentDisposition' => 'inline',
            ]);
        } catch (\Throwable $exception) {
            return response()
                ->json([
                    'message' => 'Failed to create multipart upload.',
                    'exception' => $exception->getMessage(),
                ], 500);
        }

        return response()
            ->json([
                'uploadId' => $result['UploadId'],
                'key' => $key,
            ]);
    }

    /**
     * Generate the dynamic folder path based on model type and ID
     */
    protected function generateDynamicFolder(?string $modelType, ?string $modelId): string
    {
        // Default folder if no model information is provided
        if (! $modelType || ! $modelId) {
            $baseFolder = config('uppy-s3-multipart-upload.s3.bucket.folder');

            return $baseFolder ? $baseFolder.'/' : '';
        }

        // Generate folder based on model type
        switch ($modelType) {
            case 'App\\Models\\Project':
                return "projects/{$modelId}/";

            case 'App\\Models\\Pitch':
                return "pitches/{$modelId}/";

            default:
                // For other model types, use a generic uploads folder
                $baseFolder = config('uppy-s3-multipart-upload.s3.bucket.folder');

                return $baseFolder ? $baseFolder.'/' : 'uploads/';
        }
    }
}
