<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payout Completed - MixPitch</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8fafc;
        }
        .email-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px 40px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 40px;
        }
        .celebration-banner {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .celebration-banner .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .amount-display {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin: 25px 0;
        }
        .amount-display .amount {
            font-size: 36px;
            font-weight: 800;
            color: #1e40af;
            margin: 0;
        }
        .amount-display .label {
            color: #3b82f6;
            font-weight: 600;
            margin-top: 5px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 25px 0;
        }
        .detail-item {
            background: #f8fafc;
            border-radius: 6px;
            padding: 15px;
            border-left: 4px solid #10b981;
        }
        .detail-label {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .detail-value {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }
        .project-info {
            background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .project-info h3 {
            margin: 0 0 10px 0;
            color: #7c3aed;
            font-size: 18px;
        }
        .project-info p {
            margin: 5px 0;
            color: #5b21b6;
        }
        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
        }
        .action-button:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }
        .footer {
            background: #f8fafc;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 5px 0;
            color: #6b7280;
            font-size: 14px;
        }
        .footer a {
            color: #3b82f6;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .header, .content, .footer {
                padding: 20px;
            }
            .details-grid {
                grid-template-columns: 1fr;
            }
            .amount-display .amount {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>🎉 Payout Completed!</h1>
            <p>Your earnings have been successfully transferred</p>
        </div>
        
        <div class="content">
            <div class="celebration-banner">
                <div class="icon">💰</div>
                <h2 style="margin: 0; color: #f59e0b;">Congratulations!</h2>
                <p style="margin: 10px 0 0 0; color: #d97706;">Your payout has been processed and is on its way to your account.</p>
            </div>

            <div class="amount-display">
                <h2 class="amount">${{ number_format($payout->net_amount, 2) }}</h2>
                <p class="label">Net Amount Transferred</p>
            </div>

            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Gross Amount</div>
                    <div class="detail-value">${{ number_format($payout->gross_amount, 2) }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Commission Rate</div>
                    <div class="detail-value">{{ $payout->commission_rate }}%</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Transfer ID</div>
                    <div class="detail-value">{{ $payout->stripe_transfer_id ?? 'Processing...' }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Completed At</div>
                    <div class="detail-value">{{ $payout->completed_at?->format('M j, Y g:i A') }}</div>
                </div>
            </div>

            @if($payout->workflow_type === 'contest' && $payout->contestPrize)
            <div class="project-info">
                <h3>🏆 Contest Prize Details</h3>
                <p><strong>Placement:</strong> {{ $payout->contestPrize->placement }}</p>
                <p><strong>Prize Amount:</strong> ${{ number_format($payout->contestPrize->amount, 2) }}</p>
                <p><strong>Contest:</strong> {{ $payout->project->name }}</p>
            </div>
            @else
            <div class="project-info">
                <h3>📁 Project Details</h3>
                <p><strong>Project:</strong> {{ $payout->project->name }}</p>
                <p><strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $payout->workflow_type)) }}</p>
            </div>
            @endif

            <div style="text-align: center;">
                <a href="{{ route('payouts.index') }}" class="action-button">
                    View Payout Dashboard
                </a>
            </div>

            <div style="background: #eff6ff; border-radius: 8px; padding: 20px; margin-top: 30px;">
                <h4 style="margin: 0 0 10px 0; color: #1e40af;">💡 What's Next?</h4>
                <ul style="margin: 0; padding-left: 20px; color: #3730a3;">
                    <li>Funds typically arrive in your account within 1-2 business days</li>
                    <li>You'll receive a separate confirmation from Stripe when the transfer completes</li>
                    <li>Check your payout dashboard for detailed transaction history</li>
                    <li>Keep creating amazing music and earning more!</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>MixPitch</strong> - Connecting Artists Worldwide</p>
            <p>
                <a href="{{ route('dashboard') }}">Dashboard</a> • 
                <a href="{{ route('payouts.index') }}">Payouts</a> • 
                <a href="{{ route('profile.show', auth()->user()) }}">Profile</a>
            </p>
            <p style="margin-top: 15px;">
                Questions? Contact us at <a href="mailto:support@mixpitch.com">support@mixpitch.com</a>
            </p>
        </div>
    </div>
</body>
</html> 