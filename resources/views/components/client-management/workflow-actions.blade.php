@props(['pitch', 'project', 'component'])

@php
    $status = $pitch->status;
    $statusFeedbackMessage = $component->statusFeedbackMessage ?? null;
    $canResubmit = $component->canResubmit ?? false;
    $producerFiles = $component->producerFiles ?? collect();
    $clientFiles = $component->clientFiles ?? collect();
@endphp

@if($status === \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED)
    {{-- PRIORITY FLOW: Client Revisions Requested --}}
    
    <!-- 1. CRITICAL: Enhanced Feedback Panel -->
    <x-client-project.feedback-panel :pitch="$pitch" />

    <!-- 2. IMMEDIATE ACTION: Response to Feedback -->
    @if($statusFeedbackMessage)
        <x-client-management.feedback-response-section :component="$component" />
    @endif

    <!-- 3. UPDATE FILES: File Management (Streamlined) -->
    <x-client-management.file-update-section 
        :pitch="$pitch" 
        title="Update Your Deliverables"
        description="Make changes based on client feedback"
        uploadTitle="Upload Updated Files"
        uploadDescription="Upload revised versions or additional files based on client feedback" />

    <!-- 4. COMPLETE FLOW: Submit Revisions -->
    <x-client-management.submit-section 
        :pitch="$pitch" 
        :component="$component"
        type="revisions"
        title="Submit Revisions"
        description="Send your updated work back to the client"
        buttonText="Submit Revisions to Client"
        infoText="Client will be notified and can review your updated work" />

@elseif($status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
    {{-- READY FOR REVIEW: Actions now handled by workflow-status component --}}
    {{-- No additional sections needed - workflow status component shows recall buttons --}}

@else
    {{-- STANDARD FLOW: In Progress / Getting Started --}}
    
    <!-- 1. CLIENT REFERENCE FILES: First priority for new projects -->
    <x-client-management.client-files-section 
        :project="$project"
        :component="$component"
        :files="$clientFiles" />

    <!-- 2. UPLOAD AREA: Primary action for active work -->
    <x-client-management.upload-work-section 
        :pitch="$pitch"
        :component="$component"
        :fileCount="$producerFiles->count()" />

@endif