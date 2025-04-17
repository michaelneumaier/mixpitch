<?php

namespace App\Livewire\User;

use App\Models\PortfolioItem;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;
use Illuminate\Support\Str;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;

class ManagePortfolioItems extends Component
{
    use WithFileUploads;

    public User $user;
    public Collection $portfolioItems;
    public $availableProjects;

    // Form state properties
    public bool $showForm = false;
    public ?int $editingItemId = null;
    // public string $itemType = 'audio_upload'; // Old property, replaced by type

    // New properties for type and video URL
    #[Rule('required|in:audio,youtube')]
    public string $type = PortfolioItem::TYPE_AUDIO; // Default type

    #[Rule('required_if:type,youtube|nullable|url|max:2048')]
    public string $video_url = '';

    #[Rule('required|string|max:255')]
    public string $title = '';

    #[Rule('nullable|string')]
    public string $description = '';

    // Specific fields for types
    #[Rule(['nullable', 'file', 'mimes:mp3,wav', 'max:102400'])]
    public $audioFile = null;

    // Remove old properties
    // #[Rule('required_if:itemType,external_link|nullable|url|max:255')]
    // public string $externalUrl = '';

    // #[Rule('required_if:itemType,mixpitch_project_link|nullable|integer|exists:projects,id')]
    // public ?int $linkedProjectId = null;

    public ?string $existingFilePath = null;

    #[Rule('boolean')]
    public bool $isPublic = true;

    // public $items = []; // Seems unused, can be removed if confirmed

    // Custom validation messages
    protected $messages = [
        'video_url.required_if' => 'The YouTube URL field is required when type is YouTube Video.',
        'video_url.url' => 'The YouTube URL must be a valid URL.',
        'audioFile.mimes' => 'Only MP3 and WAV files are allowed.',
        'audioFile.max' => 'The audio file must not be larger than 100MB.',
    ];

    public function mount()
    {
        $this->user = Auth::user();
        if (!$this->user) {
            // Handle unauthorized access appropriately
            abort(403, 'You need to be logged in to manage portfolios.');
        }
        $this->loadItems();
        // $this->loadAvailableProjects(); // Remove project loading if not needed
    }

    public function loadItems()
    {
        // Load items ordered by display_order
        $this->portfolioItems = $this->user->portfolioItems()->orderBy('display_order')->get();
    }

    // Remove loadAvailableProjects if not needed
    // public function loadAvailableProjects()
    // {
    //     // Load available projects for selection
    //     $this->availableProjects = Project::all();
    // }

    public function resetForm()
    {
        $this->reset([
            'editingItemId', 'type', 'title', 'description', 'video_url',
            'audioFile', 'isPublic', 'existingFilePath'
        ]);
        $this->showForm = false;
        $this->resetValidation();
        // Ensure temp uploaded file is cleaned up if form is cancelled
        if ($this->audioFile && method_exists($this->audioFile, 'delete')) {
            try {
                $this->audioFile->delete();
            } catch (\Exception $e) {
                // Log or ignore error if temporary file deletion fails
                Log::warning('Could not delete temporary upload file during resetForm', ['error' => $e->getMessage()]);
            }
            $this->audioFile = null;
        }
    }

    public function addItem()
    {
        $this->resetForm();
        $this->type = PortfolioItem::TYPE_AUDIO; // Default to audio
        $this->isPublic = true;
        $this->video_url = ''; // Explicitly set empty video URL for new items
        $this->showForm = true;

        // Dispatch browser event to ensure UI is updated
        $this->dispatch('portfolio-form-opened');
    }

    public function editItem(int $itemId)
    {
        $item = PortfolioItem::findOrFail($itemId);
        $this->authorize('update', $item);

        $this->resetForm(); // Clear any previous state
        $this->editingItemId = $item->id;
        $this->type = $item->item_type;
        $this->title = $item->title;
        $this->description = $item->description ?? '';
        $this->isPublic = $item->is_public;
        
        // Set type-specific properties
        if ($item->item_type === PortfolioItem::TYPE_AUDIO) {
            $this->existingFilePath = $item->file_path;
            $this->video_url = ''; // Clear video URL for audio items
        } elseif ($item->item_type === PortfolioItem::TYPE_YOUTUBE) {
            $this->video_url = $item->video_url ?? '';
            $this->existingFilePath = null; // Clear file path for YouTube items
        }
        
        $this->showForm = true;
        
        // Dispatch browser event to ensure UI is updated
        $this->dispatch('portfolio-form-opened');
    }

    public function saveItem()
    {
        Log::info('Entering saveItem method.', ['type' => $this->type, 'editingItemId' => $this->editingItemId, 'video_url' => $this->video_url, 'has_audio_file' => !is_null($this->audioFile)]);

        // Common validation rules
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'isPublic' => 'boolean',
            'type' => 'required|in:audio,youtube',
        ];

        // Add type-specific rules AND pre-validation for required file on create
        if ($this->type === PortfolioItem::TYPE_AUDIO) {
            // Explicitly check for required file when creating
            if (!$this->editingItemId && !$this->audioFile) {
                 Log::warning('Audio validation failed: New item requires file.');
                 $this->addError('audioFile', 'Please select an audio file to upload.');
                 return; // Fail fast
            }
            // Only require file if creating new or if no existing file path (rule remains for size/mimes)
            $audioRule = ($this->editingItemId && $this->existingFilePath) ? 'nullable' : 'required';
            $rules['audioFile'] = [$audioRule, 'file', 'mimes:mp3,wav', 'max:102400']; // 100MB Max
            Log::info('Added audio validation rules.', ['rules' => $rules['audioFile']]);
        } elseif ($this->type === PortfolioItem::TYPE_YOUTUBE) {
            // Use a custom validator for YouTube URLs to handle various formats
            $rules['video_url'] = ['required', 'url', function ($attribute, $value, $fail) {
                if (!PortfolioItem::extractYouTubeVideoId($value)) {
                    $fail('Please enter a valid YouTube video URL.');
                }
            }];
            Log::info('Added YouTube validation rules.');
        }

        // Perform validation
        Log::info('Attempting combined validation.');
        try {
            // Use the dynamically built rules array
            $validatedData = $this->validate($rules); 
            Log::info('Combined validation passed.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Combined validation failed.', ['errors' => $e->errors()]);
            // Errors are automatically handled by Livewire, just log and return
            return;
        }

        $data = [
            'user_id' => auth()->id(),
            'item_type' => $this->type,
            'title' => $validatedData['title'],
            'description' => $validatedData['description'] ?? '',
            'is_public' => $validatedData['isPublic'],
            'video_url' => null,
            'video_id' => null,
            'file_path' => null,
            'file_name' => null,
            'original_filename' => null,
            'mime_type' => null,
            'file_size' => null,
        ];

        Log::info('Portfolio item data prepared for save.', [
            'data' => $data,
            'editing_item_id' => $this->editingItemId
        ]);

        // Process based on type
        if ($this->type === PortfolioItem::TYPE_AUDIO) {
            Log::info('Processing audio save logic.');
            // Handle audio upload logic
            if (isset($validatedData['audioFile']) && $validatedData['audioFile']) { // Check validated data
                // If a new file was validated and uploaded
                try {
                    $this->audioFile = $validatedData['audioFile']; // Ensure we use the validated file instance
                    Log::info('Processing audio file upload', ['original_name' => $this->audioFile->getClientOriginalName()]);
                    
                    $originalName = pathinfo($this->audioFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeName = Str::slug($originalName) . '-' . time();
                    $extension = $this->audioFile->getClientOriginalExtension();
                    $filePath = "portfolio-audio/{$data['user_id']}/{$safeName}.{$extension}";
                    
                    Log::info('Attempting to store file on S3', ['path' => $filePath]);
                    $path = $this->audioFile->storeAs('/', $filePath, 's3');

                    if (!$path) {
                        throw new \Exception("File storage failed for unknown reasons.");
                    }
                    
                    $data['file_path'] = $filePath;
                    $data['file_name'] = basename($filePath);
                    $data['original_filename'] = $this->audioFile->getClientOriginalName();
                    $data['mime_type'] = $this->audioFile->getMimeType();
                    $data['file_size'] = $this->audioFile->getSize();

                    // If updating, delete the old file
                    if ($this->editingItemId && $this->existingFilePath) {
                        Log::info('Deleting old audio file.', ['path' => $this->existingFilePath]);
                        Storage::disk('s3')->delete($this->existingFilePath);
                    }
                    Log::info('Audio file processed and stored.', ['path' => $filePath]);

                } catch (\Exception $e) {
                    Log::error('Error uploading audio file.', ['error' => $e->getMessage()]);
                    $this->dispatch('toast', type: 'error', message: 'Error uploading audio file: ' . $e->getMessage());
                    return;
                }
            } elseif ($this->editingItemId && $this->existingFilePath) {
                // Updating but no new file uploaded, keep existing file info
                Log::info('Keeping existing audio file.', ['path' => $this->existingFilePath]);
                $item = PortfolioItem::find($this->editingItemId);
                $data['file_path'] = $item->file_path;
                $data['file_name'] = $item->file_name;
                $data['original_filename'] = $item->original_filename;
                $data['mime_type'] = $item->mime_type;
                $data['file_size'] = $item->file_size;
            } else {
                // Should not happen due to validation, but log just in case
                 Log::error('Audio save logic reached invalid state.', ['editing' => $this->editingItemId, 'existingPath' => $this->existingFilePath]);
                 $this->dispatch('toast', type: 'error', message: 'An unexpected error occurred saving the audio item.');
                 return;
            }
            
            // Clear video fields for audio type
             $data['video_url'] = null;
             $data['video_id'] = null;

        } elseif ($this->type === PortfolioItem::TYPE_YOUTUBE) {
            Log::info('Processing YouTube save logic.');
            // Handle YouTube URL logic
            $videoId = PortfolioItem::extractYouTubeVideoId($validatedData['video_url']); // Use validated URL
            if ($videoId) {
                $data['video_url'] = $validatedData['video_url'];
                $data['video_id'] = $videoId;
                // Clear audio fields for YouTube type
                $data['file_path'] = null;
                $data['file_name'] = null;
                $data['original_filename'] = null;
                $data['mime_type'] = null;
                $data['file_size'] = null;

                // If updating from Audio to YouTube, delete old audio file
                if ($this->editingItemId) {
                    $item = PortfolioItem::find($this->editingItemId);
                    if ($item && $item->item_type === PortfolioItem::TYPE_AUDIO && $item->file_path) {
                         Log::info('Deleting old audio file when switching to YouTube.', ['path' => $item->file_path]);
                         Storage::disk('s3')->delete($item->file_path);
                    }
                }
                Log::info('YouTube data processed.', ['video_id' => $videoId]);
            } else {
                // This should not happen due to validation, but good to handle
                Log::error('YouTube video ID extraction failed after validation pass.', ['url' => $validatedData['video_url']]);
                $this->addError('video_url', 'Failed to process the YouTube URL.');
                return;
            }
        }

        // Save the item (Create or Update)
        try {
            if ($this->editingItemId) {
                Log::info('Attempting to update portfolio item.', ['id' => $this->editingItemId, 'data' => $data]);
                $item = PortfolioItem::findOrFail($this->editingItemId);
                $this->authorize('update', $item);
                $item->update($data);
                Log::info('Portfolio item updated successfully.', ['id' => $item->id]);
                session()->flash('toast', ['type' => 'success', 'message' => 'Portfolio item updated successfully.']);
            } else {
                Log::info('Attempting to create portfolio item.', ['data' => $data]);
                // Display order is handled by model event
                $item = PortfolioItem::create($data);
                Log::info('Portfolio item created successfully.', ['id' => $item->id]);
                session()->flash('toast', ['type' => 'success', 'message' => 'Portfolio item added successfully.']);
            }

            $this->loadItems(); // Reload items
            $this->resetForm(); // Close form and reset state

        } catch (AuthorizationException $e) {
            Log::error('Authorization error saving portfolio item.', ['error' => $e->getMessage()]);
            session()->flash('toast', ['type' => 'error', 'message' => 'You are not authorized to perform this action.']);
        } catch (\Exception $e) {
            Log::error('Error saving portfolio item to database.', [
                'error' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 1000),
                'editingItemId' => $this->editingItemId,
                'data' => $data
            ]);
            session()->flash('toast', [
                'type' => 'error',
                'message' => 'An unexpected error occurred while saving the item. Please try again. Details: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteItem(int $itemId)
    {
        try {
            $item = PortfolioItem::findOrFail($itemId);
            $this->authorize('delete', $item);

            // Delete associated file from S3 if it's an audio item
            if ($item->item_type === PortfolioItem::TYPE_AUDIO && $item->file_path) {
                Log::info('Deleting S3 audio file for item', ['item_id' => $item->id, 'path' => $item->file_path]);
                Storage::disk('s3')->delete($item->file_path);
            }

            $item->delete();
            $this->loadItems(); // Reload items after deletion
            session()->flash('toast', [
                'type' => 'success',
                'message' => 'Portfolio item deleted'
            ]);
        } catch (AuthorizationException $e) {
             Log::warning('Authorization failed deleting portfolio item', ['item_id' => $itemId, 'user_id' => auth()->id()]);
             session()->flash('toast', [
                'type' => 'error',
                'message' => 'You are not authorized to perform this action'
             ]);
             // Re-dispatch for Livewire test helper if session flash isn't caught
             $this->dispatch('toast', type: 'error', message: 'You are not authorized to perform this action');
        } catch (\Exception $e) {
            Log::error("Error deleting portfolio item {$itemId}", ['error' => $e->getMessage()]);
            session()->flash('toast', [
                'type' => 'error',
                'message' => 'An error occurred while deleting the item'
            ]);
        }
    }

    public function updateSort(array $orderedIds)
    {
        try {
            Log::info('Received sort update', ['data' => $orderedIds]);
            
            // Ensure we only process items belonging to the authenticated user
            $userItems = PortfolioItem::where('user_id', auth()->id())->pluck('id')->toArray();
            
            // Update display_order based on the received order
            foreach ($orderedIds as $index => $item) {
                $itemId = $item['value'];
                
                // Verify this item belongs to the user
                if (in_array($itemId, $userItems)) {
                    PortfolioItem::where('id', $itemId)
                        ->update(['display_order' => $index + 1]); // Use 1-based index for order
                    
                    Log::info('Updated item order', [
                        'item_id' => $itemId,
                        'new_order' => $index + 1
                    ]);
                } else {
                    Log::warning('Attempted to update order for unauthorized item', [
                        'item_id' => $itemId,
                        'user_id' => auth()->id()
                    ]);
                }
            }
            
            $this->loadItems(); // Reload items to reflect new order
            session()->flash('toast', [
                'type' => 'success',
                'message' => 'Portfolio order updated'
            ]);
        } catch (\Exception $e) {
            Log::error("Error updating portfolio sort order: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data_received' => $orderedIds
            ]);
            session()->flash('toast', [
                'type' => 'error',
                'message' => 'An error occurred while updating the order: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.user.manage-portfolio-items', [
            'portfolioItems' => $this->portfolioItems,
            'availableProjects' => $this->availableProjects
        ]);
    }
}
