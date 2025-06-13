<?php
namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Project;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization logic should be handled by policies or controllers
        // Typically check if the user owns the project
        // $project = $this->route('project'); // Get project from route
        // return $project && $this->user()->can('update', $project);
        return true; // Assuming policy handles authorization
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|max:5000',
            'artist_name' => 'sometimes|nullable|string|max:255',
            // Keep original 'project_type' validation if it serves a different purpose
            // 'project_type' => 'sometimes|required|string|max:100',
            'collaboration_type' => 'sometimes|nullable|array',
            'collaboration_type.*' => 'string|max:50',
            'budget' => 'sometimes|required|string|max:100',
            'genre_id' => 'sometimes|required|exists:genres,id',
            'subgenre_id' => 'nullable|exists:subgenres,id,parent_genre_id,'.intval($this->input('genre_id')), // Ensure genre_id is present
            'visibility' => ['sometimes', 'required', Rule::in(['public', 'unlisted'])],
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Example image validation

            // Add validation for workflow_type
            'workflow_type' => ['sometimes', 'required', Rule::in(Project::getWorkflowTypes())],

            // Conditional Validation (adjust based on how updates handle these)
            'submission_deadline' => 'nullable|date|after:now',
            'judging_deadline' => 'nullable|date|after:submission_deadline',
            'prize_amount' => 'nullable|numeric|min:0',
            'prize_currency' => 'nullable|string|size:3',
            'target_producer_id' => 'nullable|exists:users,id',
            'client_email' => 'nullable|email|max:255',
            'client_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
        ];
    }
} 