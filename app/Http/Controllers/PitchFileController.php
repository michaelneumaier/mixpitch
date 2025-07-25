<?php

// app/Http/Controllers/PitchFileController.php

namespace App\Http\Controllers;

use App\Exceptions\File\FileDeletionException;
use App\Exceptions\File\FileUploadException;
use App\Exceptions\File\StorageLimitException;
use App\Helpers\RouteHelpers;
use App\Models\FileUploadSetting;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Services\FileManagementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PitchFileController extends Controller
{
    use AuthorizesRequests;

    protected $fileManagementService;

    public function __construct(FileManagementService $fileManagementService)
    {
        $this->fileManagementService = $fileManagementService;
    }

    public function show(PitchFile $file)
    {
        $this->authorize('view', $file);

        $file->load(['pitch.project', 'pitch.user', 'comments.user']);

        return view('pitch-files.show', ['file' => $file]);
    }

    public function delete(PitchFile $file)
    {
        $this->authorize('delete', $file);

        $pitch = $file->pitch;

        try {
            $this->fileManagementService->deletePitchFile($file, Auth::user());

            Log::info('Pitch file deleted via Controller', [
                'pitch_id' => $pitch->id,
                'file_id' => $file->id,
            ]);

            return redirect(RouteHelpers::pitchUrl($pitch))->with('success', 'File deleted successfully.');
        } catch (FileDeletionException $e) {
            Log::warning('Failed to delete pitch file via Controller', ['file_id' => $file->id, 'error' => $e->getMessage()]);

            return redirect(RouteHelpers::pitchUrl($pitch))->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Unexpected error deleting pitch file via Controller', [
                'error' => $e->getMessage(),
                'file_id' => $file->id,
                'pitch_id' => $pitch->id,
            ]);

            return redirect(RouteHelpers::pitchUrl($pitch))->with('error', 'An unexpected error occurred while deleting the file.');
        }
    }

    /**
     * Handle a single file upload via AJAX
     */
    public function uploadSingle(Request $request, ?Pitch $pitch = null)
    {
        // Get pitch context settings for validation
        $settings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_PITCHES);
        $maxFileSizeKB = $settings[FileUploadSetting::MAX_FILE_SIZE_MB] * 1024; // Convert MB to KB for Laravel validation

        // Get pitch from route parameter or request body
        if ($pitch) {
            // Pitch passed as route parameter
            $validated = $request->validate([
                'file' => "required|file|max:{$maxFileSizeKB}",
            ]);
            $targetPitch = $pitch;
        } else {
            // Legacy format with pitch_id in request body
            $validated = $request->validate([
                'file' => "required|file|max:{$maxFileSizeKB}",
                'pitch_id' => 'required|exists:pitches,id',
            ]);
            $targetPitch = Pitch::findOrFail($validated['pitch_id']);
        }

        $file = $validated['file'];
        $user = Auth::user();

        try {
            $this->authorize('uploadFile', $targetPitch);

            $pitchFile = $this->fileManagementService->uploadPitchFile($targetPitch, $file, $user);

            $targetPitch->refresh();

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'file_id' => $pitchFile->id,
                'file_name' => $pitchFile->file_name,
                'file_path' => $pitchFile->file_path,
                'file_size' => $pitchFile->size,
                'mime_type' => $pitchFile->mime_type,
                'storage_used_formatted' => Pitch::formatBytes($targetPitch->total_storage_used),
                'storage_percentage' => $targetPitch->getStorageUsedPercentage(),
                'storage_remaining_formatted' => Pitch::formatBytes($targetPitch->getRemainingStorageBytes()),
                'storage_limit_message' => $targetPitch->getStorageLimitMessage(),
            ]);

        } catch (FileUploadException|StorageLimitException $e) {
            Log::warning('Pitch file upload failed (validation)', ['pitch_id' => $targetPitch->id, 'user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning('Unauthorized attempt to upload pitch file via Controller', ['pitch_id' => $targetPitch->id, 'user_id' => $user->id]);

            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to upload files to this pitch.',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error uploading single pitch file via AJAX Controller', [
                'error' => $e->getMessage(),
                'pitch_id' => $targetPitch->id,
                'file_name' => $file->getClientOriginalName(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred during upload. Please try again.',
            ], 500);
        }
    }

    /**
     * Download a file
     */
    public function download(PitchFile $file)
    {
        $this->authorize('downloadFile', $file);

        try {
            // Generate URL using the service
            $signedUrl = $this->fileManagementService->getTemporaryDownloadUrl($file, 60);

            // Log the successful download attempt
            Log::info('Pitch file download requested', [
                'file_id' => $file->id,
                'file_uuid' => $file->uuid,
                'user_id' => Auth::id(),
                'filename' => $file->file_name,
            ]);

            // Redirect to the signed URL
            return redirect()->away($signedUrl);
        } catch (\Exception $e) {
            Log::error('Error generating download URL for pitch file', [
                'file_id' => $file->id,
                'file_uuid' => $file->uuid,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Unable to download file. Please try again.');
        }
    }
}
