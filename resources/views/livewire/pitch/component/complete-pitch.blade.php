<div>
    <!-- Button to open completion modal with consistent styling -->
    @if($pitch->status === \App\Models\Pitch::STATUS_APPROVED && auth()->id() === $pitch->project->user_id)
    <div class="{{ request()->routeIs('projects.manage') ? '' : 'mt-4' }}">
        <button type="button" wire:click="openCompletionModal"
            class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 px-4 py-2.5 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-green-700 hover:to-emerald-700 hover:shadow-lg">
            <i class="fas fa-check-circle mr-2"></i>Complete Pitch
        </button>
    </div>
    @endif
</div>