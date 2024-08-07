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
            //'files.*' => 'required|mimes:wav,mp3',
        ]);

        $project = new Project();
        $project->name = $request->name;
        $project->description = $request->description;
        $project->genre = $request->genre;
        if ($request->hasFile('project_image')) {
            $imageName = time() . '.' . $request->project_image->extension();
            $request->project_image->move(public_path('images'), $imageName);
            $project->image_path = "/images/{$imageName}";
        }
        $project->save();
        // $project = Project::create([
        //     'user_id' => auth()->id(),
        //     'name' => $request->name,
        //     'genre' => $request->genre,
        // ]);

        // if ($request->hasFile('files')) {
        //     foreach ($request->file('files') as $file) {
        //         $fileName = $file->getClientOriginalName();
        //         $path = $file->storeAs('projects/' . $project->id, $fileName, 'public');

        //         $project->files()->create([
        //             'file_path' => $path,
        //         ]);
        //     }
        // }

        //return redirect()->route('projects.show', $project->id);
        return redirect()->route('projects.createStep2', ['project' => $project]);
    }

    public function storeStep2(Request $request, Project $project)
    {
        $request->validate([
            'files.*' => 'required|mimes:mp3,wav|max:102400',
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $fileName = $file->getClientOriginalName();
                $path = $file->storeAs('projects/' . $project->id, $fileName, 'public');
                $fileSize = $file->getSize();
                //$path = Storage::putFile('projects', $file);
                $projectFile = new ProjectFile([
                    'project_id' => $project->id,
                    'file_path' => $path,
                    'size' => $fileSize,
                ]);

                $projectFile->save();
            }
        }
        return response()->json(['success' => 'Files uploaded successfully!']);

        //return redirect()->route('projects.show', $project->id)->with('success', 'Project created successfully!');
    }

    public function storeTrack(UploadedFile $file, Project $project)
    {
        $fileName = $file->getClientOriginalName();
        $path = $file->storeAs('projects/' . $project->id, $fileName, 'public');
        $fileSize = $file->getSize();
        //$path = Storage::putFile('projects', $file);
        $projectFile = new ProjectFile([
            'project_id' => $project->id,
            'file_path' => $path,
            'size' => $fileSize,
        ]);

        $projectFile->save();
        return $projectFile->id;
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
        ]);

        // Update the project's name and genre
        $project->name = $request->input('name');
        $project->description = $request->input('description');
        $project->genre = $request->input('genre');
        $project->status = $request->input('status');
        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            $oldFilePath = $project->image_path;
            if ($oldFilePath && Storage::disk('public')->exists($oldFilePath)) {
                Storage::disk('public')->delete($oldFilePath);
            }

            // Store the new image
            $imageName = $request->file('image')->store('images', 'public');
            $project->image_path = "/{$imageName}";
        }
        $project->save();

        // Redirect to the project's page with a success message
        return redirect()->route('projects.show', $project)->with('success', 'Project updated successfully.');
    }

    public function download(Project $project)
    {
        $zip = new ZipArchive();
        $zip_name = tempnam(sys_get_temp_dir(), 'project_') . '.zip';

        if ($zip->open($zip_name, ZipArchive::CREATE) !== TRUE) {
            return redirect()->back()->withErrors(['Unable to create a zip file.']);
        }

        foreach ($project->files as $file) {
            $file_path = storage_path('app/public/' . $file->file_path);
            $zip->addFile($file_path, basename($file_path));
        }

        $zip->close();

        return response()->download($zip_name)->deleteFileAfterSend();
    }


    public function deleteFile(Project $project, ProjectFile $file)
    {
        $this->authorize('update', $project);
        // Delete the file from the storage
        Storage::disk('public')->delete($file->file_path);

        // Remove the file entry from the database
        $file->delete();

        //return redirect()->route('projects.edit', $project)->with('success', 'File deleted successfully.');
    }


    public function destroy(Project $project)
    {
        // Check if the logged-in user is the owner of the project
        if (Auth::id() !== $project->user_id) {
            return redirect()->route('projects.index')->withErrors(['You are not allowed to delete this project.']);
        }

        // Retrieve project files
        //$projectFiles = $project->files;

        $imageFilePath = $project->image_path;
        if ($imageFilePath && Storage::disk('public')->exists($imageFilePath)) {
            Storage::disk('public')->delete($imageFilePath);
        }

        // Delete the files from the storage
        $filesPath = 'public/projects/' . $project->id;
        Storage::deleteDirectory($filesPath);
        //Storage::deleteDirectory('public/' . $project->project_id);
        // foreach ($projectFiles as $file) {
        //     Storage::deleteDirectory('public/' . $project->project_id);
        // }

        // Delete project_files entries in the database
        $project->files()->delete();

        // Delete the project entry from the database
        $project->delete();

        // Redirect back to the projects index with a success message
        return redirect()->route('dashboard')->with('success', 'Project and all associated files have been deleted.');
    }




    //public function storeProject(Request $request)
    // {
    //     $request->validate([
    //         'title' => 'required|string|max:255',
    //         'tracks.*' => 'required|mimes:mp3,wav|max:20480',
    //     ]);

    //     $tracks = [];

    //     foreach ($request->file('tracks') as $track) {
    //         $path = $track->store('tracks', 'public');
    //         $filename = $track->getClientOriginalName();

    //         $tracks[] = [
    //             'title' => $request->input('title'),

    //             'genre' => 'unknown', // Replace this with an appropriate genre or add a genre input field to the form
    //             'file_path' => $path,
    //             'user_id' => auth()->user()->id,
    //             'created_at' => now(),
    //             'updated_at' => now(),
    //         ];
    //     }

    //     Track::insert($tracks);

    //     return redirect()->route('tracks.index')->with('message', 'Project uploaded successfully.');
    // }
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
