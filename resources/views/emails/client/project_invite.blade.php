<x-mail::message>
# Invitation to Collaborate on Project: {{ $projectTitle }}

Hello {{ $clientName ?? 'there' }},

{{ $producerName }} has invited you to collaborate on the project "**{{ $projectTitle }}**" using the MixPitch Client Portal.

You can access the secure portal to view project details, communicate with {{ $producerName }}, and review deliverables using the button below.

<x-mail::button :url="$portalUrl">
Access Client Portal
</x-mail::button>

This link is unique to you and will expire.

If you have any questions, please reply to this email or contact {{ $producerName }} directly.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
