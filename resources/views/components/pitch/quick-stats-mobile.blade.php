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
            'iconBg' => 'from-green-400 to-green-600'
        ],
        'approved' => [
            'bg' => 'bg-blue-50', 
            'border' => 'border-blue-200/50', 
            'text' => 'text-blue-700', 
            'textBold' => 'text-blue-900',
            'icon' => 'fa-thumbs-up',
            'iconBg' => 'from-blue-400 to-blue-600'
        ],
        'closed' => [
            'bg' => 'bg-gray-50', 
            'border' => 'border-gray-200/50', 
            'text' => 'text-gray-700', 
            'textBold' => 'text-gray-900',
            'icon' => 'fa-lock',
            'iconBg' => 'from-gray-400 to-gray-600'
        ],
        'denied' => [
            'bg' => 'bg-red-50', 
            'border' => 'border-red-200/50', 
            'text' => 'text-red-700', 
            'textBold' => 'text-red-900',
            'icon' => 'fa-times-circle',
            'iconBg' => 'from-red-400 to-red-600'
        ],
        'pending' => [
            'bg' => 'bg-yellow-50', 
            'border' => 'border-yellow-200/50', 
            'text' => 'text-yellow-700', 
            'textBold' => 'text-yellow-900',
            'icon' => 'fa-clock',
            'iconBg' => 'from-yellow-400 to-yellow-600'
        ],
        'in_progress' => [
            'bg' => 'bg-blue-50', 
            'border' => 'border-blue-200/50', 
            'text' => 'text-blue-700', 
            'textBold' => 'text-blue-900',
            'icon' => 'fa-spinner',
            'iconBg' => 'from-blue-400 to-blue-600'
        ],
        'pending_review' => [
            'bg' => 'bg-purple-50', 
            'border' => 'border-purple-200/50', 
            'text' => 'text-purple-700', 
            'textBold' => 'text-purple-900',
            'icon' => 'fa-search',
            'iconBg' => 'from-purple-400 to-purple-600'
        ],
        'ready_for_review' => [
            'bg' => 'bg-indigo-50', 
            'border' => 'border-indigo-200/50', 
            'text' => 'text-indigo-700', 
            'textBold' => 'text-indigo-900',
            'icon' => 'fa-clipboard-check',
            'iconBg' => 'from-indigo-400 to-indigo-600'
        ],
        'revisions_requested' => [
            'bg' => 'bg-amber-50', 
            'border' => 'border-amber-200/50', 
            'text' => 'text-amber-700', 
            'textBold' => 'text-amber-900',
            'icon' => 'fa-pencil-alt',
            'iconBg' => 'from-amber-400 to-amber-600'
        ],
    ];
    $config = $statusConfig[$pitch->status] ?? [
        'bg' => 'bg-gray-50', 
        'border' => 'border-gray-200/50', 
        'text' => 'text-gray-700', 
        'textBold' => 'text-gray-900',
        'icon' => 'fa-info-circle',
        'iconBg' => 'from-gray-400 to-gray-600'
    ];
    
    $submissionCount = $pitch->snapshots->count();
    $fileCount = $pitch->files->count();
    $lastActivity = $pitch->updated_at;
@endphp

<div class="bg-gradient-to-br from-white/95 to-gray-50/90 backdrop-blur-sm border border-white/50 rounded-2xl p-4 shadow-lg">
    <div class="flex items-center mb-4">
        <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg mr-3">
            <i class="fas fa-chart-line text-white text-sm"></i>
        </div>
        <h3 class="text-base font-bold text-gray-800">Pitch Overview</h3>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <!-- Pitch Status -->
        <div class="{{ $config['bg'] }} border {{ $config['border'] }} rounded-xl p-3 text-center">
            <div class="flex items-center justify-center w-6 h-6 bg-gradient-to-br {{ $config['iconBg'] }} rounded-lg mb-2 mx-auto">
                <i class="fas {{ $config['icon'] }} text-white text-xs"></i>
            </div>
            <div class="text-xs font-medium {{ $config['text'] }} uppercase tracking-wide mb-1">Status</div>
            <div class="text-sm font-bold {{ $config['textBold'] }} leading-tight">
                {{ ucwords(str_replace('_', ' ', $pitch->status)) }}
            </div>
        </div>

        <!-- Submissions Count -->
        <div class="bg-purple-50 border border-purple-200/50 rounded-xl p-3 text-center">
            <div class="flex items-center justify-center w-6 h-6 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg mb-2 mx-auto">
                <i class="fas fa-code-branch text-white text-xs"></i>
            </div>
            <div class="text-xs font-medium text-purple-700 uppercase tracking-wide mb-1">Submissions</div>
            <div class="text-sm font-bold text-purple-900">{{ $submissionCount }}</div>
        </div>

        <!-- Files Count -->
        <div class="bg-indigo-50 border border-indigo-200/50 rounded-xl p-3 text-center">
            <div class="flex items-center justify-center w-6 h-6 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-lg mb-2 mx-auto">
                <i class="fas fa-file-audio text-white text-xs"></i>
            </div>
            <div class="text-xs font-medium text-indigo-700 uppercase tracking-wide mb-1">Files</div>
            <div class="text-sm font-bold text-indigo-900">{{ $fileCount }}</div>
        </div>

        <!-- Last Activity -->
        <div class="bg-gray-50 border border-gray-200/50 rounded-xl p-3 text-center">
            <div class="flex items-center justify-center w-6 h-6 bg-gradient-to-br from-gray-400 to-gray-600 rounded-lg mb-2 mx-auto">
                <i class="fas fa-clock text-white text-xs"></i>
            </div>
            <div class="text-xs font-medium text-gray-700 uppercase tracking-wide mb-1">Activity</div>
            <div class="text-sm font-bold text-gray-900 leading-tight">{{ $lastActivity->diffForHumans(null, true) }}</div>
        </div>
    </div>
</div> 