<?php

namespace App\Http\Controllers;

use App\Models\PitchFile;
use App\Models\ProjectFile;
use App\Services\FileManagementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FileDownloadController extends Controller
{
    use AuthorizesRequests;

    protected $fileManagementService;

    public function __construct(FileManagementService $fileManagementService)
    {
        $this->fileManagementService = $fileManagementService;
    }

    /**
     * Handle secure downloads for pitch files
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function downloadPitchFile(PitchFile $file)
    {
        try {
            // Authorization: Use Policy
            $this->authorize('downloadFile', $file);

            // Use the PitchFile model's permission-based download URL
            $signedUrl = $file->getDownloadUrl(Auth::user(), 60);

            if (! $signedUrl) {
                Log::error('Could not generate download URL for pitch file', [
                    'file_id' => $file->id,
                    'file_uuid' => $file->uuid,
                    'user_id' => Auth::id(),
                ]);

                return back()->with('error', 'Unable to generate download URL. Please try again.');
            }

            // Log the successful download attempt
            Log::info('Pitch file download requested', [
                'file_id' => $file->id,
                'file_uuid' => $file->uuid,
                'user_id' => Auth::id(),
                'filename' => $file->file_name,
                'will_receive_watermarked' => $file->shouldServeWatermarked(Auth::user()),
            ]);

            // Redirect to the signed URL
            return redirect()->away($signedUrl);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning('Unauthorized attempt to download pitch file', ['file_id' => $file->id, 'file_uuid' => $file->uuid, 'user_id' => Auth::id()]);
            abort(403, 'You are not authorized to download this file.');
        } catch (\Exception $e) {
            Log::error('Error generating download URL for pitch file', [
                'file_id' => $file->id,
                'file_uuid' => $file->uuid,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to download file. Please try again.');
        }
    }

    /**
     * Handle secure downloads for project files
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function downloadProjectFile($id)
    {
        $file = ProjectFile::findOrFail($id);

        try {
            // Authorization: Use Policy
            $this->authorize('download', $file);

            // Generate URL using the service
            $signedUrl = $this->fileManagementService->getTemporaryDownloadUrl($file, Auth::user());

            // Log the successful download attempt
            Log::info('Project file download requested', [
                'file_id' => $id,
                'user_id' => Auth::id(),
                'filename' => $file->file_name, // Assuming file_name exists
            ]);

            // Redirect to the signed URL
            return redirect()->away($signedUrl);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning('Unauthorized attempt to download project file', ['file_id' => $file->id, 'user_id' => Auth::id()]);
            abort(403, 'You are not authorized to download this file.');
        } catch (\Exception $e) {
            Log::error('Error generating download URL for project file', [
                'file_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to download file. Please try again.');
        }
    }
}
