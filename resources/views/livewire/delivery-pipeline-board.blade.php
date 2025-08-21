<div>
<!-- Delivery Pipeline Header -->
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-2">
    <div class="flex items-center gap-3">
      <flux:icon.truck variant="solid" class="w-8 h-8 text-blue-600 dark:text-blue-400" />
      <div>
        <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">Delivery Pipeline</flux:heading>
        <flux:subheading class="text-gray-600 dark:text-gray-400">Make • Review • Wrap Up</flux:subheading>
      </div>
    </div>
    
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
      <!-- Filter Checkboxes -->
      <div class="hidden md:flex items-center gap-4 text-sm">
        <flux:checkbox wire:click="toggleFilter('filterClientComments')" :checked="$filterClientComments" label="Client comments" />
        <flux:checkbox wire:click="toggleFilter('filterUnpaidMilestones')" :checked="$filterUnpaidMilestones" label="Unpaid milestones" />
        <flux:checkbox wire:click="toggleFilter('filterRevisionsRequested')" :checked="$filterRevisionsRequested" label="Revisions" />
        <flux:checkbox wire:click="toggleFilter('filterHasReminders')" :checked="$filterHasReminders" label="Has reminders" />
        
        <div class="flex items-center gap-2">
          <span class="text-gray-500 dark:text-gray-400 text-sm">Recent window:</span>
          <select wire:model="recentClientCommentDays" class="pl-2 pr-10 py-1 border border-gray-200 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900 dark:text-gray-100">
            <option value="3">3d</option>
            <option value="7">7d</option>
            <option value="14">14d</option>
            <option value="30">30d</option>
            <option value="0">All</option>
          </select>
        </div>
      </div>
      
      <flux:button wire:click="loadBoard" variant="ghost" size="sm" icon="arrow-path">Refresh</flux:button>
    </div>
  </div>

  <!-- Kanban Board -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @foreach($columnTitles as $key => $label)
      <flux:card class="{{ match($key) {
        'make' => 'bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800',
        'review' => 'bg-amber-50 dark:bg-amber-950 border-amber-200 dark:border-amber-800',
        'wrap' => 'bg-emerald-50 dark:bg-emerald-950 border-emerald-200 dark:border-emerald-800',
        default => 'bg-gray-50 dark:bg-gray-950 border-gray-200 dark:border-gray-800'
      } }}">
        <!-- Column Header -->
        <div class="flex items-center justify-between mb-4 p-4 -m-4 mb-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="w-3 h-3 rounded-full {{ match($key) {
              'make' => 'bg-indigo-500',
              'review' => 'bg-orange-500',
              'wrap' => 'bg-emerald-500',
              default => 'bg-gray-500'
            } }}"></div>
            <flux:subheading class="font-semibold {{ match($key) {
              'make' => 'text-blue-700 dark:text-blue-300',
              'review' => 'text-amber-700 dark:text-amber-300',
              'wrap' => 'text-emerald-700 dark:text-emerald-300',
              default => 'text-gray-700 dark:text-gray-300'
            } }}">{{ $label }}</flux:subheading>
          </div>
          <div class="flex items-center gap-2">
            @if($key === 'wrap')
              <flux:badge variant="warning" size="xs">
                ${{ number_format(($columnMeta['wrap']['outstanding_amount'] ?? 0), 2) }}
              </flux:badge>
            @endif
            <flux:badge variant="neutral" size="xs">
              {{ isset($columns[$key]) ? count($columns[$key]) : 0 }}
            </flux:badge>
          </div>
        </div>
        <!-- Column Content -->
        <div class="space-y-3 min-h-[120px]" x-data x-init="
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
            <flux:card class="hover:shadow-md transition-shadow cursor-move" data-project-id="{{ $card['project_id'] }}">
              <!-- Project Header -->
              <div class="flex items-center justify-between mb-2">
                <flux:subheading class="font-medium truncate" title="{{ $card['project_name'] }}">
                  {{ $card['project_name'] }}
                </flux:subheading>
                <flux:badge variant="neutral" size="xs">#{{ $card['project_id'] }}</flux:badge>
              </div>
              
              <div class="text-xs text-gray-500 dark:text-gray-400 mb-3 truncate">{{ $card['client_email'] }}</div>

              <!-- Status Badges -->
              <div class="flex flex-wrap gap-1.5 mb-3">
                <flux:badge variant="neutral" size="xs">
                  Files {{ $card['files_approved'] }}/{{ $card['files_total'] }}
                </flux:badge>
                
                @if($card['milestones_unpaid'] > 0)
                  <flux:badge variant="warning" size="xs">
                    {{ $card['milestones_unpaid'] }} unpaid milestones
                  </flux:badge>
                @elseif($card['milestones_paid'] > 0)
                  <flux:badge variant="success" size="xs">
                    {{ $card['milestones_paid'] }} milestones paid
                  </flux:badge>
                @endif
                
                @if(($card['client_comments_total'] ?? 0) > 0)
                  <flux:badge variant="info" size="xs" title="Unresolved/Total client comments">
                    {{ $card['client_comments_unresolved'] ?? 0 }}/{{ $card['client_comments_total'] }} comments
                  </flux:badge>
                @endif
                
                @if(($card['client_uploads'] ?? 0) > 0)
                  <flux:badge variant="info" size="xs">Client uploads</flux:badge>
                @endif
                
                @if($card['revisions_requested'])
                  <flux:badge variant="warning" size="xs">Revisions requested</flux:badge>
                @endif
                
                @if($card['overdue_reminders'] > 0)
                  <flux:badge variant="danger" size="xs">{{ $card['overdue_reminders'] }} overdue</flux:badge>
                @elseif($card['upcoming_reminders'] > 0)
                  <flux:badge variant="info" size="xs">{{ $card['upcoming_reminders'] }} upcoming</flux:badge>
                @endif
              </div>
              
              <!-- Additional Info -->
              <div class="space-y-2 mb-3">
                @if(!empty($card['time_in_stage']))
                  <div class="text-xs text-gray-500 dark:text-gray-400">In stage {{ $card['time_in_stage'] }}</div>
                @endif
                
                @if(!empty($card['last_client_comment_excerpt']))
                  <div class="flex items-start gap-2 p-2 bg-blue-50 dark:bg-blue-950 rounded text-xs">
                    <flux:icon.chat-bubble-left class="w-3 h-3 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                    <div class="flex-1 min-w-0">
                      <div class="truncate text-blue-700 dark:text-blue-300">{{ $card['last_client_comment_excerpt'] }}</div>
                      @if(!empty($card['last_client_comment_at']))
                        <div class="text-gray-500 dark:text-gray-400">{{ $card['last_client_comment_at'] }} ago</div>
                      @endif
                    </div>
                  </div>
                @endif
                
                @if(!empty($card['next_reminder_id']))
                  <div class="flex items-center gap-2 p-2 bg-yellow-50 dark:bg-yellow-950 rounded text-xs">
                    <flux:icon.bell class="w-3 h-3 text-yellow-600 dark:text-yellow-400 flex-shrink-0" />
                    <div class="flex-1 min-w-0">
                      <div class="truncate text-yellow-700 dark:text-yellow-300">{{ $card['next_reminder_note'] }}</div>
                      @if(!empty($card['next_reminder_due_human']))
                        <div class="text-gray-500 dark:text-gray-400">{{ $card['next_reminder_due_human'] }}</div>
                      @endif
                    </div>
                    <flux:button wire:click="completeReminder({{ $card['next_reminder_id'] }})" variant="subtle" size="xs">Done</flux:button>
                  </div>
                @endif
              </div>

              <!-- Action Buttons -->
              <div class="flex items-center gap-2">
                <flux:button href="{{ route('projects.manage-client', $card['project_slug']) }}" variant="ghost" size="xs">
                  Manage
                </flux:button>
                @if($key === 'make' && $card['files_total'] > 0)
                  <flux:button wire:click="submitForReview({{ $card['project_id'] }})" variant="primary" size="xs">
                    Submit for Review
                  </flux:button>
                @endif
                <flux:button wire:click="openReminderModal({{ $card['project_id'] }})" variant="ghost" size="xs" icon="bell">
                  Reminder
                </flux:button>
              </div>
            </flux:card>
          @empty
            <div class="text-xs text-gray-500 dark:text-gray-400 text-center py-8">No items</div>
          @endforelse
          
          @if(count($columns[$key] ?? []) >= ($limits[$key] ?? 20))
            <div class="pt-2">
              <flux:button wire:click="loadMore('{{ $key }}')" variant="ghost" size="sm" class="w-full">
                Load more
              </flux:button>
            </div>
          @endif
        </div>
      </flux:card>
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
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date & Time</label>
            <input type="datetime-local" wire:model.defer="reminderDueAt" class="mt-1 w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
            @error('reminderDueAt')<div class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</div>@enderror
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Note</label>
            <textarea rows="4" wire:model.defer="reminderNote" class="mt-1 w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="What should we follow up on?"></textarea>
            @error('reminderNote')<div class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</div>@enderror
          </div>
        </div>
      </x-slot>
      <x-slot name="footer">
        <button type="button" wire:click="$set('showReminderModal', false)" class="px-4 py-2 text-sm rounded-md bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 mr-2 text-gray-700 dark:text-gray-300">Cancel</button>
        <button type="button" wire:click="saveReminder" class="px-4 py-2 text-sm rounded-md bg-indigo-600 hover:bg-indigo-700 text-white">Save Reminder</button>
      </x-slot>
    </x-dialog-modal>
  </template>
</div>

