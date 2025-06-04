@props(['pitch'])

@php
    $project = $pitch->project;
    $statusConfig = [
        'completed' => [
            'bg' => 'bg-green-50', 
            'border' => 'border-green-200/50', 
            'text' => 'text-green-700', 
            'textBold' => 'text-green-900',
            'icon' => 'fa-trophy',
            'iconColor' => 'text-green-600'
        ],
        'approved' => [
            'bg' => 'bg-blue-50', 
            'border' => 'border-blue-200/50', 
            'text' => 'text-blue-700', 
            'textBold' => 'text-blue-900',
            'icon' => 'fa-thumbs-up',
            'iconColor' => 'text-blue-600'
        ],
        'closed' => [
            'bg' => 'bg-gray-50', 
            'border' => 'border-gray-200/50', 
            'text' => 'text-gray-700', 
            'textBold' => 'text-gray-900',
            'icon' => 'fa-lock',
            'iconColor' => 'text-gray-600'
        ],
        'denied' => [
            'bg' => 'bg-red-50', 
            'border' => 'border-red-200/50', 
            'text' => 'text-red-700', 
            'textBold' => 'text-red-900',
            'icon' => 'fa-times-circle',
            'iconColor' => 'text-red-600'
        ],
        'pending' => [
            'bg' => 'bg-yellow-50', 
            'border' => 'border-yellow-200/50', 
            'text' => 'text-yellow-700', 
            'textBold' => 'text-yellow-900',
            'icon' => 'fa-clock',
            'iconColor' => 'text-yellow-600'
        ],
        'in_progress' => [
            'bg' => 'bg-blue-50', 
            'border' => 'border-blue-200/50', 
            'text' => 'text-blue-700', 
            'textBold' => 'text-blue-900',
            'icon' => 'fa-spinner',
            'iconColor' => 'text-blue-600'
        ],
        'pending_review' => [
            'bg' => 'bg-purple-50', 
            'border' => 'border-purple-200/50', 
            'text' => 'text-purple-700', 
            'textBold' => 'text-purple-900',
            'icon' => 'fa-search',
            'iconColor' => 'text-purple-600'
        ],
        'ready_for_review' => [
            'bg' => 'bg-indigo-50', 
            'border' => 'border-indigo-200/50', 
            'text' => 'text-indigo-700', 
            'textBold' => 'text-indigo-900',
            'icon' => 'fa-clipboard-check',
            'iconColor' => 'text-indigo-600'
        ],
        'revisions_requested' => [
            'bg' => 'bg-amber-50', 
            'border' => 'border-amber-200/50', 
            'text' => 'text-amber-700', 
            'textBold' => 'text-amber-900',
            'icon' => 'fa-pencil-alt',
            'iconColor' => 'text-amber-600'
        ],
    ];
    $config = $statusConfig[$pitch->status] ?? [
        'bg' => 'bg-gray-50', 
        'border' => 'border-gray-200/50', 
        'text' => 'text-gray-700', 
        'textBold' => 'text-gray-900',
        'icon' => 'fa-info-circle',
        'iconColor' => 'text-gray-600'
    ];
    
    $submissionCount = $pitch->snapshots->count();
    $fileCount = $pitch->files->count();
    $lastActivity = $pitch->updated_at;
@endphp

<div class="bg-gradient-to-br from-white/95 to-gray-50/90 backdrop-blur-sm border border-white/50 rounded-2xl p-6 shadow-lg">
    <div class="flex items-center mb-4">
        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl mr-3">
            <i class="fas fa-chart-line text-white"></i>
        </div>
        <div>
            <h3 class="text-lg font-bold text-gray-800">Pitch Overview</h3>
            <p class="text-sm text-gray-600">Key metrics and status</p>
        </div>
    </div>

    <div class="space-y-4">
        <!-- Pitch Status -->
        <div class="{{ $config['bg'] }} border {{ $config['border'] }} rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium {{ $config['text'] }} uppercase tracking-wide">Status</span>
                <i class="fas {{ $config['icon'] }} {{ $config['iconColor'] }}"></i>
            </div>
            <div class="text-sm font-bold {{ $config['textBold'] }}">
                {{ ucwords(str_replace('_', ' ', $pitch->status)) }}
            </div>
        </div>

        <!-- Submissions Count -->
        <div class="bg-purple-50 border border-purple-200/50 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-purple-700 uppercase tracking-wide">Submissions</span>
                <i class="fas fa-code-branch text-purple-600"></i>
            </div>
            <div class="text-sm font-bold text-purple-900">{{ $submissionCount }}</div>
        </div>

        <!-- Files Count -->
        <div class="bg-indigo-50 border border-indigo-200/50 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-indigo-700 uppercase tracking-wide">Files</span>
                <i class="fas fa-file-audio text-indigo-600"></i>
            </div>
            <div class="text-sm font-bold text-indigo-900">{{ $fileCount }}</div>
        </div>

        <!-- Last Activity -->
        <div class="bg-gray-50 border border-gray-200/50 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-700 uppercase tracking-wide">Last Activity</span>
                <i class="fas fa-clock text-gray-600"></i>
            </div>
            <div class="text-sm font-bold text-gray-900">{{ $lastActivity->diffForHumans() }}</div>
        </div>
    </div>
</div> 