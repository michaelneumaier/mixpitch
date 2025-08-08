<x-mail::message>
@php
    // Basic branding hooks; extend later with producer settings
    $brandName = $producerName . ' via ' . config('app.name');
@endphp
# {{ $customBody ? 'Invitation to Collaborate' : ('Invitation to Collaborate on Project: ' . $projectTitle) }}

Hello {{ $clientName ?? 'there' }},

@if(!empty($customBody))
{!! nl2br(e($customBody)) !!}
@else
{{ $producerName }} has invited you to collaborate on the project "**{{ $projectTitle }}**" using the MixPitch Client Portal.
@endif

You can access the secure portal to view project details, communicate with {{ $producerName }}, and review deliverables using the button below.

<x-mail::button :url="$portalUrl">
Access Client Portal
</x-mail::button>

This link is unique to you and will expire.

If you have any questions, please reply to this email or contact {{ $producerName }} directly.

Thanks,<br>
{{ $brandName }}
</x-mail::message>
