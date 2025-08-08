<div class="p-4">
  <div class="flex items-center justify-between mb-4">
    <div>
      <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-900 via-indigo-800 to-purple-800 bg-clip-text text-transparent">Delivery Pipeline</h2>
      <p class="text-xs text-gray-500">Make • Review • Wrap Up</p>
    </div>
    <div class="flex items-center gap-2">
      <div class="hidden md:flex items-center gap-3 text-xs">
        <label class="inline-flex items-center gap-1"><input type="checkbox" wire:click="toggleFilter('filterClientComments')" @checked($filterClientComments) /> Client comments</label>
        <label class="inline-flex items-center gap-1"><input type="checkbox" wire:click="toggleFilter('filterUnpaidMilestones')" @checked($filterUnpaidMilestones) /> Unpaid milestones</label>
        <label class="inline-flex items-center gap-1"><input type="checkbox" wire:click="toggleFilter('filterRevisionsRequested')" @checked($filterRevisionsRequested) /> Revisions</label>
        <label class="inline-flex items-center gap-1"><input type="checkbox" wire:click="toggleFilter('filterHasReminders')" @checked($filterHasReminders) /> Has reminders</label>
        <div class="inline-flex items-center gap-1">
          <span class="text-gray-500">Recent window:</span>
          <select wire:model="recentClientCommentDays" class="pl-2 pr-10 py-1 border border-gray-200 rounded-md bg-white text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <option value="3">3d</option>
            <option value="7">7d</option>
            <option value="14">14d</option>
            <option value="30">30d</option>
            <option value="0">All</option>
          </select>
        </div>
      </div>
      <button wire:click="loadBoard" class="px-3 py-1.5 text-sm rounded-md bg-white border border-gray-200 hover:bg-gray-50">Refresh</button>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @foreach($columnTitles as $key => $label)
      <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-4 py-2.5 border-b flex items-center justify-between
            @if($key==='make') bg-gradient-to-r from-blue-50 to-indigo-50 @elseif($key==='review') bg-gradient-to-r from-amber-50 to-orange-50 @else bg-gradient-to-r from-emerald-50 to-teal-50 @endif">
          <div class="text-sm font-semibold flex items-center gap-2">
            @if($key==='make')
              <span class="inline-block w-2 h-2 rounded-full bg-indigo-500"></span>
            @elseif($key==='review')
              <span class="inline-block w-2 h-2 rounded-full bg-orange-500"></span>
            @else
              <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
            @endif
            {{ $label }}
          </div>
          <div class="text-xs text-gray-500 flex items-center gap-2">
            @if($key === 'wrap')
              <span class="text-[10px] bg-yellow-50 text-yellow-700 px-2 py-0.5 rounded" title="Outstanding payments">
                ${{ number_format(($columnMeta['wrap']['outstanding_amount'] ?? 0), 2) }}
              </span>
            @endif
            <span>{{ isset($columns[$key]) ? count($columns[$key]) : 0 }}</span>
          </div>
        </div>
        <div class="p-3 space-y-3 min-h-[120px]" x-data x-init="
            () => {
              if (window.Sortable) {
                const el = $el;
                window.Sortable.create(el, {
                  group: 'delivery-board-3',
                  animation: 150,
                  onAdd: (evt) => {
                    const projectId = parseInt(evt.item.getAttribute('data-project-id'));
                    const fromStage = evt.from.getAttribute('data-stage');
                    const toStage = evt.to.getAttribute('data-stage');
                    Livewire.find($wire.__instance.id).call('handleDrop', projectId, toStage, fromStage);
                  },
                  onUpdate: (evt) => {
                    const ids = Array.from(el.querySelectorAll('[data-project-id]')).map(n => parseInt(n.getAttribute('data-project-id')));
                    const stage = el.getAttribute('data-stage');
                    Livewire.find($wire.__instance.id).call('reorderWithinColumn', stage, ids);
                  },
                });
              }
            }
          " data-stage="{{ $key }}">
          @forelse(($columns[$key] ?? []) as $card)
            <div class="border border-gray-200/70 rounded-xl p-4 hover:shadow-md transition" data-project-id="{{ $card['project_id'] }}">
              <div class="flex items-center justify-between">
                <div class="font-medium text-sm truncate" title="{{ $card['project_name'] }}">{{ $card['project_name'] }}</div>
                <span class="text-[10px] uppercase tracking-wide text-gray-500">#{{ $card['project_id'] }}</span>
              </div>
              <div class="mt-1 text-xs text-gray-500 truncate">{{ $card['client_email'] }}</div>

              <div class="mt-2 flex flex-wrap gap-1.5">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] bg-gray-100 text-gray-700">
                  Files {{ $card['files_approved'] }}/{{ $card['files_total'] }}
                </span>
                @if($card['milestones_unpaid'] > 0)
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] bg-yellow-100 text-yellow-800">
                    {{ $card['milestones_unpaid'] }} unpaid milestones
                  </span>
                @elseif($card['milestones_paid'] > 0)
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] bg-green-100 text-green-800">
                    {{ $card['milestones_paid'] }} milestones paid
                  </span>
                @endif
                @if(($card['client_comments_total'] ?? 0) > 0)
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] bg-blue-100 text-blue-800" title="Unresolved/Total client comments">
                    {{ $card['client_comments_unresolved'] ?? 0 }}/{{ $card['client_comments_total'] }} client comments
                  </span>
                @endif
                @if(($card['client_uploads'] ?? 0) > 0)
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] bg-sky-100 text-sky-800">
                    Client uploads
                  </span>
                @endif
                @if($card['revisions_requested'])
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] bg-purple-100 text-purple-800">
                    Revisions requested
                  </span>
                @endif
                @if($card['overdue_reminders'] > 0)
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] bg-red-100 text-red-800">
                    {{ $card['overdue_reminders'] }} overdue
                  </span>
                @elseif($card['upcoming_reminders'] > 0)
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] bg-indigo-100 text-indigo-800">
                    {{ $card['upcoming_reminders'] }} upcoming
                  </span>
                @endif
              </div>
              <div class="mt-2 flex flex-wrap items-center gap-2 text-[10px] text-gray-500">
                @if(!empty($card['time_in_stage']))
                  <span>In stage {{ $card['time_in_stage'] }}</span>
                @endif
                @if(!empty($card['last_client_comment_excerpt']))
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-blue-50 text-blue-700" title="Latest client comment">
                    <i class="fas fa-comment-alt text-[9px]"></i>
                    <span class="truncate max-w-[220px]">{{ $card['last_client_comment_excerpt'] }}</span>
                    @if(!empty($card['last_client_comment_at']))
                      <span class="text-[9px] text-gray-400 ml-1">{{ $card['last_client_comment_at'] }} ago</span>
                    @endif
                  </span>
                @endif
                @if(!empty($card['next_reminder_id']))
                  <span class="inline-flex items-center gap-2 px-2 py-0.5 rounded bg-yellow-50 text-yellow-800" title="Upcoming reminder">
                    <i class="fas fa-bell text-[9px]"></i>
                    <span class="truncate max-w-[220px]">{{ $card['next_reminder_note'] }}</span>
                    @if(!empty($card['next_reminder_due_human']))
                      <span class="text-[9px] ml-1">{{ $card['next_reminder_due_human'] }}</span>
                    @endif
                    <button wire:click="completeReminder({{ $card['next_reminder_id'] }})" class="ml-2 px-1.5 py-0.5 text-[10px] rounded bg-yellow-100 hover:bg-yellow-200 border border-yellow-200">Done</button>
                  </span>
                @endif
              </div>

               <div class="mt-3 flex items-center gap-2">
                 <a href="{{ route('projects.manage-client', $card['project_id']) }}" class="px-2 py-1 text-xs rounded-md bg-white border border-gray-200 hover:bg-gray-50">Manage</a>
                 @if($key === 'make' && $card['files_total'] > 0)
                   <button wire:click="submitForReview({{ $card['project_id'] }})" class="px-2 py-1 text-xs rounded-md bg-purple-600 text-white hover:bg-purple-700">Submit for Review</button>
                 @endif
                 <button wire:click="openReminderModal({{ $card['project_id'] }})" class="px-2 py-1 text-xs rounded-md bg-white border border-gray-200 hover:bg-gray-50">Add Reminder</button>
               </div>
            </div>
          @empty
            <div class="text-xs text-gray-500">No items</div>
          @endforelse
          @if(count($columns[$key] ?? []) >= ($limits[$key] ?? 20))
            <div class="pt-2">
              <button wire:click="loadMore('{{ $key }}')" class="w-full px-3 py-2 text-xs rounded-md bg-white border border-gray-200 hover:bg-gray-50">Load more</button>
            </div>
          @endif
        </div>
      </div>
    @endforeach
  </div>
  
  <!-- Reminder Modal (teleported to <body> for proper fullscreen overlay) -->
  <template x-teleport="body">
    <x-dialog-modal wire:model="showReminderModal" maxWidth="lg">
      <x-slot name="title">
        Add Reminder
      </x-slot>
      <x-slot name="content">
        <div class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-gray-700">Due</label>
            <input type="datetime-local" wire:model.defer="reminderDueAt" class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            @error('reminderDueAt')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700">Note</label>
            <textarea rows="4" wire:model.defer="reminderNote" class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="What should we follow up on?"></textarea>
            @error('reminderNote')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
          </div>
        </div>
      </x-slot>
      <x-slot name="footer">
        <button type="button" wire:click="$set('showReminderModal', false)" class="px-3 py-1.5 text-sm rounded-md bg-white border border-gray-200 hover:bg-gray-50 mr-2">Cancel</button>
        <button type="button" wire:click="saveReminder" class="px-3 py-1.5 text-sm rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Save Reminder</button>
      </x-slot>
    </x-dialog-modal>
  </template>
</div>


