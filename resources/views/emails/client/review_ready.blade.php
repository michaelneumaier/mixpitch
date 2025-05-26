<x-mail::message>
# Your Project is Ready for Review

Hello {{ $clientName ?? 'there' }},

Great news! {{ $producerName }} has submitted work for your project "**{{ $projectTitle }}**" and it's ready for your review.

@if($fileCount > 0)
{{ $producerName }} has uploaded {{ $fileCount }} {{ Str::plural('file', $fileCount) }} for you to review.
@endif

You can access the client portal to:
- Listen to or download the submitted files
- Provide feedback or request revisions
- Approve the work if you're satisfied

<x-mail::button :url="$portalUrl">
Review Your Project
</x-mail::button>

This secure link is unique to you and will expire after some time for security.

If you have any questions, please reply to this email or contact {{ $producerName }} directly.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message> 