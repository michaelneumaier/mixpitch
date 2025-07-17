<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest; // If needed for policy
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Typically, any authenticated user can attempt to create a project
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'artist_name' => 'nullable|string|max:255',
            // Keep original 'project_type' validation if it serves a different purpose
            // 'project_type' => 'required|string|max:100', // Adjust max as needed
            'collaboration_type' => 'nullable|array',
            'collaboration_type.*' => 'string|max:50',
            'budget' => 'required|string|max:100',
            'genre_id' => 'required|exists:genres,id',
            'subgenre_id' => 'nullable|exists:subgenres,id,parent_genre_id,'.intval($this->genre_id),
            'visibility' => ['required', Rule::in(['public', 'unlisted'])],
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Example image validation

            // Validation for workflow_type
            'workflow_type' => ['required', Rule::in(Project::getWorkflowTypes())],

            // Conditional Validation
            'submission_deadline' => 'required_if:workflow_type,'.Project::WORKFLOW_TYPE_CONTEST.'|nullable|date|after:now',
            'judging_deadline' => 'nullable|date|after:submission_deadline',
            'prize_amount' => 'required_if:workflow_type,'.Project::WORKFLOW_TYPE_CONTEST.'|nullable|numeric|min:0',
            'prize_currency' => 'required_with:prize_amount|nullable|string|size:3',

            'target_producer_id' => 'required_if:workflow_type,'.Project::WORKFLOW_TYPE_DIRECT_HIRE.'|nullable|exists:users,id',

            'client_email' => 'required_if:workflow_type,'.Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT.'|nullable|email|max:255',
            'client_name' => 'nullable|string|max:255',
        ];
    }
}
