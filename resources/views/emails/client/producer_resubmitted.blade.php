<x-mail::message>
# {{ $producerName }} Has Addressed Your Feedback

Hello {{ $clientName ?? 'there' }},

Great news! {{ $producerName }} has uploaded updated work for "**{{ $projectTitle }}**" based on your feedback.

@if($fileCount > 0)
{{ $producerName }} has uploaded {{ $fileCount }} updated {{ \Illuminate\Support\Str::plural('file', $fileCount) }} for your review.
@endif

@if($producerNote)
## Message from {{ $producerName }}

> {{ $producerNote }}
@endif

## Ready to Review

You can now listen to the updated work, provide additional feedback if needed, or approve the project if you're satisfied with the changes.

<x-mail::button :url="$portalUrl">
Review Updated Work
</x-mail::button>

Your feedback helps {{ $producerName }} deliver exactly what you're looking for. If you have any questions or need further adjustments, just let {{ $producerName }} know through the portal.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
