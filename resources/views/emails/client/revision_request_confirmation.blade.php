<x-mail::message>
# We've Received Your Feedback

Hello {{ $clientName ?? 'there' }},

Thank you for your detailed feedback on "**{{ $projectTitle }}**". We've received your revision request and {{ $producerName }} has been notified immediately.

## Your Feedback

> {{ $feedback }}

## What Happens Next?

{{ $producerName }} will review your feedback and work on the requested changes. You'll receive an email notification once the updated work is ready for your review.

In the meantime, you can view the project status and continue the conversation via your client portal.

<x-mail::button :url="$portalUrl">
View Your Project
</x-mail::button>

If you have any additional questions or need to add more details to your feedback, please reply to this email or add a comment in the portal.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
