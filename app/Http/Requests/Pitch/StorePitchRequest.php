<?php
namespace App\Http\Requests\Pitch;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Project;

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
        $rules = [
            // project_id is usually from route binding, but validate if in request body
            'project_id' => 'sometimes|required|exists:projects,id',
            'agree_terms' => 'accepted', // Always require platform terms checkbox
            // Add rules for title, description if they are part of the initial form
            // 'title' => 'required|string|max:255',
            // 'description' => 'nullable|string|max:2048',
        ];
        
        // Add license agreement requirement if project requires it
        $project = $this->route('project') ?? Project::find($this->input('project_id'));
        if ($project && $project->requiresLicenseAgreement()) {
            $rules['agree_license'] = 'accepted';
        }
        
        return $rules;
    }

    public function messages(): array
    {
        return [
            'agree_terms.accepted' => 'You must agree to the Terms and Conditions to submit a pitch.',
            'agree_license.accepted' => 'You must agree to the project license terms to submit a pitch.',
        ];
    }
} 