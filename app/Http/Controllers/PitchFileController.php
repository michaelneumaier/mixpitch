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
}
