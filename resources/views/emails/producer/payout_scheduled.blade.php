<x-mail::message>
# Your Payout Has Been Scheduled

Hello {{ $producerName }},

Great news! Your payout for "**{{ $projectTitle }}**" has been scheduled and will be processed soon.

## Payout Details

**Net Payout Amount:** ${{ number_format($netAmount, 2) }}
**Release Date:** {{ $holdReleaseDate->format('F j, Y') }}

### Payment Breakdown
- **Gross Amount:** ${{ number_format($grossAmount, 2) }}
- **Platform Commission ({{ $commissionRate }}%):** ${{ number_format($commissionAmount, 2) }}
- **Your Earnings:** ${{ number_format($netAmount, 2) }}

## What Happens Next?

Your payout will be automatically processed on {{ $holdReleaseDate->format('F j, Y') }} and transferred to your connected payout account. This hold period ensures transaction security and allows for any potential disputes to be resolved.

You'll receive another notification once the payout has been successfully processed and transferred.

<x-mail::button :url="$payoutsUrl">
View All Payouts
</x-mail::button>

<x-mail::button :url="$dashboardUrl" color="secondary">
Go to Dashboard
</x-mail::button>

If you have any questions about your payout or need to update your payout information, please contact our support team.

**Payout Reference:** #{{ $payoutScheduleId }}

Thanks for being a valued producer on {{ config('app.name') }}!

Best regards,<br>
The {{ config('app.name') }} Team
</x-mail::message> 