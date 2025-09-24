# PayPal Commerce Platform Setup Guide

## Overview

MixPitch now supports PayPal Commerce Platform as a full marketplace payment solution, equivalent to Stripe Connect. This allows users to connect their existing PayPal Business accounts and receive payments directly with platform fee support.

## How PayPal Commerce Platform Works

### Architecture
PayPal Commerce Platform is PayPal's marketplace solution that enables:
- **Direct Payment Flow**: Customer payments go directly to seller's PayPal account
- **Platform Fees**: Automatic commission deduction on each transaction
- **OAuth Onboarding**: Sellers connect existing PayPal Business accounts
- **Instant Disbursement**: Immediate access to funds (no holding period)

### User Flow
1. **Producer Setup**: Producer clicks "Connect PayPal" on payout setup page
2. **OAuth Flow**: Redirects to PayPal to connect their business account
3. **Permission Grant**: Producer grants platform permission to process payments
4. **Account Connection**: Returns to MixPitch with account connected
5. **Payment Processing**: Customers can pay directly to producer's PayPal account

### Payment Processing
```
Customer Payment → Producer's PayPal Account (Direct)
                ↓
Platform Fee automatically deducted → Your PayPal Partner Account
```

## Current Implementation Status

### ✅ Completed Components
- **PayPalProvider Service** - Full Commerce Platform API integration
- **Database Schema** - Onboarding tracking and account management
- **PayPalController** - Handles onboarding returns and webhooks
- **Routes** - OAuth return flow and webhook endpoints
- **UI Integration** - Shows as "PayPal" option in provider selector

### ⚠️ Currently Disabled
PayPal is **disabled by default** (`PAYPAL_ENABLED=false`) until partnership approval is obtained.

## Setup Requirements

### 1. PayPal Partnership Application

**Apply Here**: [PayPal Commerce Platform Partnership](https://developer.paypal.com/docs/multiparty/)

**Required Information**:
- Business details (legal name, address, tax ID)
- Platform description and business model
- Expected transaction volume
- Integration timeline
- Technical contact information

**Application Process**:
1. Fill out partnership application form
2. Provide business verification documents
3. Describe your marketplace use case
4. Wait for approval (typically 1-2 weeks)
5. Receive partner credentials

### 2. API Credentials Setup

Once approved, you'll receive:
- **Partner ID** - Your unique partner identifier
- **Client ID** - OAuth application identifier  
- **Client Secret** - OAuth application secret
- **BN Code** - Partner attribution code for revenue tracking

### 3. Environment Configuration

Update your `.env` file:
```env
# Enable PayPal after approval
PAYPAL_ENABLED=true

# Partner Credentials (from PayPal)
PAYPAL_PARTNER_ID=your_partner_id_here
PAYPAL_CLIENT_ID=your_client_id_here  
PAYPAL_CLIENT_SECRET=your_client_secret_here
PAYPAL_BN_CODE=your_bn_code_here

# Environment
PAYPAL_ENVIRONMENT=sandbox  # Use 'production' for live

# Webhook ID (created after setup)
PAYPAL_WEBHOOK_ID=your_webhook_id_here
```

### 4. Webhook Configuration

**Create Webhook in PayPal Developer Console**:
1. Login to PayPal Developer Dashboard
2. Navigate to your app → Webhooks
3. Create webhook with URL: `https://yourdomain.com/paypal/webhook`
4. Subscribe to these events:
   - `MERCHANT.ONBOARDING.COMPLETED`
   - `MERCHANT.PARTNER-CONSENT.REVOKED`  
   - `PAYMENT.CAPTURE.COMPLETED`
   - `PAYMENT.CAPTURE.REFUNDED`

**Update Configuration**:
```env
PAYPAL_WEBHOOK_ID=your_webhook_id_from_paypal
```

## Testing Process

### 1. Sandbox Testing
```env
PAYPAL_ENVIRONMENT=sandbox
PAYPAL_ENABLED=true
# Use sandbox credentials
```

### 2. Test User Flow
1. Create test PayPal Business account in sandbox
2. Go to `/payouts/setup` 
3. Click "Set Up PayPal"
4. Complete OAuth flow with test account
5. Verify account shows as connected

### 3. Test Payment Processing
1. Create test order with platform fee
2. Process payment through PayPal Checkout
3. Verify direct payment to seller account
4. Verify platform fee to partner account

## Production Deployment

### 1. Update Environment
```env
PAYPAL_ENVIRONMENT=production
PAYPAL_ENABLED=true
# Use production credentials
```

### 2. Update Webhook URL
Change webhook URL to production: `https://mixpitch.com/paypal/webhook`

### 3. Monitor Integration
- Check logs for successful onboarding completions
- Monitor webhook event processing
- Verify platform fee calculations
- Test refund processing

## Technical Implementation Details

### Database Tables
```sql
-- Tracks PayPal onboarding flows
paypal_onboarding_links (
    user_id, tracking_id, action_url, 
    expires_at, completed_at, merchant_id
)

-- Enhanced user payout accounts
user_payout_accounts (
    user_id, provider='paypal', account_id, 
    paypal_merchant_id, paypal_permissions,
    paypal_payments_receivable
)
```

### Key Classes
- `PayPalProvider` - Main service class
- `PayPalController` - Handles OAuth returns and webhooks
- `PayoutAccountManagementService` - Multi-provider account management

### Available Routes
```php
// OAuth return after PayPal onboarding
GET /paypal/onboarding/return

// Webhook endpoint for PayPal events  
POST /paypal/webhook
```

## Business Benefits

### For Users (Producers)
- **Familiar Platform**: Use existing PayPal Business account
- **Direct Payments**: Money goes directly to their account
- **Instant Access**: No holding periods or delays
- **Lower Fees**: Competitive PayPal processing rates
- **Global Reach**: PayPal available in 200+ countries

### For Platform (MixPitch)
- **Reduced Risk**: No money handling or liability
- **Automatic Fees**: Platform commission handled by PayPal
- **User Choice**: Support users' preferred payment method
- **Compliance**: PayPal handles regulatory requirements
- **Revenue Attribution**: Tracking via BN codes

## Support and Troubleshooting

### Common Issues
1. **Onboarding Fails**: Check partner credentials and permissions
2. **Webhooks Not Working**: Verify webhook URL and signature validation
3. **Platform Fees Missing**: Ensure BN code is included in API calls
4. **Account Not Ready**: Verify business account completion

### Monitoring
- Check Laravel logs for PayPal API errors
- Monitor webhook delivery in PayPal dashboard  
- Track onboarding completion rates
- Monitor platform fee collection

### PayPal Support
- Partner support via PayPal Developer Console
- Technical documentation at developer.paypal.com
- Sandbox testing tools available

## Next Steps Checklist

- [ ] Submit PayPal Commerce Platform partnership application
- [ ] Wait for approval and receive credentials
- [ ] Update environment configuration with real credentials
- [ ] Set up production webhooks
- [ ] Test onboarding flow in sandbox
- [ ] Test payment processing with platform fees
- [ ] Deploy to production with monitoring
- [ ] Update user documentation with PayPal option

## Contact Information

For questions about this implementation:
- Technical: Review code in `app/Services/Payouts/PayPalProvider.php`
- PayPal Support: PayPal Developer Console
- Documentation: This guide and inline code comments

---

**Note**: PayPal Commerce Platform partnership approval is required before this feature can be enabled for users. The technical implementation is complete and ready for production deployment once approved.