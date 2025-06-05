<x-mail::message>
# Reminder: License Agreement Pending

Hello {{ $user->name }},

This is a friendly reminder that you have a pending license agreement for the project **{{ $project->name }}**.

## Project Details
- **Project:** {{ $project->name }}
- **Owner:** {{ $project->user->name }}
- **Genre:** {{ $project->genre }}

@if($licenseTemplate)
## License Template
**{{ $licenseTemplate->name }}**

{{ Str::limit($licenseTemplate->description, 200) }}
@else
This project uses the platform's standard terms and conditions.
@endif

## Action Required
To participate in this project, please review and sign the license agreement.

<x-mail::button :url="$signUrl">
Review & Sign License Agreement
</x-mail::button>

## Why is this important?
- License agreements protect both you and the project owner
- They clarify how your contributions can be used
- They ensure everyone understands their rights and responsibilities

@if($reminderCount > 1)
*This is reminder #{{ $reminderCount }}. Please take a moment to review the agreement.*
@endif

If you're no longer interested in this project or have questions about the license terms, please contact the project owner.

Thanks,<br>
{{ config('app.name') }}

---
<small>You're receiving this because you were invited to collaborate on {{ $project->name }}. If you believe this was sent in error, please contact our support team.</small>
</x-mail::message> 