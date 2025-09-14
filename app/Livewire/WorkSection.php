<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\ServicePackage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WorkSection extends Component
{
    public string $filter = 'all';

    public function mount()
    {
        // Initialize with default filter
        $this->filter = 'all';
    }

    public function setFilter(string $filterType)
    {
        $this->filter = $filterType;
    }

    public function getWorkItemsProperty()
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        $workItems = new Collection;

        // Define active statuses
        $activeProjectStatuses = [
            Project::STATUS_UNPUBLISHED,
            Project::STATUS_OPEN,
            Project::STATUS_IN_PROGRESS,
            Project::STATUS_COMPLETED,
        ];

        $activePitchStatuses = [
            Pitch::STATUS_PENDING,
            Pitch::STATUS_IN_PROGRESS,
            Pitch::STATUS_READY_FOR_REVIEW,
            Pitch::STATUS_REVISIONS_REQUESTED,
            Pitch::STATUS_CONTEST_ENTRY,
            Pitch::STATUS_AWAITING_ACCEPTANCE,
            Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
            Pitch::STATUS_APPROVED,
            Pitch::STATUS_COMPLETED,
            Pitch::STATUS_CONTEST_WINNER,
            Pitch::STATUS_CONTEST_RUNNER_UP,
        ];

        $activeOrderStatuses = [
            Order::STATUS_PENDING_REQUIREMENTS,
            Order::STATUS_IN_PROGRESS,
            Order::STATUS_NEEDS_CLARIFICATION,
            Order::STATUS_READY_FOR_REVIEW,
            Order::STATUS_REVISIONS_REQUESTED,
        ];

        // Fetch owned projects
        $ownedProjects = Project::where('user_id', $user->id)
            ->whereIn('status', $activeProjectStatuses)
            ->with(['user', 'targetProducer', 'pitches'])
            ->latest('updated_at')
            ->get();

        // Filter out client management projects that have corresponding pitches
        $filteredProjects = $ownedProjects->filter(function ($project) use ($user) {
            if ($project->isClientManagement()) {
                $hasPitch = $project->pitches()->where('user_id', $user->id)->exists();

                return ! $hasPitch;
            }

            return true;
        });

        $workItems = $this->mergeWithTypeKeys($workItems, $filteredProjects);

        // Fetch orders as client
        $ordersAsClient = Order::where('client_user_id', $user->id)
            ->whereIn('status', $activeOrderStatuses)
            ->with(['client', 'producer', 'servicePackage'])
            ->latest('updated_at')
            ->get();
        $workItems = $this->mergeWithTypeKeys($workItems, $ordersAsClient);

        // Fetch client management projects where user is the client
        $clientProjects = Project::where(function ($query) use ($user) {
            $query->where('client_user_id', $user->id)
                ->orWhere('client_email', $user->email);
        })
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->whereIn('status', $activeProjectStatuses)
            ->with(['pitches' => function ($q) {
                $q->with(['user', 'files']);
            }])
            ->latest('updated_at')
            ->get();
        $workItems = $this->mergeWithTypeKeys($workItems, $clientProjects);

        // Fetch assigned pitches
        $assignedPitches = Pitch::where('user_id', $user->id)
            ->whereIn('status', $activePitchStatuses)
            ->with(['project', 'project.user', 'user'])
            ->latest('updated_at')
            ->get();
        $workItems = $this->mergeWithTypeKeys($workItems, $assignedPitches);

        // Fetch orders as producer
        $ordersAsProducer = Order::where('producer_user_id', $user->id)
            ->whereIn('status', $activeOrderStatuses)
            ->with(['client', 'producer', 'servicePackage'])
            ->latest('updated_at')
            ->get();
        $workItems = $this->mergeWithTypeKeys($workItems, $ordersAsProducer);

        // Fetch managed service packages
        $managedServices = ServicePackage::where('user_id', $user->id)
            ->with('user')
            ->latest('updated_at')
            ->get();
        $workItems = $this->mergeWithTypeKeys($workItems, $managedServices);

        // Sort all collected work items by last update time
        return $workItems->sortByDesc('updated_at');
    }

    /**
     * Merge collections using type+id as keys to prevent collisions
     * when merging collections of different model types.
     */
    private function mergeWithTypeKeys(Collection $target, Collection $source): Collection
    {
        $newCollection = new Collection;

        // First add all existing items from target with custom keys
        foreach ($target as $item) {
            $modelType = class_basename(get_class($item));
            $customKey = $modelType.'-'.$item->id;
            $newCollection->put($customKey, $item);
        }

        // Then add all items from source with custom keys
        foreach ($source as $item) {
            $modelType = class_basename(get_class($item));
            $customKey = $modelType.'-'.$item->id;
            $newCollection->put($customKey, $item);
        }

        return $newCollection;
    }

    public function render()
    {
        return view('livewire.work-section', [
            'workItems' => $this->workItems,
        ]);
    }
}
