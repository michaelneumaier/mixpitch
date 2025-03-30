<?php
namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Project;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project'); // Assuming route model binding
        if (!$project instanceof Project) {
            // Handle cases where route model binding might fail or isn't used
            // Depending on your routes, you might need to fetch the project differently
            return false; // Or log an error
        }
        return $this->user()->can('update', $project);
    }

    public function rules(): array
    {
         // Extracted from ProjectController::update (or existing validation)
         // Loosen 'required' for fields that aren't always updated
         // TODO: Review and confirm these rules match current project fields/needs
         return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2048',
            'genre' => 'sometimes|required|string|in:Pop,Rock,Country,Hip Hop,Jazz', // Use Enum
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Renamed from project_image?
            'artist_name' => 'sometimes|required|string|max:255',
            'project_type' => 'sometimes|required|string|max:100',
            'collaboration_type' => 'sometimes|required|array',
            'collaboration_type.*' => 'string|max:100',
            'budget' => 'sometimes|required|numeric|min:0',
            'deadline' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:5000',
            'is_published' => 'sometimes|boolean', // For publish/unpublish via update
            // Only allow status changes via dedicated actions/services, not mass update.
            // 'status' => 'sometimes|required|in:...' - Avoid this
         ];
    }
} 