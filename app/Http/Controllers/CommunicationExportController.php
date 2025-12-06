<?php

namespace App\Http\Controllers;

use App\Models\Pitch;
use App\Models\Project;
use App\Services\CommunicationExportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommunicationExportController extends Controller
{
    public function __construct(
        protected CommunicationExportService $exportService
    ) {}

    /**
     * Show printable communication transcript
     */
    public function print(Project $project, Pitch $pitch): View
    {
        // Ensure the pitch belongs to the project
        abort_unless($pitch->project_id === $project->id, 404);

        // Authorization - must be project owner or pitch owner
        $user = auth()->user();
        abort_unless(
            $user->id === $project->user_id || $user->id === $pitch->user_id,
            403,
            'Unauthorized to view this communication.'
        );

        $data = $this->exportService->getExportData($pitch);

        return view('communication.print', $data);
    }

    /**
     * Show printable communication transcript for client portal (signed URL)
     */
    public function clientPrint(Request $request, Project $project, Pitch $pitch): View
    {
        // Ensure the pitch belongs to the project
        abort_unless($pitch->project_id === $project->id, 404);

        // Verify signed URL
        abort_unless($request->hasValidSignature(), 403, 'Invalid or expired link.');

        $data = $this->exportService->getExportData($pitch);

        return view('communication.print', $data);
    }
}
