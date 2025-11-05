<div x-show="$wire.shouldShow">
    <flux:callout icon="clipboard-document-check" color="blue" class="mb-2">
        <div x-data="{ expanded: false }">
            {{-- Header - Collapsed by default --}}
            <div class="flex items-center justify-between gap-4 cursor-pointer" @click="expanded = !expanded">
                <div class="flex-1">
                    @php
                        $requiredCount = count($this->groupedItems['required']);
                        $optionalCount = count($this->groupedItems['optional']);
                        $totalCount = $requiredCount + $optionalCount;
                    @endphp

                    <flux:heading size="sm" class="text-gray-900 dark:text-gray-100">
                        @if($requiredCount > 0 && $optionalCount > 0)
                            {{ $requiredCount }} {{ $requiredCount === 1 ? 'task' : 'tasks' }} to complete, {{ $optionalCount }} optional
                        @elseif($requiredCount > 0)
                            {{ $requiredCount }} {{ $requiredCount === 1 ? 'task' : 'tasks' }} to complete before publishing
                        @elseif($optionalCount > 0)
                            {{ $optionalCount }} optional {{ $optionalCount === 1 ? 'task' : 'tasks' }} remaining
                        @else
                            Setup tasks
                        @endif
                    </flux:heading>
                </div>

                {{-- Toggle icon --}}
                <flux:icon.chevron-down variant="outline" class="w-5 h-5 text-gray-500 transition-transform" ::class="{ 'rotate-180': expanded }" />
            </div>

            {{-- Expandable content --}}
            <div x-show="expanded" x-collapse class="-ml-8 md:-ml-0">
                <div class="mt-4 space-y-4">
                    @php
                        $grouped = $this->groupedItems;
                    @endphp

                    {{-- Required tasks --}}
                    @if(count($grouped['required']) > 0)
                        <div>
                            <flux:subheading class="text-gray-700 dark:text-gray-300 mb-2">
                                Required
                            </flux:subheading>
                            <ul class="space-y-2 ml-1">
                                @foreach($grouped['required'] as $item)
                                    <li class="flex items-start gap-2">
                                        {{-- Icon based on status --}}
                                        <div class="flex-shrink-0 mt-0.5">
                                            @if($item['status'] === 'ready')
                                                <flux:icon.check-circle variant="solid" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                            @elseif($item['status'] === 'blocked')
                                                <flux:icon.lock-closed variant="outline" class="w-5 h-5 text-gray-400" />
                                            @else
                                                <flux:icon.exclamation-circle variant="outline" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                                            @endif
                                        </div>

                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $item['label'] }}
                                            </div>
                                            @if(isset($item['description']))
                                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                                                    {{ $item['description'] }}
                                                </div>
                                            @endif
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
                            <ul class="space-y-2 ml-1">
                                @foreach($grouped['optional'] as $item)
                                    <li class="flex items-start gap-2">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <flux:icon.information-circle variant="outline" class="w-5 h-5 text-gray-400" />
                                        </div>

                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $item['label'] }}
                                            </div>
                                            @if(isset($item['description']))
                                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                                                    {{ $item['description'] }}
                                                </div>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </flux:callout>
</div>
