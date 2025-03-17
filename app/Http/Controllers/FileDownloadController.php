<?php

namespace App\Http\Controllers;

use App\Models\PitchFile;
use App\Models\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FileDownloadController extends Controller
{
    /**
     * Handle secure downloads for pitch files
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function downloadPitchFile($id)
    {
        $file = PitchFile::findOrFail($id);
        
        // Check if the user has access to this file
        // User must be either the file owner, the pitch owner, or the project owner
        $pitch = $file->pitch;
        $project = $pitch->project;
        
        if (!Auth::check() || (Auth::id() !== $file->user_id && 
            Auth::id() !== $pitch->user_id && 
            Auth::id() !== $project->user_id && 
            !Auth::user()->hasRole('admin'))) {
            abort(403, 'Unauthorized access to this file.');
        }
        
        try {
            // Generate a fresh signed URL with Content-Disposition header
            $signedUrl = $this->generateSignedDownloadUrl($file);
            
            // Log the successful download attempt
            Log::info('Pitch file download requested', [
                'file_id' => $id,
                'user_id' => Auth::id(),
                'filename' => $file->file_name
            ]);
            
            // Redirect to the signed URL
            return redirect()->away($signedUrl);
        } catch (\Exception $e) {
            Log::error('Error generating download URL for pitch file', [
                'file_id' => $id,
                'error' => $e->getMessage()
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
        $project = $file->project;
        
        // Check if user has access to this file
        if (!Auth::check() || 
            (Auth::id() !== $project->user_id && !Auth::user()->hasRole('admin'))) {
            
            // Check if user has an active pitch for this project
            $userPitch = $project->userPitch(Auth::id());
            if (!$userPitch || $userPitch->status === 'pending') {
                abort(403, 'Unauthorized access to this file.');
            }
        }
        
        try {
            // Generate a fresh signed URL with Content-Disposition header
            $signedUrl = $this->generateSignedDownloadUrl($file);
            
            // Log the successful download attempt
            Log::info('Project file download requested', [
                'file_id' => $id,
                'user_id' => Auth::id(),
                'filename' => $file->getFileNameAttribute()
            ]);
            
            // Redirect to the signed URL
            return redirect()->away($signedUrl);
        } catch (\Exception $e) {
            Log::error('Error generating download URL for project file', [
                'file_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Unable to download file. Please try again.');
        }
    }
    
    /**
     * Generate a signed URL for file download
     *
     * @param  mixed  $file
     * @return string
     */
    private function generateSignedDownloadUrl($file)
    {
        // Get file path and name
        $filePath = $file->file_path;
        $fileName = $file instanceof PitchFile ? $file->file_name : $file->getFileNameAttribute();
        
        // Generate a fresh signed URL with a 5-minute expiration
        // Use Content-Disposition header to force download with correct filename
        return \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl(
            $filePath,
            now()->addMinutes(5),
            [
                'ResponseContentDisposition' => 'attachment; filename="' . $fileName . '"',
                'ResponseContentType' => 'application/octet-stream'
            ]
        );
    }
} 