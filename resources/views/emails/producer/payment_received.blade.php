<x-mail::message>
# Great News! {{ $clientName }} Has Paid for {{ $projectTitle }}

Hello {{ $producerName }},

Congratulations! {{ $clientName }} has successfully completed payment for "**{{ $projectTitle }}**" and your payout has been scheduled.

## Payment Breakdown

**Client Payment:** {{ $currency }} {{ $grossAmount }}
**Platform Fee ({{ $platformFeePercentage }}%):** {{ $currency }} {{ $platformFee }}
**Your Payout:** {{ $currency }} {{ $netAmount }}

## Payout Schedule

Your payout of **{{ $currency }} {{ $netAmount }}** will be released on **{{ $payoutDate }}** ({{ $payoutDateRelative }}).

The funds will be transferred to your connected payout account according to our standard payout schedule. You'll receive a separate notification once the payout has been processed.

## What's Next?

- Your project is now marked as completed and paid
- This successful completion will contribute to your producer profile and ratings
- The client can now access the final deliverables

<x-mail::button :url="$projectUrl">
View Project Details
</x-mail::button>

<x-mail::button :url="$earningsUrl" color="secondary">
Check Payout Status
</x-mail::button>

## Questions About Your Payout?

You can track your payout status and view your earnings history on your dashboard. If you have any questions about this payment or your payout schedule, please don't hesitate to contact our support team.

Thank you for delivering excellent work and maintaining the high standards that make our platform great!

Best regards,<br>
The {{ config('app.name') }} Team
</x-mail::message>
