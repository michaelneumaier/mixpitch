<x-mail::message>
# Payment Received - Thank You!

Hello {{ $clientName ?? 'there' }},

Thank you for your payment! We've successfully processed your payment for "**{{ $projectTitle }}**".

## Payment Details

**Amount Paid:** {{ $currency }} {{ $formattedAmount }}
**Transaction ID:** {{ $transactionId }}
**Payment Date:** {{ $paymentDate }}
**Project:** {{ $projectTitle }}

## What Happens Next?

Your payment has been confirmed and {{ $producerName }} will receive their payout according to our standard payout schedule.

You can now access your final deliverables through the client portal.

<x-mail::button :url="$invoiceUrl">
View Invoice
</x-mail::button>

<x-mail::button :url="$portalUrl" color="secondary">
Access Deliverables
</x-mail::button>

## Need Help?

If you have any questions about this transaction or need a copy of your invoice, please don't hesitate to contact us or reply to this email.

**Important:** Please save this email for your records. It serves as your payment confirmation and receipt.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
