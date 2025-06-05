<x-mail::message>
# License Agreement Required

Hello {{ $user->name }},

You've been invited to collaborate on the project **{{ $project->name }}** by {{ $invitedBy->name }}.

Before you can participate in this project, you need to review and accept the license agreement.

## Project Details
- **Project:** {{ $project->name }}
- **Owner:** {{ $project->user->name }}
- **Genre:** {{ $project->genre }}

@if($signature->invitation_message)
## Personal Message
{{ $signature->invitation_message }}
@endif

## License Information
@if($licenseTemplate)
**License Template:** {{ $licenseTemplate->name }}

{{ Str::limit($licenseTemplate->description, 200) }}
@else
This project uses the platform's standard terms and conditions.
@endif

<x-mail::button :url="$signUrl">
Review & Sign License Agreement
</x-mail::button>

## What happens next?
1. Click the button above to review the full license terms
2. If you agree, digitally sign the agreement
3. You'll gain access to collaborate on the project

If you have any questions about the license terms, please contact {{ $invitedBy->name }} or the project owner.

Thanks,<br>
{{ config('app.name') }}

---
<small>This invitation was sent by {{ $invitedBy->name }} ({{ $invitedBy->email }}). If you believe this was sent in error, please contact our support team.</small>
</x-mail::message> 