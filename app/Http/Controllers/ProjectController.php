<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Track;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

class ProjectController extends Controller
{

    public function index(Request $request)
    {
        $genres = $request->get('genre');
        $query = Project::query();

        if ($genres) {
            $query->whereIn('genre', $genres);
        }

        $projects = $query->paginate(10);
        return view('projects.index', compact('projects'));
    }

    public function show(Project $project)
    {
        $userPitch = null;
        if (auth()->check()) {
            $userPitch = $project->userPitch(auth()->id());
        }
        return view('projects.project', compact('project', 'userPitch'));
    }


    public function projects()
    {
        $projects = Track::all();
        //$projects = Track::distinct()->select('title', 'user_id', 'created_at')->get();
        return view('projects.index', compact('projects'));
    }

    public function createProject()
    {
        return view('projects.upload-project');
    }

    public function createStep2(Project $project)
    {
        return view('projects.create_step2', compact('project'));
    }


    public function storeProject(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'description' => 'max:2048',
            'genre' => 'required|in:Pop,Rock,Country,Hip Hop,Jazz',
            'project_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $project = new Project();
            $project->name = $request->name;
            $project->description = $request->description;
            $project->genre = $request->genre;
            
            if ($request->hasFile('project_image')) {
                // Store image in S3 instead of moving to public path
                $path = $request->file('project_image')->store('project_images', 's3');
                $project->image_path = $path;
                
                \Log::info('Project image uploaded to S3', [
                    'path' => $path,
                    'project_name' => $project->name
                ]);
            }
            
            $project->save();
            
            return redirect()->route('projects.createStep2', ['project' => $project]);
        } catch (\Exception $e) {
            \Log::error('Error creating project', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return redirect()->back()->with('error', 'Failed to create project: ' . $e->getMessage())->withInput();
        }
    }

    public function storeStep2(Request $request, Project $project)
    {
        $request->validate([
            'files.*' => 'required|mimes:mp3,wav|max:102400',
        ]);

        try {
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $fileName = $file->getClientOriginalName();
                    // Store file on S3 instead of 'public' disk
                    $path = $file->storeAs('projects/' . $project->id, $fileName, 's3');
                    $fileSize = $file->getSize();
                    
                    \Log::info('File uploaded to S3 via storeStep2', [
                        'filename' => $fileName,
                        'path' => $path,
                        'size' => $fileSize,
                        'project' => $project->id
                    ]);
                    
                    $projectFile = new ProjectFile([
                        'project_id' => $project->id,
                        'file_path' => $path,
                        'size' => $fileSize,
                    ]);

                    $projectFile->save();
                }
                
                // Delete any cached ZIP files since project files have changed
                $project->deleteCachedZip();
            }
            return response()->json(['success' => 'Files uploaded successfully!']);
        } catch (\Exception $e) {
            \Log::error('Error uploading files to S3 via storeStep2', [
                'error' => $e->getMessage(),
                'project' => $project->id
            ]);
            return response()->json(['error' => 'Error uploading files: ' . $e->getMessage()], 500);
        }
    }

    public function storeTrack(UploadedFile $file, Project $project)
    {
        $fileName = $file->getClientOriginalName();
        
        try {
            // Store file on S3 instead of 'public' disk
            $path = $file->storeAs('projects/' . $project->id, $fileName, 's3');
            $fileSize = $file->getSize();
            
            \Log::info('File uploaded to S3', [
                'filename' => $fileName,
                'path' => $path,
                'size' => $fileSize,
                'project' => $project->id
            ]);
            
            $projectFile = new ProjectFile([
                'project_id' => $project->id,
                'file_path' => $path,
                'size' => $fileSize,
            ]);

            $projectFile->save();
            
            // Delete any cached ZIP files since project files have changed
            $project->deleteCachedZip();
            
            return $projectFile->id;
        } catch (\Exception $e) {
            \Log::error('Error uploading file to S3', [
                'filename' => $fileName,
                'error' => $e->getMessage(),
                'project' => $project->id
            ]);
            return null;
        }
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        //$this->authorize('update', $project);
        // Validate the request data
        $request->validate([
            'name' => 'required|max:255',
            'description' => 'max:2048',
            'genre' => 'required|in:Pop,Rock,Country,Hip Hop,Jazz',
            'status' => 'required|in:unpublished,open,review,completed,closed',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            // Update the project's name and genre
            $project->name = $request->input('name');
            $project->description = $request->input('description');
            $project->genre = $request->input('genre');
            $project->status = $request->input('status');
            
            if ($request->hasFile('image')) {
                // Delete the old image if it exists
                $oldFilePath = $project->image_path;
                if ($oldFilePath && Storage::disk('s3')->exists($oldFilePath)) {
                    Storage::disk('s3')->delete($oldFilePath);
                    \Log::info('Old project image deleted from S3', [
                        'path' => $oldFilePath,
                        'project_id' => $project->id
                    ]);
                }

                // Store the new image on S3
                $path = $request->file('image')->store('project_images', 's3');
                $project->image_path = $path;
                
                \Log::info('New project image uploaded to S3', [
                    'path' => $path,
                    'project_id' => $project->id
                ]);
            }
            
            $project->save();

            // Redirect to the project's page with a success message
            return redirect()->route('projects.show', $project)->with('success', 'Project updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Error updating project', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
                'request' => $request->all()
            ]);
            
            return redirect()->back()->with('error', 'Failed to update project: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Download all project files as a ZIP
     */
    public function download(Project $project)
    {
        // Check if user has permission to download
        if (!$project->isOwnedByUser(Auth::user()) && !Auth::user()->hasRole('admin')) {
            // Check if user has an active pitch for this project
            $userPitch = $project->userPitch(Auth::id());
            
            if (!$userPitch || $userPitch->status === 'pending') {
                // User doesn't have an active pitch or it's still pending
                return back()->with('error', 'You do not have permission to download these files.');
            }
        }

        // Collect all project files
        $files = $project->files;
        
        if ($files->isEmpty()) {
            return back()->with('error', 'No files found to download.');
        }

        try {
            // First check if we have a cached ZIP file for this project state
            if ($project->hasCachedZip()) {
                \Log::info('Using cached ZIP file for project', [
                    'project_id' => $project->id,
                    'zipPath' => $project->getCachedZipPath()
                ]);
                
                // Get a signed URL for the cached ZIP
                $signedUrl = $project->getCachedZipUrl(30);
                
                if ($signedUrl) {
                    // Redirect to the signed URL
                    return redirect()->away($signedUrl);
                }
            }
            
            \Log::info('No cached ZIP found, generating new ZIP for project', [
                'project_id' => $project->id
            ]);
            
            // No cached ZIP or failed to get signed URL, so generate a new one
            return $this->generateAndCacheZip($project);
            
        } catch (\Exception $e) {
            \Log::error('Error in download process', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Error downloading files: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate a ZIP file for a project and cache it on S3
     *
     * @param Project $project
     * @return \Illuminate\Http\Response
     */
    protected function generateAndCacheZip(Project $project)
    {
        // Create a temporary folder to store the files
        $tempFolder = storage_path('app/temp-downloads/' . uniqid());
        if (!is_dir($tempFolder)) {
            mkdir($tempFolder, 0755, true);
        }

        // Keep track of successfully downloaded files
        $downloadedFiles = 0;

        // Download all files to the temporary folder using their signed URLs
        foreach ($project->files as $file) {
            try {
                $signedUrl = $file->signedUrl(30); // 30 minutes expiration
                if (!$signedUrl) {
                    \Log::warning('Failed to generate signed URL for file', [
                        'file_id' => $file->id,
                        'file_path' => $file->file_path
                    ]);
                    continue; // Skip if URL generation fails
                }
                
                // Use Guzzle to download the file from S3 via the signed URL
                $client = new \GuzzleHttp\Client();
                $localPath = $tempFolder . '/' . $file->getFileNameAttribute();
                
                $client->request('GET', $signedUrl, [
                    'sink' => $localPath
                ]);
                
                // Verify the file was actually downloaded
                if (file_exists($localPath) && filesize($localPath) > 0) {
                    $downloadedFiles++;
                    \Log::info('Downloaded file for ZIP from S3', [
                        'file' => $file->file_path,
                        'localPath' => $localPath,
                        'size' => filesize($localPath)
                    ]);
                } else {
                    \Log::warning('File downloaded but appears empty or missing', [
                        'file' => $file->file_path,
                        'localPath' => $localPath
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Error downloading file for ZIP', [
                    'file' => $file->file_path,
                    'error' => $e->getMessage()
                ]);
                // Continue with next file
            }
        }
        
        // Check if we actually have any files to zip
        if ($downloadedFiles === 0) {
            $this->cleanTempFolder($tempFolder);
            return back()->with('error', 'Unable to download any files from storage. Please try again later.');
        }
        
        // Create a ZIP archive
        $zipName = Str::slug($project->name) . '_files.zip';
        $localZipPath = storage_path('app/temp-downloads/' . $zipName);
        
        // Log the ZIP file path for debugging
        \Log::info('Creating ZIP file at path', [
            'project_id' => $project->id,
            'zipName' => $zipName,
            'localZipPath' => $localZipPath
        ]);
        
        // Create the ZIP file
        $zip = new \ZipArchive();
        $zipResult = $zip->open($localZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        
        if ($zipResult !== true) {
            \Log::error('Failed to create ZIP file', [
                'zipPath' => $localZipPath,
                'error_code' => $zipResult
            ]);
            $this->cleanTempFolder($tempFolder);
            return back()->with('error', 'Failed to create ZIP archive. Error code: ' . $zipResult);
        }
        
        try {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempFolder),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            $filesAdded = 0;
            
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = basename($filePath);
                    
                    if ($zip->addFile($filePath, $relativePath)) {
                        $filesAdded++;
                    } else {
                        \Log::warning('Failed to add file to ZIP', [
                            'file' => $filePath
                        ]);
                    }
                }
            }
            
            // Check if we added any files to the ZIP
            if ($filesAdded === 0) {
                $zip->close();
                $this->cleanTempFolder($tempFolder);
                return back()->with('error', 'No files could be added to the ZIP archive.');
            }
            
            // Close the ZIP file
            $zip->close();
            
            // Verify the ZIP file was created
            if (!file_exists($localZipPath) || filesize($localZipPath) === 0) {
                \Log::error('ZIP file was not created or is empty', [
                    'zipPath' => $localZipPath
                ]);
                $this->cleanTempFolder($tempFolder);
                return back()->with('error', 'Failed to create ZIP archive. The file was not created or is empty.');
            }
            
            // ZIP file created successfully, now upload it to S3 for caching
            $s3ZipPath = $project->getCachedZipPath();
            
            // Upload the ZIP to S3 - using a streamWrapper approach for larger files
            try {
                // Ensure the file exists and is readable
                if (!file_exists($localZipPath)) {
                    throw new \Exception("Generated ZIP file doesn't exist at path: $localZipPath");
                }
                
                // Log the local ZIP file details
                \Log::info('Local ZIP file details before upload', [
                    'path' => $localZipPath,
                    'exists' => file_exists($localZipPath),
                    'size' => filesize($localZipPath),
                    'readable' => is_readable($localZipPath)
                ]);
                
                // Read the file contents - make sure we have enough memory
                $fileContents = file_get_contents($localZipPath);
                if ($fileContents === false) {
                    throw new \Exception("Unable to read ZIP file contents: $localZipPath");
                }
                
                // Store on S3 using put() which is available in older Flysystem versions
                $uploaded = Storage::disk('s3')->put(
                    $s3ZipPath, 
                    $fileContents,
                    'private'
                );
                
                if (!$uploaded) {
                    throw new \Exception("S3 upload operation returned false");
                }
                
                \Log::info('Cached ZIP file uploaded to S3', [
                    'project_id' => $project->id,
                    's3ZipPath' => $s3ZipPath,
                    'size' => filesize($localZipPath)
                ]);
                
                // Verify the file was uploaded
                if (!Storage::disk('s3')->exists($s3ZipPath)) {
                    throw new \Exception("File was not found on S3 after upload");
                }
                
                // Get a signed URL for the newly cached ZIP
                $signedUrl = $project->getCachedZipUrl(30);
                
                // Clean up temporary files
                $this->cleanTempFolder($tempFolder);
                
                if ($signedUrl) {
                    // Redirect to the signed URL
                    return redirect()->away($signedUrl);
                } else {
                    // Fallback to serving the local ZIP
                    return response()->download($localZipPath, $zipName)->deleteFileAfterSend(true);
                }
            } catch (\Exception $e) {
                \Log::error('Error uploading ZIP to S3', [
                    'project_id' => $project->id,
                    'localZipPath' => $localZipPath,
                    's3ZipPath' => $s3ZipPath,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // If S3 upload fails, still serve the local ZIP file
                $result = response()->download($localZipPath, $zipName)->deleteFileAfterSend(true);
                $this->cleanTempFolder($tempFolder);
                return $result;
            }
            
        } catch (\Exception $e) {
            \Log::error('Exception during ZIP creation or caching', [
                'project_id' => $project->id,
                'zipPath' => $localZipPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Clean up
            if ($zip) {
                $zip->close();
            }
            $this->cleanTempFolder($tempFolder);
            
            return back()->with('error', 'An error occurred while creating the ZIP file: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete a specific file from a project
     */
    public function deleteFile(Project $project, ProjectFile $file)
    {
        // Check if user has permission
        $this->authorize('update', $project);
        
        // Check if file belongs to the project
        if ($file->project_id !== $project->id) {
            return back()->with('error', 'File does not belong to this project.');
        }
        
        try {
            // Delete from S3
            if (Storage::disk('s3')->exists($file->file_path)) {
                Storage::disk('s3')->delete($file->file_path);
                \Log::info('Deleted file from S3', [
                    'file_path' => $file->file_path,
                    'project_id' => $project->id
                ]);
            }
            
            // If this was the preview track, clear the reference
            if ($project->preview_track === $file->id) {
                $project->preview_track = null;
                $project->save();
            }
            
            // Delete the database record
            $file->delete();
            
            // Delete any cached ZIP files since the project files have changed
            $project->deleteCachedZip();
            
            return back()->with('success', 'File deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Error deleting file', [
                'file_id' => $file->id,
                'file_path' => $file->file_path,
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Error deleting file: ' . $e->getMessage());
        }
    }

    public function destroy(Project $project)
    {
        // Check if the logged-in user is the owner of the project
        if (Auth::id() !== $project->user_id) {
            return redirect()->route('projects.index')->withErrors(['You are not allowed to delete this project.']);
        }

        // Begin database transaction to ensure all-or-nothing deletion
        \DB::beginTransaction();

        try {
            // 1. Delete all pitches and their associated data
            foreach ($project->pitches as $pitch) {
                // 1.1 Delete pitch events (audit trail from memory)
                $pitch->events()->delete();
                
                // 1.2 Delete pitch notifications using the notification system from memory
                \Illuminate\Support\Facades\DB::table('notifications')
                    ->whereRaw("json_extract(data, '$.pitch_id') = ?", [$pitch->id])
                    ->delete();
                
                // 1.3 Delete pitch snapshots and their files
                foreach ($pitch->snapshots as $snapshot) {
                    // Delete files associated with this snapshot
                    if (isset($snapshot->snapshot_data['file_ids']) && is_array($snapshot->snapshot_data['file_ids'])) {
                        foreach ($snapshot->snapshot_data['file_ids'] as $fileId) {
                            $file = \App\Models\PitchFile::find($fileId);
                            if ($file) {
                                // Delete physical file if it exists
                                if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                                    Storage::disk('public')->delete($file->file_path);
                                }
                                $file->delete();
                            }
                        }
                    }
                    
                    // Delete the snapshot record
                    $snapshot->delete();
                }
                
                // 1.4 Delete any remaining pitch files not associated with snapshots
                $pitch->files()->get()->each(function($file) {
                    if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                        Storage::disk('public')->delete($file->file_path);
                    }
                    $file->delete();
                });
                
                // 1.5 Delete the pitch itself
                $pitch->delete();
            }
            
            // 2. Delete project image if exists
            $imageFilePath = $project->image_path;
            if ($imageFilePath && Storage::disk('public')->exists($imageFilePath)) {
                Storage::disk('public')->delete($imageFilePath);
            }
            
            // 3. Delete all project files from storage
            $filesPath = 'public/projects/' . $project->id;
            Storage::deleteDirectory($filesPath);
            
            // 4. Delete all project file records from database
            $project->files()->delete();
            
            // 5. Delete mixes associated with the project
            if (method_exists($project, 'mixes')) {
                $project->mixes()->delete();
            }
            
            // 6. Delete project notifications
            \Illuminate\Support\Facades\DB::table('notifications')
                ->whereRaw("json_extract(data, '$.project_id') = ?", [$project->id])
                ->delete();
            
            // 7. Delete any project comments (if applicable)
            if (method_exists($project, 'comments')) {
                $project->comments()->delete();
            }
            
            // 8. Delete the project itself
            $project->delete();
            
            // Commit the transaction if all operations succeed
            \DB::commit();
            
            // Redirect to dashboard with success message
            return redirect()->route('dashboard')->with('success', 'Project and all associated data have been deleted successfully.');
            
        } catch (\Exception $e) {
            // Roll back the transaction if any operation fails
            \DB::rollBack();
            
            // Log the error
            \Illuminate\Support\Facades\Log::error('Project deletion failed: ' . $e->getMessage());
            
            // Redirect with error message
            return redirect()->route('dashboard')->withErrors(['Failed to delete project. Please try again or contact support.']);
        }
    }

    function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Helper method to clean up temporary folder
     * 
     * @param string $folder Path to the folder to clean
     * @return void
     */
    private function cleanTempFolder($folder)
    {
        if (!is_dir($folder)) {
            return;
        }
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        
        rmdir($folder);
    }

    /**
     * Handle a single file upload via AJAX
     */
    public function uploadSingle(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
            'project_id' => 'required|exists:projects,id',
        ]);

        $project = Project::findOrFail($request->project_id);
        
        // Authorize the user
        if (Auth::id() !== $project->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        try {
            $file = $request->file('file');
            
            // Store file in S3 bucket
            $fileName = $file->getClientOriginalName();
            $filePath = $file->storeAs(
                'projects/' . $project->id, 
                $fileName, 
                's3'
            );
            $fileSize = $file->getSize(); // Get file size in bytes

            // Save file information in the database
            $projectFile = new ProjectFile([
                'project_id' => $project->id,
                'file_path' => $filePath,
                'size' => $fileSize,
            ]);
            
            $projectFile->save();
            
            // Delete any cached ZIP files since project files have changed
            $project->deleteCachedZip();
            
            \Log::info('Single project file uploaded to S3 via AJAX', [
                'filename' => $fileName,
                'path' => $filePath,
                'project_id' => $project->id,
                'file_id' => $projectFile->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'file_path' => $filePath,
                'file_id' => $projectFile->id,
                'file_name' => $fileName,
                'file_size' => $fileSize
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error uploading single project file to S3 via AJAX', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
                'file_name' => $request->file('file')->getClientOriginalName()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error uploading file: ' . $e->getMessage()
            ], 500);
        }
    }
}
