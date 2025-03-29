<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isFreeProject ? 'Project Completion' : 'Payment Receipt' }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eaeaea;
            margin-bottom: 20px;
        }
        
        .logo {
            max-width: 150px;
            margin-bottom: 10px;
        }
        
        h1 {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        h2 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        p {
            margin: 0 0 15px;
        }
        
        .receipt-details {
            background-color: #f9f9f9;
            border: 1px solid #eaeaea;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eaeaea;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
        }
        
        .detail-value {
            text-align: right;
            color: #333;
        }
        
        .amount {
            font-size: 18px;
            font-weight: 700;
            color: #2a9d8f;
        }
        
        .free-project {
            color: #2a9d8f;
            font-weight: 600;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 12px;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
        }
        
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4a7aff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        @media screen and (max-width: 600px) {
            .container {
                width: 100% !important;
                padding: 15px !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ config('app.url') }}/images/logo.png" alt="Mixpitch Logo" class="logo">
            <h1>{{ $isFreeProject ? 'Project Completion Confirmation' : 'Payment Receipt' }}</h1>
            <p>{{ $paymentDate }}</p>
        </div>
        
        <p>Dear {{ $recipientName }},</p>
        
        @if($recipientType === 'owner')
            @if($isFreeProject)
            <p>Your free project <strong>{{ $project->name }}</strong> has been successfully completed!</p>
            @else
            <p>Your payment for <strong>{{ $project->name }}</strong> has been successfully processed.</p>
            @endif
        @else
            @if($isFreeProject)
            <p>The free project <strong>{{ $project->name }}</strong> that you created a pitch for has been marked as completed!</p>
            @else
            <p>We're pleased to inform you that payment for your work on <strong>{{ $project->name }}</strong> has been processed.</p>
            @endif
        @endif
        
        <div class="receipt-details">
            <div class="detail-row">
                <span class="detail-label">Project:</span>
                <span class="detail-value">{{ $project->name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Pitch ID:</span>
                <span class="detail-value">#{{ $pitch->id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value">{{ $paymentDate }}</span>
            </div>
            @if(!$isFreeProject)
            <div class="detail-row">
                <span class="detail-label">Invoice ID:</span>
                <span class="detail-value">{{ $invoiceId }}</span>
            </div>
            @endif
            <div class="detail-row">
                <span class="detail-label">Amount:</span>
                <span class="detail-value {{ $isFreeProject ? 'free-project' : 'amount' }}">
                    {{ $isFreeProject ? 'Free Project' : '$'.number_format($amount, 2) }}
                </span>
            </div>
        </div>
        
        @if($recipientType === 'owner')
            <p>Thank you for using Mixpitch for your music production needs. We hope you're satisfied with the completed work!</p>
        @else
            <p>Thank you for your excellent work on this project. We appreciate your talent and dedication!</p>
        @endif
        
        <p>You can view the complete details of this {{ $isFreeProject ? 'project' : 'payment' }} by clicking the button below:</p>
        
        <p style="text-align: center;">
            <a href="{{ route('projects.pitches.payment.receipt', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]) }}" class="button">View Receipt Details</a>
        </p>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Mixpitch. All rights reserved.</p>
            <p>If you have any questions, please contact our support team at support@mixpitch.com</p>
        </div>
    </div>
</body>
</html> 