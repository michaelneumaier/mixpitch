<div class="delivery-pipeline">
<!-- Delivery Pipeline Header -->
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
    <div class="flex items-center gap-3">
      <flux:icon.truck variant="solid" class="w-6 h-6 sm:w-8 sm:h-8 text-blue-600 dark:text-blue-400" />
      <div>
        <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">Delivery Pipeline</flux:heading>
        <flux:subheading class="text-gray-600 dark:text-gray-400">Make • Review • Wrap Up</flux:subheading>
      </div>
    </div>
    
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
      <!-- Mobile Filter Toggle -->
      <div x-data="{ showFilters: false }" class="md:hidden w-full sm:w-auto">
        <flux:button @click="showFilters = !showFilters" variant="ghost" size="sm" class="w-full sm:w-auto">
          <flux:icon.funnel class="w-4 h-4" />
          Filters
          <flux:icon.chevron-down x-show="!showFilters" class="w-3 h-3 ml-1" />
          <flux:icon.chevron-up x-show="showFilters" class="w-3 h-3 ml-1" />
        </flux:button>
        
        <!-- Mobile Filter Panel -->
        <div x-show="showFilters" x-collapse class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg space-y-3">
          <flux:checkbox wire:click="toggleFilter('filterClientComments')" :checked="$filterClientComments" label="Client comments" />
          <flux:checkbox wire:click="toggleFilter('filterUnpaidMilestones')" :checked="$filterUnpaidMilestones" label="Unpaid milestones" />
          <flux:checkbox wire:click="toggleFilter('filterRevisionsRequested')" :checked="$filterRevisionsRequested" label="Revisions" />
          <flux:checkbox wire:click="toggleFilter('filterHasReminders')" :checked="$filterHasReminders" label="Has reminders" />
          
          <div class="flex items-center gap-2">
            <span class="text-gray-500 dark:text-gray-400 text-sm">Recent:</span>
            <select wire:model="recentClientCommentDays" class="flex-1 pl-2 pr-10 py-1 border border-gray-200 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900 dark:text-gray-100">
              <option value="3">3 days</option>
              <option value="7">7 days</option>
              <option value="14">14 days</option>
              <option value="30">30 days</option>
              <option value="0">All time</option>
            </select>
          </div>
        </div>
      </div>
      
      <!-- Desktop Filters -->
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
  <div class="space-y-4 md:grid md:grid-cols-3 md:gap-4 md:space-y-0">
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
        <div class="space-y-3 min-h-[120px]">
          @forelse(($columns[$key] ?? []) as $card)
            <flux:card class="kanban-card hover:shadow-md transition-shadow p-3 sm:p-4">
              <!-- Project Header -->
              <div class="flex items-center justify-between gap-2 mb-2">
                <flux:subheading class="font-medium truncate min-w-0" title="{{ $card['project_name'] }}">
                  {{ $card['project_name'] }}
                </flux:subheading>
                <flux:badge variant="neutral" size="xs" class="flex-shrink-0">#{{ $card['project_id'] }}</flux:badge>
              </div>
              
              <div class="text-xs text-gray-500 dark:text-gray-400 mb-3 truncate min-w-0">{{ $card['client_email'] }}</div>

              <!-- Status Badges -->
              <div class="flex flex-wrap gap-1 sm:gap-1.5 mb-3">
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
                  <div class="flex items-start gap-2 p-1.5 sm:p-2 bg-blue-50 dark:bg-blue-950 rounded text-xs">
                    <flux:icon.chat-bubble-left class="w-3 h-3 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                    <div class="flex-1 min-w-0 overflow-hidden">
                      <div class="truncate text-blue-700 dark:text-blue-300">{{ $card['last_client_comment_excerpt'] }}</div>
                      @if(!empty($card['last_client_comment_at']))
                        <div class="text-gray-500 dark:text-gray-400">{{ $card['last_client_comment_at'] }} ago</div>
                      @endif
                    </div>
                  </div>
                @endif
                
                @if(!empty($card['next_reminder_id']))
                  <div class="flex items-center gap-2 p-1.5 sm:p-2 bg-yellow-50 dark:bg-yellow-950 rounded text-xs">
                    <flux:icon.bell class="w-3 h-3 text-yellow-600 dark:text-yellow-400 flex-shrink-0" />
                    <div class="flex-1 min-w-0 overflow-hidden">
                      <div class="truncate text-yellow-700 dark:text-yellow-300">{{ $card['next_reminder_note'] }}</div>
                      @if(!empty($card['next_reminder_due_human']))
                        <div class="text-gray-500 dark:text-gray-400">{{ $card['next_reminder_due_human'] }}</div>
                      @endif
                    </div>
                    <flux:button wire:click="completeReminder({{ $card['next_reminder_id'] }})" variant="subtle" size="xs" class="flex-shrink-0">Done</flux:button>
                  </div>
                @endif
              </div>

              <!-- Action Buttons -->
              <div class="flex flex-wrap gap-1.5 sm:gap-2">
                <flux:button href="{{ route('projects.manage-client', $card['project_slug']) }}" variant="ghost" size="xs" class="flex-1 sm:flex-none">
                  Manage
                </flux:button>
                @if($key === 'make' && $card['files_total'] > 0)
                  <flux:button wire:click="submitForReview({{ $card['project_id'] }})" variant="primary" size="xs" class="flex-1 sm:flex-none">
                    <span class="hidden sm:inline">Submit for Review</span>
                    <span class="sm:hidden">Submit</span>
                  </flux:button>
                @endif
                <flux:button wire:click="openReminderModal({{ $card['project_id'] }})" variant="ghost" size="xs" class="px-2 sm:px-3">
                  <flux:icon.bell class="w-4 h-4" />
                  <span class="sr-only sm:not-sr-only sm:ml-1">Reminder</span>
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
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Date & Time</label>
            <input type="datetime-local" wire:model.defer="reminderDueAt" class="w-full px-3 py-2 border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
            @error('reminderDueAt')<div class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</div>@enderror
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Note</label>
            <textarea rows="4" wire:model.defer="reminderNote" class="w-full px-3 py-2 border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="What should we follow up on?"></textarea>
            @error('reminderNote')<div class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</div>@enderror
          </div>
        </div>
      </x-slot>
      <x-slot name="footer">
        <div class="flex flex-col sm:flex-row gap-2 sm:justify-end">
          <button type="button" wire:click="$set('showReminderModal', false)" class="w-full sm:w-auto px-4 py-2 text-sm rounded-md bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300">Cancel</button>
          <button type="button" wire:click="saveReminder" class="w-full sm:w-auto px-4 py-2 text-sm rounded-md bg-indigo-600 hover:bg-indigo-700 text-white">Save Reminder</button>
        </div>
      </x-slot>
    </x-dialog-modal>
  </template>
</div>

