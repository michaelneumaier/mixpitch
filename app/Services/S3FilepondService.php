<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use RahulHaque\Filepond\Services\FilepondService;

class S3FilepondService extends FilepondService
{
    /**
     * Merge chunks for S3-compatible storage
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function chunk(Request $request)
    {
        // Increase execution time for chunk operations
        $originalTimeLimit = ini_get('max_execution_time');
        set_time_limit(300); // 5 minutes

        try {
            $id = Crypt::decrypt($request->patch)['id'];
            $tempDisk = config('filepond.temp_disk', 'local');
            $tempFolder = config('filepond.temp_folder', 'filepond/temp');

            $filename = $request->header('Upload-Name');
            $length = $request->header('Upload-Length');
            $offset = $request->header('Upload-Offset');

            $chunkPath = $tempFolder.'/'.$id.'/'.$offset;

            \Illuminate\Support\Facades\Log::debug('Processing chunk upload', [
                'id' => $id,
                'filename' => $filename,
                'offset' => $offset,
                'length' => $length,
                'chunk_path' => $chunkPath,
            ]);

            // Store chunk in S3/R2 with timeout configuration
            $startTime = microtime(true);
            Storage::disk($tempDisk)->put($chunkPath, $request->getContent());
            $uploadTime = round(microtime(true) - $startTime, 2);

            \Illuminate\Support\Facades\Log::debug('Chunk uploaded to S3', [
                'id' => $id,
                'offset' => $offset,
                'upload_time_seconds' => $uploadTime,
                'chunk_size_bytes' => strlen($request->getContent()),
            ]);

            // Check if all chunks are uploaded by listing files
            $chunkFiles = Storage::disk($tempDisk)->files($tempFolder.'/'.$id);

            $totalSize = 0;
            foreach ($chunkFiles as $chunkFile) {
                $totalSize += Storage::disk($tempDisk)->size($chunkFile);
            }

            \Illuminate\Support\Facades\Log::debug('Chunk upload status', [
                'id' => $id,
                'total_size' => $totalSize,
                'expected_length' => $length,
                'chunk_count' => count($chunkFiles),
                'is_complete' => $length == $totalSize,
            ]);

            // If all chunks are uploaded, merge them
            if ($length == $totalSize) {
                $this->mergeChunksS3($id, $filename, $chunkFiles, $tempDisk, $tempFolder);
            }

            return $totalSize;

        } finally {
            // Restore original time limit
            set_time_limit($originalTimeLimit);
        }
    }

    /**
     * Merge chunks stored in S3/R2 with optimizations for large files
     */
    protected function mergeChunksS3(int $id, string $filename, array $chunkFiles, string $tempDisk, string $tempFolder)
    {
        // Increase execution time for large file operations
        $originalTimeLimit = ini_get('max_execution_time');
        set_time_limit(300); // 5 minutes for large files

        \Illuminate\Support\Facades\Log::info('Starting chunk merge', [
            'id' => $id,
            'filename' => $filename,
            'chunk_count' => count($chunkFiles),
            'temp_disk' => $tempDisk,
            'original_time_limit' => $originalTimeLimit,
            'new_time_limit' => 300,
        ]);

        // Sort chunks by offset (filename)
        usort($chunkFiles, function ($a, $b) {
            return intval(basename($a)) - intval(basename($b));
        });

        // Create a temporary local file to merge chunks
        $tempLocalFile = tempnam(sys_get_temp_dir(), 'filepond_merge_');

        try {
            $handle = fopen($tempLocalFile, 'w');

            // Download and merge each chunk with memory optimization
            foreach ($chunkFiles as $index => $chunkFile) {
                \Illuminate\Support\Facades\Log::debug('Processing chunk', [
                    'chunk' => $chunkFile,
                    'index' => $index + 1,
                    'total' => count($chunkFiles),
                ]);

                // Use stream for large chunks to reduce memory usage
                $chunkStream = Storage::disk($tempDisk)->readStream($chunkFile);
                if ($chunkStream) {
                    stream_copy_to_stream($chunkStream, $handle);
                    fclose($chunkStream);
                } else {
                    // Fallback to get() method
                    $chunkContent = Storage::disk($tempDisk)->get($chunkFile);
                    fwrite($handle, $chunkContent);
                }

                // Delete the chunk after merging to free up space
                Storage::disk($tempDisk)->delete($chunkFile);

                // Force garbage collection for large files
                if (($index + 1) % 10 === 0) {
                    gc_collect_cycles();
                }
            }

            fclose($handle);

            $fileSize = filesize($tempLocalFile);
            \Illuminate\Support\Facades\Log::info('Chunks merged, uploading final file', [
                'id' => $id,
                'local_file_size' => $fileSize,
                'file_size_mb' => round($fileSize / 1024 / 1024, 2),
            ]);

            // Upload the merged file back to S3/R2 using stream for large files
            $finalPath = $tempFolder.'/'.$id.'/'.$filename;
            $uploadStartTime = microtime(true);

            $fileStream = fopen($tempLocalFile, 'r');
            Storage::disk($tempDisk)->put($finalPath, $fileStream);
            fclose($fileStream);

            $uploadEndTime = microtime(true);
            $uploadDuration = round($uploadEndTime - $uploadStartTime, 2);

            \Illuminate\Support\Facades\Log::info('File uploaded to S3/R2', [
                'id' => $id,
                'final_path' => $finalPath,
                'upload_duration_seconds' => $uploadDuration,
                'upload_speed_mbps' => round(($fileSize / 1024 / 1024) / $uploadDuration, 2),
            ]);

            // Update the filepond record
            $model = config('filepond.model');
            $filepond = $model::find($id);
            if ($filepond) {
                $filepond->update([
                    'filepath' => $finalPath,
                    'filename' => $filename,
                    'extension' => pathinfo($filename, PATHINFO_EXTENSION),
                    'mimetypes' => 'application/octet-stream', // We'll detect this later when processing
                ]);
            }

            \Illuminate\Support\Facades\Log::info('Chunk merge completed successfully', [
                'id' => $id,
                'final_path' => $finalPath,
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Chunk merge failed', [
                'id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        } finally {
            // Clean up local temp file
            if (file_exists($tempLocalFile)) {
                unlink($tempLocalFile);
            }

            // Restore original time limit
            set_time_limit($originalTimeLimit);
        }
    }

    /**
     * Get the offset of the last uploaded chunk for resume
     *
     * @return int
     *
     * @throws \Throwable
     */
    public function offset(string $content)
    {
        $filepond = $this->retrieve($content);
        $tempDisk = config('filepond.temp_disk', 'local');
        $tempFolder = config('filepond.temp_folder', 'filepond/temp');

        $chunkFiles = Storage::disk($tempDisk)->files($tempFolder.'/'.$filepond->id);

        $size = 0;
        foreach ($chunkFiles as $chunkFile) {
            $size += Storage::disk($tempDisk)->size($chunkFile);
        }

        return $size;
    }

    /**
     * Restore filepond file for S3
     *
     * @return array
     */
    public function restore(string $content)
    {
        $filepond = $this->retrieve($content);
        $tempDisk = config('filepond.temp_disk', 'local');

        return [$filepond, Storage::disk($tempDisk)->get($filepond->filepath)];
    }

    /**
     * Delete the filepond file and record with S3 support
     *
     * @return bool|null
     */
    public function delete(\RahulHaque\Filepond\Models\Filepond $filepond)
    {
        $tempDisk = config('filepond.temp_disk', 'local');

        \Illuminate\Support\Facades\Log::info('Deleting FilePond file', [
            'filepond_id' => $filepond->id,
            'filepath' => $filepond->filepath,
            'filename' => $filepond->filename,
        ]);

        // Delete the file from S3/R2 if it exists
        if ($filepond->filepath && Storage::disk($tempDisk)->exists($filepond->filepath)) {
            Storage::disk($tempDisk)->delete($filepond->filepath);
        }

        // Delete any remaining chunks (cleanup in case of incomplete uploads)
        $tempFolder = config('filepond.temp_folder', 'filepond/temp');
        $chunkDirectory = $tempFolder.'/'.$filepond->id;
        if (Storage::disk($tempDisk)->exists($chunkDirectory)) {
            Storage::disk($tempDisk)->deleteDirectory($chunkDirectory);
        }

        // Delete the filepond database record
        if (config('filepond.soft_delete', true)) {
            return $filepond->delete(); // Soft delete
        } else {
            return $filepond->forceDelete(); // Hard delete
        }
    }
}
