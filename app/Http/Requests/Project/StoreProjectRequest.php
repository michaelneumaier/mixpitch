<?php
namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Project; // If needed for policy

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Assumes any authenticated user can create a project
        // Or use Policy: return $this->user()->can('create', Project::class);
        return auth()->check();
    }

    public function rules(): array
    {
        // Extracted from ProjectController::storeProject (or existing validation)
        // TODO: Review and confirm these rules match current project fields/needs
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2048',
            'genre' => 'required|string|in:Pop,Rock,Country,Hip Hop,Jazz', // Consider making this configurable or use an Enum
            'project_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'artist_name' => 'required|string|max:255',
            'project_type' => 'required|string|max:100', // Adjust max as needed
            'collaboration_type' => 'required|array', // Add validation for array contents if possible
            'collaboration_type.*' => 'string|max:100',
            'budget' => 'required|numeric|min:0',
            'deadline' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:5000',
            // Add rules for any other fields captured at creation
        ];
    }
} 