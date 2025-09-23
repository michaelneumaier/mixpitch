<?php

namespace App\Http\Controllers;

use App\Models\PitchFile;
use App\Models\ProjectFile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class UniversalVideoController extends Controller
{
    use AuthorizesRequests;

    /**
     * Show a pitch file in the universal video player
     */
    public function showPitchFile(PitchFile $file)
    {
        $this->authorize('view', $file);

        $file->load(['pitch.project', 'pitch.user', 'comments.user']);

        return view('video.show', [
            'file' => $file,
            'fileType' => 'pitch_file',
            'breadcrumbs' => [
                'title' => $file->pitch->title ?? 'Pitch',
                'url' => \App\Helpers\RouteHelpers::pitchUrl($file->pitch),
                'icon' => 'fas fa-video',
            ],
        ]);
    }

    /**
     * Show a project file in the universal video player
     */
    public function showProjectFile(ProjectFile $file)
    {
        $this->authorize('view', $file);

        $file->load(['project', 'project.user']);

        return view('video.show', [
            'file' => $file,
            'fileType' => 'project_file',
            'breadcrumbs' => [
                'title' => $file->project->title ?? 'Project',
                'url' => route('projects.show', $file->project),
                'icon' => 'fas fa-folder-open',
            ],
        ]);
    }

    /**
     * Universal video player route that detects file type and redirects
     */
    public function show(Request $request)
    {
        $fileType = $request->get('type');
        $fileId = $request->get('id');

        if (! $fileType || ! $fileId) {
            abort(400, 'Missing file type or ID');
        }

        switch ($fileType) {
            case 'pitch_file':
                $file = PitchFile::where('uuid', $fileId)->firstOrFail();

                return $this->showPitchFile($file);

            case 'project_file':
                $file = ProjectFile::findOrFail($fileId);

                return $this->showProjectFile($file);

            default:
                abort(400, 'Invalid file type');
        }
    }
}