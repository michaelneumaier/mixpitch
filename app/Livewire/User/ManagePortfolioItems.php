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
    public string $itemType = 'audio_upload'; // Default type

    #[Rule('required|string|max:255')]
    public string $title = '';

    #[Rule('nullable|string')]
    public string $description = '';

    // Specific fields for types
    #[Rule('nullable|file|mimes:mp3,wav|max:102400')] // Remove required_if which was causing validation issues
    public $audioFile = null;

    #[Rule('required_if:itemType,external_link|nullable|url|max:255')]
    public string $externalUrl = '';

    #[Rule('required_if:itemType,mixpitch_project_link|nullable|integer|exists:projects,id')]
    public ?int $linkedProjectId = null;

    public ?string $existingFilePath = null;

    #[Rule('boolean')]
    public bool $isPublic = true;

    public $items = [];

    public function mount()
    {
        $this->user = Auth::user();
        if (!$this->user) {
            // Handle unauthorized access appropriately
            abort(403, 'You need to be logged in to manage portfolios.');
        }
        $this->loadItems();
        $this->loadAvailableProjects();
    }

    public function loadItems()
    {
        // Load items ordered by display_order
        $this->portfolioItems = $this->user->portfolioItems()->orderBy('display_order')->get();
    }

    public function loadAvailableProjects()
    {
        // Load available projects for selection
        $this->availableProjects = Project::all();
    }

    public function resetForm()
    {
        $this->reset([
            'editingItemId', 'itemType', 'title', 'description', 
            'audioFile', 'externalUrl', 'linkedProjectId', 'isPublic'
        ]);
        $this->showForm = false;
        $this->resetValidation();
        // Ensure temp uploaded file is cleaned up if form is cancelled
        if ($this->audioFile) {
            $this->audioFile->delete();
        }
    }

    public function addItem()
    {
        $this->resetForm();
        $this->itemType = 'audio_upload';
        $this->isPublic = true;
        $this->showForm = true;
    }

    public function editItem(int $itemId)
    {
        $item = PortfolioItem::findOrFail($itemId);
        $this->authorize('update', $item);

        $this->resetForm(); // Clear any previous state
        $this->editingItemId = $item->id;
        $this->itemType = $item->item_type;
        $this->title = $item->title;
        $this->description = $item->description ?? '';
        $this->externalUrl = $item->external_url ?? '';
        $this->linkedProjectId = $item->linked_project_id;
        $this->isPublic = $item->is_public;
        $this->existingFilePath = $item->file_path;
        // Do not pre-fill $this->audioFile - user must re-upload to change.
        $this->showForm = true;
    }

    public function saveItem()
    {
        // For audio uploads, validate file requirements
        if ($this->itemType === 'audio_upload') {
            // If editing and there's an existing file path, a new file is optional
            // If creating a new item, a file is required
            if (!$this->editingItemId && !$this->audioFile) {
                $this->addError('audioFile', 'Please select an audio file to upload.');
                return;
            }
        }

        $this->validate();

        $data = [
            'user_id' => auth()->id(),
            'title' => $this->title,
            'description' => $this->description ?? '',
            'item_type' => $this->itemType,
            'is_public' => $this->isPublic,
            'external_url' => null,
            'file_path' => null,
            'linked_project_id' => null,
        ];

        Log::info('Portfolio item save attempt', [
            'user_id' => auth()->id(),
            'title' => $this->title,
            'item_type' => $this->itemType,
            'has_audio_file' => !is_null($this->audioFile),
            'editing_item_id' => $this->editingItemId
        ]);

        // Keep existing file path if we're editing and no new file is uploaded
        if ($this->itemType === 'audio_upload' && $this->editingItemId && !$this->audioFile && $this->existingFilePath) {
            $data['file_path'] = $this->existingFilePath;
        }

        // Handle file upload for 'audio_upload' type
        if ($this->itemType === 'audio_upload' && $this->audioFile) {
            try {
                Log::info('Processing audio file upload', ['original_name' => $this->audioFile->getClientOriginalName()]);
                
                $originalName = pathinfo($this->audioFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeName = Str::slug($originalName) . '-' . time();
                $extension = $this->audioFile->getClientOriginalExtension();
                $filePath = "portfolio-audio/{$data['user_id']}/{$safeName}.{$extension}";
                
                // Store the file on S3
                Log::info('Attempting to store file on S3', ['path' => $filePath]);
                
                $this->audioFile->storeAs('/', $filePath, 's3'); // Store in the root of the bucket path defined
                $data['file_path'] = $filePath;
                
                Log::info('File stored successfully', ['path' => $filePath]);
            } catch (\Exception $e) {
                Log::error('Error uploading audio file', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                session()->flash('toast', [
                    'type' => 'error',
                    'message' => 'Error uploading audio file: ' . $e->getMessage()
                ]);
                return;
            }
        } elseif ($this->itemType === 'external_link') {
            $data['external_url'] = $this->externalUrl;
            Log::info('External link item', ['url' => $this->externalUrl]);
        } elseif ($this->itemType === 'mixpitch_project_link') {
            $data['linked_project_id'] = $this->linkedProjectId;
            Log::info('Project link item', ['project_id' => $this->linkedProjectId]);
        }

        try {
            if ($this->editingItemId) {
                $item = PortfolioItem::findOrFail($this->editingItemId);
                $this->authorize('update', $item); // Authorize update

                // If updating an audio item and a new file is uploaded, delete the old one
                if ($this->itemType === 'audio_upload' && $this->audioFile && $item->file_path) {
                    Storage::disk('s3')->delete($item->file_path);
                } elseif ($this->itemType !== 'audio_upload' && $item->file_path) {
                    // If changing type away from audio, delete the old file
                     Storage::disk('s3')->delete($item->file_path);
                     $data['file_path'] = null; // Ensure file path is cleared if type changes
                }

                Log::info('Updating portfolio item', ['id' => $item->id, 'data' => $data]);
                $item->update($data);
                Log::info('Portfolio item updated successfully', ['id' => $item->id]);
            } else {
                // Set initial display order for new items
                $maxOrder = PortfolioItem::where('user_id', auth()->id())->max('display_order');
                $data['display_order'] = ($maxOrder ?? 0) + 1;
                
                Log::info('About to authorize portfolio item creation');
                $this->authorize('create', [PortfolioItem::class]); // Authorize create
                
                Log::info('Creating new portfolio item', ['data' => $data]);
                $item = PortfolioItem::create($data);
                Log::info('Portfolio item created successfully', ['id' => $item->id]);
            }

            session()->flash('toast', [
                'type' => 'success',
                'message' => 'Portfolio item saved successfully'
            ]);
            $this->resetForm();
            $this->loadItems();

        } catch (AuthorizationException $e) {
             Log::error('Authorization exception', ['error' => $e->getMessage()]);
             session()->flash('toast', [
                'type' => 'error',
                'message' => 'You are not authorized to perform this action: ' . $e->getMessage()
             ]);
        } catch (\Exception $e) {
            Log::error("Error saving portfolio item", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('toast', [
                'type' => 'error',
                'message' => 'An error occurred while saving the item: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteItem(int $itemId)
    {
        try {
            $item = PortfolioItem::findOrFail($itemId);
            $this->authorize('delete', $item);

            // Delete associated file from S3 if it's an audio upload
            if ($item->item_type === 'audio_upload' && $item->file_path) {
                Storage::disk('s3')->delete($item->file_path);
            }

            $item->delete();
            $this->loadItems(); // Reload items after deletion
            session()->flash('toast', [
                'type' => 'success',
                'message' => 'Portfolio item deleted'
            ]);
        } catch (AuthorizationException $e) {
             session()->flash('toast', [
                'type' => 'error',
                'message' => 'You are not authorized to perform this action'
             ]);
        } catch (\Exception $e) {
            Log::error("Error deleting portfolio item {$itemId}: " . $e->getMessage());
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
