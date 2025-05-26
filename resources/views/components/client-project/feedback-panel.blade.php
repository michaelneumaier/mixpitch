@props(['pitch'])

@php
    $latestRevisionEvent = $pitch->events()
        ->where('event_type', 'client_revisions_requested')
        ->latest()
        ->first();
@endphp

@if($pitch->status === \App\Models\Pitch::STATUS_REVISIONS_REQUESTED && $latestRevisionEvent)
<div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
    <h4 class="text-lg font-semibold text-amber-800 mb-3 flex items-center">
        <i class="fas fa-exclamation-triangle text-amber-600 mr-2"></i>
        Client Requested Revisions
    </h4>
    
    <div class="bg-white border border-amber-200 rounded-md p-4 mb-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-amber-700">Client Feedback:</span>
            <span class="text-xs text-amber-600">
                {{ $latestRevisionEvent->created_at->format('M d, Y \a\t g:i A') }}
            </span>
        </div>
        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $latestRevisionEvent->comment }}</p>
    </div>
    
    <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
        <h5 class="font-medium text-blue-800 mb-2">
            <i class="fas fa-lightbulb mr-1"></i>Next Steps:
        </h5>
        <ul class="text-sm text-blue-700 space-y-1">
            <li>• Review the client's feedback above</li>
            <li>• Make the requested changes to your files</li>
            <li>• Add a comment explaining your changes</li>
            <li>• Resubmit for client review</li>
        </ul>
    </div>
</div>
@endif 