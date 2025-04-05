@props(['status'])

@php
$config = match ($status) {
    'pending' => [
        'bgColor' => 'bg-yellow-50',
        'borderColor' => 'border-yellow-500',
        'icon' => 'fa-clock',
        'iconColor' => 'text-yellow-600',
        'textColor' => 'text-yellow-800',
        'text' => 'Awaiting Project Owner Access',
    ],
    'ready_for_review' => [
        'bgColor' => 'bg-blue-50',
        'borderColor' => 'border-blue-500',
        'icon' => 'fa-hourglass-half',
        'iconColor' => 'text-blue-600',
        'textColor' => 'text-blue-800',
        'text' => 'Pitch Under Review',
    ],
    'pending_review' => [
        'bgColor' => 'bg-purple-50',
        'borderColor' => 'border-purple-500',
        'icon' => 'fa-search',
        'iconColor' => 'text-purple-600',
        'textColor' => 'text-purple-800',
        'text' => 'Response Requires Review',
    ],
    'denied' => [
        'bgColor' => 'bg-red-50',
        'borderColor' => 'border-red-500',
        'icon' => 'fa-times-circle',
        'iconColor' => 'text-red-600',
        'textColor' => 'text-red-800',
        'text' => 'Pitch Not Accepted',
    ],
    'approved' => [
        'bgColor' => 'bg-green-50',
        'borderColor' => 'border-green-500',
        'icon' => 'fa-check-circle',
        'iconColor' => 'text-green-600',
        'textColor' => 'text-green-800',
        'text' => 'Pitch Approved!',
    ],
    'revisions_requested' => [
        'bgColor' => 'bg-amber-50',
        'borderColor' => 'border-amber-500',
        'icon' => 'fa-exclamation-circle',
        'iconColor' => 'text-amber-600',
        'textColor' => 'text-amber-800',
        'text' => 'Revisions Requested',
    ],
    'completed' => [
        'bgColor' => 'bg-success/20',
        'borderColor' => 'border-success',
        'icon' => 'fa-trophy',
        'iconColor' => 'text-success',
        'textColor' => 'text-success-content',
        'text' => 'Pitch Successfully Completed',
    ],
    'in_progress' => [
        'bgColor' => 'bg-indigo-50', // Example color, adjust as needed
        'borderColor' => 'border-indigo-500',
        'icon' => 'fa-cogs', // Example icon
        'iconColor' => 'text-indigo-600',
        'textColor' => 'text-indigo-800',
        'text' => 'In Progress',
    ],
    default => [
        'bgColor' => 'bg-gray-100',
        'borderColor' => 'border-gray-400',
        'icon' => 'fa-info-circle',
        'iconColor' => 'text-gray-500',
        'textColor' => 'text-gray-700',
        'text' => ucfirst(str_replace('_', ' ', $status)),
    ]
};
@endphp

<div class="p-2.5 sm:p-4 border-l-4 rounded-r-md {{ $config['bgColor'] }} {{ $config['borderColor'] }}">
    <div class="flex items-center">
        <i class="fas {{ $config['icon'] }} {{ $config['iconColor'] }} mr-2 sm:mr-3 text-base sm:text-lg"></i>
        <div>
            <p class="font-semibold {{ $config['textColor'] }} text-sm sm:text-base leading-tight sm:leading-normal">
                {{ $config['text'] }}
            </p>
            {{-- Optionally add description if needed later --}}
            {{-- <p class="text-xs sm:text-sm text-gray-600 mt-1">Description here</p> --}}
        </div>
    </div>
</div> 