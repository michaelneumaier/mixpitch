<x-mail::message>
# {{ $clientName }} Sent You a Message

Hello {{ $producerName }},

{{ $clientName }} left a comment on your project "**{{ $projectTitle }}**".

## Message from {{ $clientName }}

@if($isLongComment)
> {{ $commentExcerpt }}...

_[View full message in project]_
@else
> {{ $comment }}
@endif

## Stay Connected

Timely communication helps build trust and ensures the project stays on track. We recommend responding to your client's message as soon as possible.

<x-mail::button :url="$projectUrl">
Reply to {{ $clientName }}
</x-mail::button>

You can view the full conversation and respond directly through the project portal.

Thanks,<br>
The {{ config('app.name') }} Team
</x-mail::message>
