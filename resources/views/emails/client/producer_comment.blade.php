@component('mail::message')
# New Message from {{ $pitch->user->name }}

Hello {{ $clientName ?? 'there' }},

{{ $pitch->user->name }} has sent you a message regarding your project "{{ $project->title }}":

@component('mail::panel')
{{ $comment }}
@endcomponent

@component('mail::button', ['url' => $signedUrl])
View Project & Respond
@endcomponent

**Project Details:**
- **Project:** {{ $project->title }}
- **Producer:** {{ $pitch->user->name }}
- **Status:** {{ $pitch->readable_status }}

You can view the full conversation and project files by clicking the button above.

Thanks,<br>
{{ config('app.name') }}
@endcomponent 