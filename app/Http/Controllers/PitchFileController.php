<?php

// app/Http/Controllers/PitchFileController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pitch;
use App\Models\PitchFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

    public function upload(Request $request, Pitch $pitch)
    {

        $request->validate([
            'files.*' => 'required|file|max:10240', // Max 10MB per file
        ]);

        foreach ($request->file('files') as $file) {
            $filePath = $file->store('pitch_files', 'public');
            $fileSize = $file->getSize(); // Get file size in bytes

            // Save file information in the database
            $pitch->files()->create([
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'size' => $fileSize,
                'user_id' => Auth::id(),
            ]);
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

        // Delete the file from storage
        Storage::disk('public')->delete($file->file_path);

        // Delete the file record from the database
        $file->delete();

        // Redirect back to the pitch page
        return redirect()->route('pitches.show', $file->pitch_id)->warning('File deleted successfully.');
    }
}
