<?php

namespace App\Services\Project;

use App\Exceptions\File\FileUploadException;
use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProjectImageService
{
    /**
     * Allowed image MIME types
     */
    const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Maximum file size in bytes (5MB)
     */
    const MAX_FILE_SIZE = 5242880;

    /**
     * Image dimensions for optimization
     */
    const MAX_WIDTH = 1200;

    const MAX_HEIGHT = 800;

    const QUALITY = 85;

    /**
     * Upload a new project image
     *
     * @return string The path of the uploaded image
     *
     * @throws FileUploadException
     */
    public function uploadProjectImage(Project $project, UploadedFile $image): string
    {
        try {
            // Validate the image
            $this->validateImageFile($image);

            // Generate unique filename
            $filename = $this->generateImageFilename($image, $project);

            // Optimize and store the image
            $path = $this->processAndStoreImage($image, $filename);

            // Update the project
            $project->image_path = $path;
            $project->save();

            Log::info('Project image uploaded successfully', [
                'project_id' => $project->id,
                'path' => $path,
                'original_name' => $image->getClientOriginalName(),
                'size' => $image->getSize(),
            ]);

            return $path;

        } catch (\Exception $e) {
            Log::error('Error uploading project image', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw new FileUploadException('Failed to upload project image: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Update existing project image
     *
     * @return string The path of the new image
     *
     * @throws FileUploadException
     */
    public function updateProjectImage(Project $project, UploadedFile $newImage, bool $deleteOld = true): string
    {
        $oldImagePath = $project->image_path;

        try {
            // Upload new image
            $newPath = $this->uploadProjectImage($project, $newImage);

            // Delete old image if requested and it exists
            if ($deleteOld && $oldImagePath && $oldImagePath !== $newPath) {
                $this->deleteImageFile($oldImagePath);
            }

            Log::info('Project image updated successfully', [
                'project_id' => $project->id,
                'old_path' => $oldImagePath,
                'new_path' => $newPath,
            ]);

            return $newPath;

        } catch (\Exception $e) {
            Log::error('Error updating project image', [
                'project_id' => $project->id,
                'old_path' => $oldImagePath,
                'error' => $e->getMessage(),
            ]);

            throw new FileUploadException('Failed to update project image: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete project image
     */
    public function deleteProjectImage(Project $project): bool
    {
        if (! $project->image_path) {
            return true; // Nothing to delete
        }

        $imagePath = $project->image_path;

        try {
            // Delete from storage
            $deleted = $this->deleteImageFile($imagePath);

            // Update project
            $project->image_path = null;
            $project->save();

            Log::info('Project image deleted successfully', [
                'project_id' => $project->id,
                'path' => $imagePath,
                'storage_deleted' => $deleted,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error deleting project image', [
                'project_id' => $project->id,
                'path' => $imagePath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Validate uploaded image file
     *
     * @throws FileUploadException
     */
    public function validateImageFile(UploadedFile $image): void
    {
        // Check if file was uploaded successfully
        if (! $image->isValid()) {
            throw new FileUploadException('File upload failed. Please try again.');
        }

        // Check file size
        if ($image->getSize() > self::MAX_FILE_SIZE) {
            $maxSizeMB = round(self::MAX_FILE_SIZE / 1024 / 1024, 1);
            throw new FileUploadException("File size too large. Maximum allowed size is {$maxSizeMB}MB.");
        }

        // Check MIME type
        if (! in_array($image->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new FileUploadException('Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.');
        }

        // Get file content for validation
        $filePath = $this->getValidFilePath($image);

        $imageInfo = getimagesize($filePath);
        if (! $imageInfo) {
            throw new FileUploadException('Invalid image file. Please upload a valid image.');
        }

        // Check image dimensions (optional - set reasonable limits)
        [$width, $height] = $imageInfo;
        if ($width < 100 || $height < 100) {
            throw new FileUploadException('Image too small. Minimum size is 100x100 pixels.');
        }

        if ($width > 5000 || $height > 5000) {
            throw new FileUploadException('Image too large. Maximum size is 5000x5000 pixels.');
        }
    }

    /**
     * Get a valid file path for the uploaded file, handling Livewire S3 temporary files
     *
     * @throws FileUploadException
     */
    private function getValidFilePath(UploadedFile $image): string
    {
        // Try standard file paths first
        $filePath = $image->getRealPath();

        if ($filePath && file_exists($filePath)) {
            return $filePath;
        }

        // Try path() method
        $filePath = $image->path();
        if ($filePath && file_exists($filePath)) {
            return $filePath;
        }

        // Try getPathname() method
        $filePath = $image->getPathname();
        if ($filePath && file_exists($filePath)) {
            return $filePath;
        }

        // If file is stored in S3 (Livewire temporary files), download it to a local temp file
        try {
            $fileContent = $image->get();
            $tempPath = tempnam(sys_get_temp_dir(), 'livewire_image_');
            file_put_contents($tempPath, $fileContent);

            if (file_exists($tempPath) && filesize($tempPath) > 0) {
                return $tempPath;
            }
        } catch (\Exception $e) {
            Log::error('Error accessing Livewire temporary file', [
                'error' => $e->getMessage(),
                'file_path' => $image->getPathname(),
            ]);
        }

        throw new FileUploadException('Unable to access uploaded file. Please try again.');
    }

    /**
     * Generate unique filename for image
     */
    private function generateImageFilename(UploadedFile $image, Project $project): string
    {
        $extension = $image->getClientOriginalExtension() ?: 'jpg';
        $timestamp = time();
        $random = substr(md5(uniqid()), 0, 8);

        return "project_{$project->id}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Process and store the image with optimization
     *
     * @return string The storage path
     */
    private function processAndStoreImage(UploadedFile $image, string $filename): string
    {
        // Get the correct file path for Livewire temporary files
        $filePath = $this->getValidFilePath($image);

        // Get image info
        $imageInfo = getimagesize($filePath);
        [$originalWidth, $originalHeight, $imageType] = $imageInfo;

        // Create image resource from uploaded file
        $sourceImage = $this->createImageFromFile($filePath, $imageType);

        if (! $sourceImage) {
            throw new FileUploadException('Unable to process image file.');
        }

        // Calculate new dimensions while maintaining aspect ratio
        $newWidth = $originalWidth;
        $newHeight = $originalHeight;

        if ($originalWidth > self::MAX_WIDTH || $originalHeight > self::MAX_HEIGHT) {
            $ratio = min(self::MAX_WIDTH / $originalWidth, self::MAX_HEIGHT / $originalHeight);
            $newWidth = intval($originalWidth * $ratio);
            $newHeight = intval($originalHeight * $ratio);
        }

        // Create new image with optimized dimensions
        $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
            imagecolortransparent($optimizedImage, imagecolorallocatealpha($optimizedImage, 0, 0, 0, 127));
            imagealphablending($optimizedImage, false);
            imagesavealpha($optimizedImage, true);
        }

        // Resize the image
        imagecopyresampled(
            $optimizedImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );

        // Save to temporary file
        $tempPath = tempnam(sys_get_temp_dir(), 'project_image_');
        imagejpeg($optimizedImage, $tempPath, self::QUALITY);

        // Clean up memory
        imagedestroy($sourceImage);
        imagedestroy($optimizedImage);

        // Store to S3
        $path = 'project_images/'.$filename;
        Storage::disk('s3')->put($path, file_get_contents($tempPath));

        // Clean up temp files
        unlink($tempPath);

        // Clean up the Livewire temp file if we created it
        if (strpos($filePath, sys_get_temp_dir()) === 0 && strpos($filePath, 'livewire_image_') !== false) {
            unlink($filePath);
        }

        return $path;
    }

    /**
     * Create image resource from file based on type
     *
     * @return resource|false
     */
    private function createImageFromFile(string $filePath, int $imageType)
    {
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filePath);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($filePath);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($filePath);
            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp') ? imagecreatefromwebp($filePath) : false;
            default:
                return false;
        }
    }

    /**
     * Delete image file from storage
     */
    private function deleteImageFile(string $path): bool
    {
        try {
            if (Storage::disk('s3')->exists($path)) {
                return Storage::disk('s3')->delete($path);
            }

            return true; // File doesn't exist, consider it deleted
        } catch (\Exception $e) {
            Log::error('Error deleting image file from storage', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get optimized image URL with caching
     */
    public function getImageUrl(Project $project, int $expirationHours = 2): ?string
    {
        if (! $project->image_path) {
            return null;
        }

        try {
            return Storage::disk('s3')->temporaryUrl(
                $project->image_path,
                now()->addHours($expirationHours)
            );
        } catch (\Exception $e) {
            Log::error('Error generating image URL', [
                'project_id' => $project->id,
                'path' => $project->image_path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
