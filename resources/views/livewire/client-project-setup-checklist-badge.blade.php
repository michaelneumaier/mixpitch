@if($this->shouldShow)
    @php
        $requiredCount = count($this->groupedItems['required']);
        $optionalCount = count($this->groupedItems['optional']);
        $totalCount = $requiredCount + $optionalCount;
    @endphp

    @if($totalCount > 0)
        <flux:dropdown position="bottom" align="start">
            <!-- Badge Trigger -->
            <button type="button" class="flex items-center gap-1.5 hover:opacity-80 transition-opacity">
                <flux:icon name="clipboard-document-check" class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                <span class="font-medium text-purple-900 dark:text-purple-100">
                    {{ $requiredCount }} {{ $requiredCount === 1 ? 'task' : 'tasks' }}
                </span>
                @if($optionalCount > 0)
                    <span class="text-slate-500 dark:text-slate-400">•</span>
                    <span class="text-sm text-slate-600 dark:text-slate-400">
                        {{ $optionalCount }} optional
                    </span>
                @endif
            </button>

            <!-- Popover Content -->
            <flux:popover class="w-80 p-4 space-y-3">
                <div class="flex items-center gap-2 mb-3">
                    <flux:icon name="clipboard-document-check" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                    <flux:heading size="sm" class="text-gray-900 dark:text-gray-100">
                        Setup Tasks
                    </flux:heading>
                </div>

                @php
                    $grouped = $this->groupedItems;
                @endphp

                {{-- Required tasks --}}
                @if(count($grouped['required']) > 0)
                    <div>
                        <flux:subheading class="text-gray-700 dark:text-gray-300 mb-2">
                            Required
                        </flux:subheading>
                        <ul class="space-y-2">
                            @foreach($grouped['required'] as $item)
                                <li class="flex items-start gap-2">
                                    <div class="flex-shrink-0 mt-0.5">
                                        @if($item['status'] === 'ready')
                                            <flux:icon.paper-airplane variant="solid" class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                        @else
                                            <flux:icon.exclamation-circle variant="outline" class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                                        @endif
                                    </div>

                                    <div class="flex-1 text-sm">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $item['label'] }}
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Optional tasks --}}
                @if(count($grouped['optional']) > 0)
                    <div>
                        <flux:subheading class="text-gray-700 dark:text-gray-300 mb-2">
                            Optional
                        </flux:subheading>
                        <ul class="space-y-2">
                            @foreach($grouped['optional'] as $item)
                                <li class="flex items-start gap-2">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <flux:icon.information-circle variant="outline" class="w-4 h-4 text-gray-400" />
                                    </div>

                                    <div class="flex-1 text-sm">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $item['label'] }}
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </flux:popover>
        </flux:dropdown>
    @endif
@endif
