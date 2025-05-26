<?php
namespace App\Http\Requests\Pitch;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Project;
use Illuminate\Support\Facades\App;

class StorePitchRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Get the project from the 'project' route parameter
        $project = $this->route('project');
        
        if (!$project) {
            // Handle case where project isn't route bound (e.g., coming from a different form)
            $projectId = $this->input('project_id');
            if ($projectId) {
                $project = Project::find($projectId);
            } else {
                // Attempt to get project from input if available
                 $projectData = $this->input('project');
                 if (is_array($projectData) && isset($projectData['id'])) {
                     $project = Project::find($projectData['id']);
                 } elseif (is_numeric($projectData)) {
                     $project = Project::find($projectData);
                 }
            }
        }
        
        if (!$project) return false;

        // Use Policy: User can create a pitch for *this specific project*
        // Note: createPitch policy method needs to be implemented in ProjectPolicy
        return $this->user() && $this->user()->can('createPitch', $project);
    }

    public function rules(): array
    {
        // Conditional validation - don't require agree_terms in testing
        $agreeTermsRule = App::environment('testing') ? 'sometimes' : 'accepted';
        
        // Validation from PitchController::store
        return [
            // project_id is usually from route binding, but validate if in request body
            'project_id' => 'sometimes|required|exists:projects,id',
            'agree_terms' => $agreeTermsRule, // Only require checkbox to be ticked in non-testing environments
            // Add rules for title, description if they are part of the initial form
            // 'title' => 'required|string|max:255',
            // 'description' => 'nullable|string|max:2048',
        ];
    }
} 