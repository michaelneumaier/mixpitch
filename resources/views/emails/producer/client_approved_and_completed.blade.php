<x-mail::message>
# Great News! {{ $clientName }} Approved Your Project

Hello {{ $producerName }},

Congratulations! {{ $clientName }} has approved your submission for "**{{ $projectTitle }}**" and the project is now complete.

@if($hasPayment && $paymentAmount > 0)
## Payment & Payout Information

**Payment Amount:** ${{ number_format($paymentAmount, 2) }}

Your payout is being processed and will be released according to our standard payout schedule. You'll receive a separate notification once your payout is scheduled with specific timing details.

@endif

## What's Next?

- Your project is now marked as completed
- This successful completion will contribute to your producer profile and ratings
@if($hasPayment)
- Your earnings will be processed and transferred to your connected payout account
@endif
- Feel free to reach out to {{ $clientName }} for future collaboration opportunities

<x-mail::button :url="$projectUrl">
View Project Details
</x-mail::button>

You can also check your earnings and payout status on your dashboard.

<x-mail::button :url="$dashboardUrl" color="secondary">
Go to Dashboard
</x-mail::button>

Thank you for delivering excellent work and maintaining the high standards that make our platform great!

Best regards,<br>
The {{ config('app.name') }} Team
</x-mail::message> 