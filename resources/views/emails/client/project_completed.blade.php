<x-mail::message>
# Project Completed: {{ $project->title }}

Hello {{ $clientName ?? 'Client' }},

We're pleased to inform you that the producer has marked your project, **{{ $project->title }}**, as complete.

@if($feedback)
**Producer Feedback:**

{{ $feedback }}
@endif

@if($rating)
**Producer Rating:** {{ $rating }} / 5
@endif

You can view the final details via the client portal, although further actions may be limited now that the project is complete.

<x-mail::button :url="$signedUrl">
View Project Portal
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
