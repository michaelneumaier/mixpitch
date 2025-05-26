<div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg border border-gray-200">
    <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Contest Entries ({{ $entries->total() }})
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
            Review entries submitted for this contest.
        </p>
    </div>
    <div class="border-t border-gray-200">
        @if($entries->isEmpty())
            <div class="text-center py-10 text-gray-500">
                No entries submitted yet.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Producer</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                            <tr class="@if($entry->status === \App\Models\Pitch::STATUS_CONTEST_WINNER) bg-success bg-opacity-10 @elseif($entry->status === \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP) bg-info bg-opacity-10 @endif">
                                <td>{{ $entry->user->name }}</td>
                                <td>{{ $entry->created_at->format('M j, Y') }}</td>
                                <td>
                                    @if($entry->status === \App\Models\Pitch::STATUS_CONTEST_WINNER)
                                        <span class="badge badge-success">Winner</span>
                                    @elseif($entry->status === \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP)
                                        <span class="badge badge-info">Runner-up (Rank: {{ $entry->rank }})</span>
                                    @elseif($entry->status === \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED)
                                        <span class="badge badge-secondary">Not Selected</span>
                                    @else
                                        <span class="badge badge-primary">Entry</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex space-x-2">
                                        @if(!$winnerExists && $entry->status === \App\Models\Pitch::STATUS_CONTEST_ENTRY && $canSelectWinner)
                                            <button wire:click="selectWinner({{ $entry->id }})" 
                                                    class="btn btn-success btn-sm"
                                                    onclick="return confirm('Are you sure you want to select this entry as the winner? This action cannot be undone.')">
                                                Select Winner
                                            </button>
                                        @endif
                                        
                                        @if($winnerExists && $entry->status === \App\Models\Pitch::STATUS_CONTEST_ENTRY)
                                            <div class="flex items-center space-x-2">
                                                <input type="number" wire:model="rankToAssign" min="2" class="input input-bordered input-sm w-16" />
                                                <button wire:click="selectRunnerUp({{ $entry->id }})" 
                                                        class="btn btn-info btn-sm"
                                                        onclick="return confirm('Are you sure you want to select this entry as runner-up with rank {{ $rankToAssign }}?')">
                                                    Select Runner-up
                                                </button>
                                            </div>
                                        @endif
                                        
                                        <a href="{{ route('projects.pitches.show', ['project' => $project, 'pitch' => $entry]) }}" 
                                           class="btn btn-primary btn-sm">
                                            View Entry
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination Links --}}
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
</div> 