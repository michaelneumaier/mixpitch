<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\Order;
use App\Models\ServicePackage;
use Illuminate\Database\Eloquent\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $workItems = new Collection();

        // Define active statuses (adjust as needed)
        $activeProjectStatuses = [Project::STATUS_OPEN, Project::STATUS_IN_PROGRESS];
        $activePitchStatuses = [
            Pitch::STATUS_PENDING, Pitch::STATUS_IN_PROGRESS, Pitch::STATUS_READY_FOR_REVIEW,
            Pitch::STATUS_REVISIONS_REQUESTED, Pitch::STATUS_CONTEST_ENTRY, Pitch::STATUS_AWAITING_ACCEPTANCE,
            Pitch::STATUS_CLIENT_REVISIONS_REQUESTED
        ];
        $activeOrderStatuses = [
            Order::STATUS_PENDING_REQUIREMENTS, Order::STATUS_IN_PROGRESS, Order::STATUS_NEEDS_CLARIFICATION,
            Order::STATUS_READY_FOR_REVIEW, Order::STATUS_REVISIONS_REQUESTED
        ];

        // --- Fetch Items Where User is the Owner/Client ---
        $ownedProjects = Project::where('user_id', $user->id)
            // ->whereIn('status', $activeProjectStatuses) // Optionally filter by status
            ->with(['user', 'targetProducer', 'pitches']) // Eager load pitches to check for client management
            ->latest('updated_at')
            ->get();

        // Filter out client management projects that have corresponding pitches
        // For client management, the pitch provides more detailed status info
        $filteredProjects = $ownedProjects->filter(function ($project) use ($user) {
            if ($project->isClientManagement()) {
                // For client management projects, only show the project if there's no corresponding pitch
                // This handles edge cases where the pitch creation might have failed
                $hasPitch = $project->pitches()->where('user_id', $user->id)->exists();
                return !$hasPitch;
            }
            // Show all non-client-management projects normally
            return true;
        });

        $workItems = $this->mergeWithTypeKeys($workItems, $filteredProjects);

        $ordersAsClient = Order::where('client_user_id', $user->id)
            ->whereIn('status', $activeOrderStatuses)
            ->with(['client', 'producer', 'servicePackage']) // Eager load relevant relations
            ->latest('updated_at')
            ->get();
        $workItems = $this->mergeWithTypeKeys($workItems, $ordersAsClient);

        // --- Fetch Items Where User is the Producer/Assignee ---
        $assignedPitches = Pitch::where('user_id', $user->id)
            ->whereIn('status', $activePitchStatuses)
            ->with(['project', 'project.user', 'user']) // Eager load relevant relations
            ->latest('updated_at')
            ->get();
        $workItems = $this->mergeWithTypeKeys($workItems, $assignedPitches);

        $ordersAsProducer = Order::where('producer_user_id', $user->id)
            ->whereIn('status', $activeOrderStatuses)
            ->with(['client', 'producer', 'servicePackage']) // Eager load relevant relations
            ->latest('updated_at')
            ->get();
        $workItems = $this->mergeWithTypeKeys($workItems, $ordersAsProducer);
        
        // --- Fetch Producer's Service Packages (if applicable) ---
        // Assuming producers might want to see their packages on the dashboard
        // Add role check if needed: if ($user->hasRole('producer')) { ... }
        $managedServices = ServicePackage::where('user_id', $user->id)
            // ->where('status', ...) // Optionally filter by status
            ->with('user') // Eager load relevant relations
            ->latest('updated_at')
            ->get();
        $workItems = $this->mergeWithTypeKeys($workItems, $managedServices);

        // Sort all collected work items by last update time
        $sortedWorkItems = $workItems->sortByDesc('updated_at');

        // Pass the sorted, combined collection to the view
        return view('dashboard', ['workItems' => $sortedWorkItems]);
    }
    
    /**
     * Merge collections using type+id as keys to prevent collisions
     * when merging collections of different model types.
     *
     * @param Collection $target The target collection
     * @param Collection $source The source collection to merge in
     * @return Collection The resulting merged collection
     */
    private function mergeWithTypeKeys(Collection $target, Collection $source): Collection
    {
        $newCollection = new Collection();
        
        // First add all existing items from target with custom keys
        foreach ($target as $item) {
            $modelType = class_basename(get_class($item));
            $customKey = $modelType . '-' . $item->id;
            $newCollection->put($customKey, $item);
        }
        
        // Then add all items from source with custom keys
        foreach ($source as $item) {
            $modelType = class_basename(get_class($item));
            $customKey = $modelType . '-' . $item->id;
            $newCollection->put($customKey, $item);
        }
        
        return $newCollection;
    }
}
