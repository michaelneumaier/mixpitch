<x-mail::message>
# {{ $clientName }} Requested Changes to Your Work

Hello {{ $producerName }},

{{ $clientName }} has reviewed your submission for "**{{ $projectTitle }}**" and requested some revisions.

## Client Feedback

> {{ $feedback }}

## Next Steps

Please review the feedback above and upload updated files when you're ready. The sooner you can address these changes, the faster you can move toward project completion and payment.

**What you need to do:**
1. Review the client's feedback carefully
2. Make the requested changes to your work
3. Upload the updated files to the project
4. Submit the work for client review again

<x-mail::button :url="$projectUrl">
View Project & Respond
</x-mail::button>

If you have any questions about the feedback or need clarification from {{ $clientName }}, you can communicate directly through the project portal.

Thanks,<br>
The {{ config('app.name') }} Team
</x-mail::message>
