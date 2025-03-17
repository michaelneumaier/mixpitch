<?php

// app/Http/Controllers/PitchFileController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pitch;
use App\Models\PitchFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Jobs\GenerateAudioWaveform;

class PitchFileController extends Controller

{

    public function show(PitchFile $file)
    {
        $user = Auth::user();

        // Check if the authenticated user is the pitch owner or the project owner
        if ($user->id !== $file->pitch->user_id && $user->id !== $file->pitch->project->user_id) {
            abort(403, 'Unauthorized action.');
        }

        return view('pitch-files.show', compact('file'));
    }

    // Currently not used
    public function upload(Request $request, Pitch $pitch)
    {
        $request->validate([
            'files.*' => 'required|file|max:100000', // Max 100MB per file
        ]);

        foreach ($request->file('files') as $file) {
            try {
                // Store file in S3 bucket with public visibility
                $fileName = $file->getClientOriginalName();
                $filePath = $file->storeAs(
                    'pitch_files/' . $pitch->id, 
                    $fileName, 
                    's3'
                );
                $fileSize = $file->getSize(); // Get file size in bytes

                // Save file information in the database
                $pitchFile = $pitch->files()->create([
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'size' => $fileSize,
                    'user_id' => Auth::id(),
                ]);
                
                // Check if this is an audio file and dispatch the waveform generation job
                // $extension = strtolower($file->getClientOriginalExtension());
                // $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac'];
                
                // if (in_array($extension, $audioExtensions)) {
                //     // Dispatch job to generate waveform data
                //     GenerateAudioWaveform::dispatch($pitchFile);
                // }
                
                \Log::info('Pitch file uploaded to S3', [
                    'filename' => $fileName,
                    'path' => $filePath,
                    'pitch_id' => $pitch->id
                ]);
            } catch (\Exception $e) {
                \Log::error('Error uploading pitch file to S3', [
                    'error' => $e->getMessage(),
                    'pitch_id' => $pitch->id,
                    'filename' => $file->getClientOriginalName()
                ]);
            }
        }

        return redirect()->back()->with('message', 'Files uploaded successfully.');
    }

    public function delete(PitchFile $file)
    {
        $user = Auth::user();

        // Check if the authenticated user is the pitch owner or the project owner
        if ($user->id !== $file->pitch->user_id && $user->id !== $file->pitch->project->user_id) {
            abort(403, 'Unauthorized action.');
        }

        // Delete the file from S3 storage
        Storage::disk('s3')->delete($file->file_path);

        // Delete the file record from the database
        $file->delete();

        // Redirect back to the pitch page
        return redirect()->route('pitches.show', $file->pitch_id)->warning('File deleted successfully.');
    }

    /**
     * Handle a single file upload via AJAX
     */
    public function uploadSingle(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
            'pitch_id' => 'required|exists:pitches,id',
        ]);

        $pitch = Pitch::findOrFail($request->pitch_id);
        
        // Authorize the user
        if (Auth::id() !== $pitch->user_id && Auth::id() !== $pitch->project->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        // Check if the pitch status allows file uploads
        if (!in_array($pitch->status, ['in_progress', 'pending_review'])) {
            return response()->json([
                'success' => false,
                'message' => 'Files can only be uploaded when the pitch is in progress or pending review.'
            ], 400);
        }

        try {
            $file = $request->file('file');
            
            // Store file in S3 bucket with public visibility
            $fileName = $file->getClientOriginalName();
            $filePath = $file->storeAs(
                'pitch_files/' . $pitch->id, 
                $fileName, 
                's3'
            );
            $fileSize = $file->getSize(); // Get file size in bytes

            // Save file information in the database
            $pitchFile = $pitch->files()->create([
                'file_path' => $filePath,
                'file_name' => $fileName,
                'size' => $fileSize,
                'user_id' => Auth::id(),
            ]);
            
            // Check if this is an audio file and dispatch the waveform generation job
            $extension = strtolower($file->getClientOriginalExtension());
            $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac'];
            
            if (in_array($extension, $audioExtensions)) {
                // Dispatch job to generate waveform data
                GenerateAudioWaveform::dispatch($pitchFile);
            }
            
            \Log::info('Single pitch file uploaded to S3 via AJAX', [
                'filename' => $fileName,
                'path' => $filePath,
                'pitch_id' => $pitch->id,
                'file_id' => $pitchFile->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'file_path' => $filePath,
                'file_id' => $pitchFile->id,
                'file_name' => $fileName,
                'file_size' => $fileSize
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error uploading single pitch file to S3 via AJAX', [
                'error' => $e->getMessage(),
                'pitch_id' => $pitch->id,
                'file_name' => $request->file('file')->getClientOriginalName()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error uploading file: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Download a file
     */
    public function download(PitchFile $file)
    {
        $user = Auth::user();

        // Check if the authenticated user is the pitch owner or the project owner
        if ($user->id !== $file->pitch->user_id && $user->id !== $file->pitch->project->user_id) {
            abort(403, 'Unauthorized action.');
        }

        // Download the file from S3 storage
        $filePath = $file->file_path;
        $fileName = $file->file_name;
        $fileSize = $file->size;

        return response()->download(Storage::disk('s3')->path($filePath), $fileName, [
            'Content-Length' => $fileSize
        ]);
    }
}
