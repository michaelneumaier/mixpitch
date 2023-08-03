<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Track;


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
        return view('projects.project', compact('project'));
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
            'genre' => 'required|in:Pop,Rock,Country,Hip Hop,Jazz',
            'project_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            //'files.*' => 'required|mimes:wav,mp3',
        ]);

        $project = new Project();
        $project->name = $request->name;
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
                //$path = Storage::putFile('projects', $file);
                $projectFile = new ProjectFile([
                    'project_id' => $project->id,
                    'file_path' => $path,
                ]);

                $projectFile->save();
            }
        }
        return response()->json(['success' => 'Project created successfully!']);

        //return redirect()->route('projects.show', $project->id)->with('success', 'Project created successfully!');
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
            'genre' => 'required|in:Pop,Rock,Country,Hip Hop,Jazz',
            'status' => 'required|in:open,review,closed',
        ]);

        // Update the project's name and genre
        $project->name = $request->input('name');
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
        return redirect()->route('projects.edit', $project)->with('success', 'Project updated successfully.');
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
        Storage::delete($file->file_path);

        // Remove the file entry from the database
        $file->delete();

        return redirect()->route('projects.edit', $project->id)->with('success', 'File deleted successfully.');
    }


    // public function delete(Project $project)
    // {
    //     // Ensure the authenticated user is the owner of the project
    //     if (auth()->id() !== $project->user_id) {
    //         abort(403);
    //     }

    //     // Delete tracks and associated files
    //     foreach ($project->tracks as $track) {
    //         \Storage::delete($track->filename);
    //         $track->delete();
    //     }

    //     // Delete the project
    //     $project->delete();

    //     return redirect()->route('projects.index');
    // }

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
        return redirect()->route('projects.index')->with('success', 'Project and all associated files have been deleted.');
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
