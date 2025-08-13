## MixPitch Payments: Stripe → Multi-Provider (Stripe, PayPal, Square)

This document proposes a concrete plan to evolve MixPitch's billing and payouts from a primarily-Stripe implementation into a provider-agnostic platform that supports Stripe, PayPal, and Square for both collection (payer side) and disbursement (receiver side). The goal is to allow each payee (producer) to enable one or more providers, and to require the payer (client) to complete checkout using one of the payee's enabled providers.

### Executive Summary of Research Findings (Updated 2025)

**✅ PayPal: Excellent Compatibility**
- Fully supports MixPitch's platform-collect and hold period model
- Robust Payouts API (15,000 payments/call, $20K-$60K limits)
- Requires Partner Referrals API approval and BN code
- **Recommendation**: High priority implementation

**⚠️ Square: Fundamental Architecture Conflict**
- Marketplace model designed for direct-settlement with immediate payouts
- Does NOT support platform hold periods in standard implementation
- Requires "instant settlement" mode that bypasses MixPitch's core workflow
- **Recommendation**: Feature-flagged implementation with clear user expectations

**Key Architectural Decision**: The plan now explicitly acknowledges that Square requires a different settlement paradigm that conflicts with MixPitch's hold period requirements. This necessitates conditional workflow logic and clear user communication about settlement timing differences.

### ⚠️ CRITICAL DISCOVERY: Existing Codebase is Exceptionally Well-Prepared

**After comprehensive analysis of 20+ files, the codebase is FAR more ready for multi-provider support than initially assumed:**

1. **Database Schema**: Already provider-agnostic with `payment_method`, `external_transaction_id`, and workflow support
2. **Service Layer**: Production-tested services can be wrapped rather than rewritten  
3. **Payout System**: `PayoutProcessingService` already orchestrates provider-agnostic payouts
4. **Transaction Tracking**: Complete financial abstraction already implemented
5. **Webhook Infrastructure**: Robust system ready for provider extension

**This significantly reduces implementation complexity and risk.**

### Objectives

- Unify payment and payout logic behind provider-agnostic interfaces.
- Keep current Stripe integration working while introducing PayPal and Square.
- Support per-producer provider enablement (one or many) and expose that choice to payers at checkout.
- Preserve MixPitch controls (hold periods, commissions, refunds, disputes, auditing) consistently across providers.
- Ship incrementally with low risk and strong observability.

---

## 1) Current State (Comprehensive Codebase Analysis)

### Core Payment Processing Architecture

**Primary Controllers:**
- `app/Http/Controllers/OrderController.php` - **Service Package Orders**
  - Uses Stripe Checkout with `payment_intent_data.transfer_data.destination` (Destination Charges)  
  - Immediate transfer to producer with `application_fee_amount` for platform commission
  - Delegates invoice creation to `InvoiceService::createInvoiceForOrder()`
  
- `app/Http/Controllers/PitchPaymentController.php` - **Pitch Payments**
  - Platform-collect model using `InvoiceService::createPitchInvoice()` and `processInvoicePayment()`
  - Calls `PitchWorkflowService::markPitchAsPaid()` on success
  - Supports client management workflow with external client payments
  
- `app/Http/Controllers/Billing/BillingController.php` - **Subscription & One-time Payments**
  - Creates Stripe Invoices and Invoice Items, finalizes, then pays via `payment_method`
  - Supports off-session payments and subscription management
  - Integrates with Cashier for customer portal and payment methods

**Service Layer (Critical for Provider Abstraction):**
- `app/Services/InvoiceService.php` - **Invoice Management**
  - Methods: `createPitchInvoice()`, `processInvoicePayment()`, `getInvoice()`, `getUserInvoices()`
  - Creates Stripe invoices with metadata for pitch/project tracking
  - Handles payment processing with proper error handling and logging
  
- `app/Services/StripeConnectService.php` - **Producer Onboarding & Payouts**
  - Express account creation, onboarding links, account status checking
  - Transfer processing: `processTransfer()`, `reverseTransfer()`, login links
  - Detailed account status with requirements parsing and next steps
  
- `app/Services/PayoutProcessingService.php` - **Payout Orchestration**
  - Schedules payouts with configurable hold periods per workflow type
  - Processes transfers via `StripeConnectService` when hold released
  - Supports standard pitches and contest prize payouts
  - Creates `Transaction` and `PayoutSchedule` records

### Database Schema (Already Provider-Ready)

**Existing Tables:**
- `transactions` - **Platform-agnostic transaction tracking**
  - Contains `payment_method`, `external_transaction_id` fields ready for multi-provider
  - Includes `producer_user_id`, `workflow_type`, `payout_schedule_id` for payout coordination
  
- `stripe_transactions` - **Stripe-specific transaction details**
  - Dedicated table for Stripe metadata (type, status, fees, Stripe IDs)
  - Can serve as model for PayPal/Square equivalents
  
- `payout_schedules` - **Multi-provider payout management**
  - Already includes `workflow_type`, `producer_stripe_account_id` fields
  - Ready for provider-specific account ID fields
  
- `users.stripe_account_id` - **Producer account tracking**
  - Single field, needs expansion to support multiple providers

**Missing Tables (Per Plan):**
- `payment_accounts` - **Proposed in plan, needs implementation**
- Provider-specific transaction tables (optional)

### Webhook Infrastructure

**Current Implementation:**
- `app/Http/Controllers/Billing/WebhookController.php` - **Comprehensive webhook handler**
  - Extends Cashier webhook controller with custom pitch payment logic
  - Handles: `invoice.payment_succeeded`, `checkout.session.completed`, subscription events
  - Supports multiple payment types: pitches, orders, client management, contests, milestones
  - Includes robust error handling and idempotency checks

### Model Integration Points

**User Model:** `app/Models/User.php`
- Methods: `hasValidStripeConnectAccount()`, `getStripeConnectStatus()` 
- Ready for provider abstraction with interface methods

**Transaction Model:** `app/Models/Transaction.php`
- Provider-agnostic design with `payment_method` and `external_transaction_id`
- Static creation methods: `createWithCommission()`, `createForPitch()`

### Workflow Integration

**Four Distinct Workflow Types** (All use same payment infrastructure):
- Standard: Platform-collect → hold → payout
- Contest: Platform-collect → immediate payout (0 day hold)
- Direct Hire: Platform-collect → hold → payout  
- Client Management: External client checkout → platform-collect → immediate payout

Observations:

- We currently exhibit two payout styles with Stripe:
  - Direct/Destination charges at checkout (funds flow to the connected account immediately with application fee retained).
  - Platform-hold-then-transfer via `PayoutProcessingService` (funds are collected into platform context first, then transferred post-hold).
- Pitch flow relies on invoices + post-payment payout scheduling; Order flow uses Checkout with immediate transfer. We should standardize these semantics prior to adding new providers.

---

## 2) Target Architecture

Introduce provider-agnostic abstractions for both payment collection and payouts. Stripe, PayPal, and Square become plug-in implementations.

### 2.1 Core interfaces

```php
interface PaymentGateway {
    // Create a payment session for a specific payee and cart/invoice context
    public function createCheckoutSession(PaymentRequest $request): PaymentSession;

    // For flows that collect immediately without redirect (e.g., off-session invoices)
    public function charge(DirectChargeRequest $request): ChargeResult;

    // Webhook verification and event parsing
    public function verifyWebhook(string $signatureHeader, string $payload): bool;
    public function parseEvent(string $payload): ProviderEvent;
}

interface PayoutGateway {
    // Onboard a recipient (producer)
    public function startOnboarding(User $user): OnboardingLink;
    public function getAccountStatus(User $user): AccountStatus;

    // Move funds to the recipient when hold is released (platform → recipient)
    public function transfer(PayoutTransferRequest $request): PayoutResult;
    public function reverseTransfer(ReverseTransferRequest $request): PayoutResult;
}

interface RefundGateway {
    public function refund(RefundRequest $request): RefundResult;
}

interface DisputeGateway {
    // Optional for providers that expose dispute webhooks and APIs
    public function fetchDispute(string $externalId): Dispute;
}
```

Each provider package implements the above against their APIs. Providers are registered in a `PaymentProviderManager` that resolves an implementation by `provider` enum (e.g., `stripe`, `paypal`, `square`).

### 2.2 Provider selection and routing

- At checkout time, the payer must choose from the recipient’s enabled providers.
- The UI reads a recipient’s enabled providers from a new `payment_accounts` table (see schema) and renders selectable options.
- The `GatewayRouterService` determines the correct `PaymentGateway` to create a session or direct charge.
- Payout processing uses the configured payout account for the recipient to pick the `PayoutGateway`.

### 2.3 Standardize payout semantics

To maintain consistent hold periods and dispute handling across providers:

- Prefer “platform collects, then disburses on schedule” as our canonical model. This aligns with existing pitch flow and allows consistent holds, refunds, and clawbacks.
- Stripe:
  - Continue using invoice-based or PaymentIntent-based collection into platform context, then transfer on hold release via Connect transfers.
  - For flows currently doing Destination Charges in `OrderController`, switch to platform-collect-and-transfer for consistency, or explicitly mark that flow as “immediate recipient settlement” and skip payout scheduling for that type. The first option is recommended for uniformity.
- PayPal:
  - **Recommended Model**: Platform-collect via standard PayPal checkout to platform merchant, then use PayPal Payouts API to disburse to recipients on hold release.
  - **PayPal Payouts API Capabilities**: Supports up to 15,000 payments per call, requires funding from PayPal balance, with individual payment limits of $20K USD for unregistered/$60K USD for registered recipients.
  - **Commerce Platform Alternative**: PayPal Commerce Platform marketplace split-pay (partner fee) settles directly to sellers but conflicts with our hold model. **Not recommended** for consistency.
  - **Partner Integration Required**: Requires Partner Referrals API access (approved partners only) with BN code for marketplace functionality.
- Square:
  - **Primary Model**: Direct-settlement via OAuth-connected seller accounts using `application_fee_money` for platform commissions. This conflicts with our hold model but is Square's preferred marketplace approach.
  - **Application Fee Limits**: Max 90% fee when payment ≥ $5.00, max 60% fee when payment < $5.00. Requires PAYMENTS_WRITE_ADDITIONAL_RECIPIENTS OAuth permission.
  - **Fund Access**: Square Instant Transfer available (funds in ~1 minute for a fee), standard payouts next business day. Platform holds not supported in typical Square marketplace model.
  - **Recommendation**: Mark Square as "direct-settlement only" and disable payout scheduling for Square transactions. Feature-flag this behavior per environment while validating with Square compliance.
  - **Alternative**: Platform-collect via our own Square merchant theoretically possible but not Square's intended marketplace model and may face policy restrictions.

---

## 2.4 Provider Capability Comparison & Critical Findings

### Platform-Collect vs Direct-Settlement Support Matrix

| Provider | Platform-Collect | Direct-Settlement | Hold Periods | Payout Control |
|----------|------------------|-------------------|--------------|----------------|
| **Stripe** | ✅ Preferred | ✅ Available | ✅ Full Control | ✅ Full Control |
| **PayPal** | ✅ Available | ⚠️ Limited | ✅ Full Control | ✅ Full Control |
| **Square** | ⚠️ Not Standard | ✅ Preferred | ❌ Not Supported | ❌ Limited |

### Critical Integration Considerations

**PayPal Considerations:**
- ✅ **Excellent fit** for platform-collect model with robust Payouts API
- ✅ Supports large batch payouts (15,000 per call) with reasonable limits
- ⚠️ Requires Partner Referrals API approval and BN code
- ⚠️ Must maintain PayPal balance for funding payouts

**Square Considerations:**
- ❌ **Fundamental conflict** with MixPitch's hold period requirements
- ❌ Direct-settlement model bypasses platform payout scheduling entirely
- ⚠️ Requires different workflow logic and UI messaging for "instant settlement"
- ⚠️ OAuth complexity with seller access tokens and PAYMENTS_WRITE_ADDITIONAL_RECIPIENTS permission
- ✅ Well-documented marketplace APIs with clear fee structure

**Recommended Implementation Strategy:**
1. **Phase 1**: Implement Stripe refactor + PayPal (both support platform-collect)
2. **Phase 2**: Add Square with explicit "instant settlement" mode and feature flags
3. **UI Clarity**: Clearly communicate settlement timing differences to users

---

## 3) Data Model Changes

### 3.1 Required: `payment_accounts` (Multi-provider Support)

**NEW TABLE NEEDED** - The existing codebase only supports single Stripe account per user.

Columns:
- `id`
- `user_id` (FK `users.id`)
- `provider` (enum: `stripe`, `paypal`, `square`)
- `external_account_id` (Stripe account ID, PayPal merchant ID, Square merchant ID)
- `display_name` (nullable, e.g., "Stripe Connect", "PayPal Business")
- `status` (enum: `pending`, `active`, `restricted`, `disabled`)
- `capabilities` (JSON; e.g., `{"transfers": true, "card_payments": true}`)
- `access_token` (nullable, encrypted; for Square/PayPal OAuth)
- `refresh_token` (nullable, encrypted)
- `token_expires_at` (nullable)
- `is_default` (boolean)
- `metadata` (JSON)
- Timestamps

**Migration Strategy:**
- Backfill existing `users.stripe_account_id` into `payment_accounts` table
- Keep `users.stripe_account_id` for backward compatibility during transition
- Add User model accessor to find default Stripe account from `payment_accounts`

### 3.2 Current `transactions` Table is ALREADY Provider-Agnostic

**VALIDATION**: ✅ Existing schema is well-designed for multi-provider support

Current fields perfectly support the plan:
- ✅ `payment_method` (varchar) - Ready for 'stripe', 'paypal', 'square'
- ✅ `external_transaction_id` (varchar) - Can store any provider's transaction ID
- ✅ `producer_user_id` - Producer receiving payout
- ✅ `workflow_type` - Supports all MixPitch workflows
- ✅ `amount`, `commission_rate`, `commission_amount`, `net_amount` - Complete financial tracking
- ✅ `metadata` (JSON) - Provider-specific data storage

**NO CHANGES NEEDED** to `transactions` table structure.

### 3.3 Current `stripe_transactions` Table as Provider Template

**VALIDATION**: ✅ Existing Stripe-specific table provides perfect template

Could create equivalent tables:
- `paypal_transactions` - Following same pattern
- `square_transactions` - Following same pattern

OR extend existing table with provider field and rename to `provider_transactions`.

### 3.4 Current `payout_schedules` Table is ALREADY Multi-Provider Ready

**VALIDATION**: ✅ Existing schema supports multi-provider payouts

Current fields support the plan:
- ✅ `producer_stripe_account_id` - Can be generalized to `producer_account_id` with provider context
- ✅ `workflow_type` - All workflows supported
- ✅ `metadata` (JSON) - Provider-specific payout data

**MINIMAL CHANGES NEEDED**: Add `provider` enum field and generalize account ID field.

### 3.5 Assessment: Codebase is Better Prepared Than Plan Assumed

The existing database schema is **exceptionally well-designed** for multi-provider support:
- Transaction tracking is already provider-agnostic
- Payout scheduling supports multiple workflows
- Financial calculations are abstracted from payment providers
- Metadata fields allow provider-specific data storage

**Required changes are MUCH smaller than originally planned.**

---

## 4) Service Layer: Provider Implementations

Create `app/Services/Payments/Providers/*` with the following classes. Start by refactoring existing Stripe logic into `StripeProvider` classes and use them via the new interfaces.

- `StripePaymentGateway` (wraps current PaymentIntent/Checkout/Invoices logic used in `BillingController`, `OrderController`, `InvoiceService`)
- `StripePayoutGateway` (extracts from `StripeConnectService`)
- `StripeRefundGateway` (use existing refund request service hooks)

- `PayPalPaymentGateway`
  - Use PayPal Checkout (Orders API) for platform-collect model.
  - `partner_fee_details` can still be set if desired, but we will not settle directly to sellers in phase 1.
- `PayPalPayoutGateway`
  - Use PayPal Payouts for disbursement to seller’s PayPal account when holds release.
  - Onboarding: PayPal Commerce Platform Partner Referrals to collect merchant details and (if needed) permissions for payouts.

- `SquarePaymentGateway`
  - Prefer platform-collect (if permitted). Otherwise flag as direct-settlement mode where payout scheduling is bypassed for Square.
  - For direct-settlement mode: create payments in the seller’s Square account using their `access_token` and `location_id`, applying `application_fee_money` for MixPitch commission.
- `SquarePayoutGateway`
  - If platform-collect is supported, disburse via Square payouts API or bank transfer from platform account to seller. Otherwise noop (direct-settlement).

Common helpers:

- `PaymentProviderManager` to resolve gateway instances by provider.
- `GatewayRouterService` to decide which provider to invoke per transaction based on recipient settings and request type.

---

## 5) Onboarding Flows (Recipients)

Unify onboarding UI in a single settings screen where producers can enable Stripe, PayPal, and Square.

- Stripe: reuse `StripeConnectService` via `StripePayoutGateway`. Keep Express onboarding and account login link.
- PayPal:
  - Implement Partner Referrals onboarding (PayPal Commerce Platform). Store `paypal_merchant_id`, onboarding status, and capability flags in `payment_accounts`.
  - For payouts, ensure the account can receive Payouts and is verified. If platform-collect, no direct settlement is performed; only payouts are needed on hold release.
- Square:
  - Implement OAuth connect flow to obtain `merchant_id`, `access_token`, and discover `location_id`.
  - Store credentials encrypted in `payment_accounts` and derive capabilities.

All flows must persist and expose a unified `AccountStatus` model so UI can show readiness and errors consistently.

---

## 6) Checkout and Payment Flows (Payers)

UI changes:

- Payment screen (e.g., `resources/views/pitches/payment/overview.blade.php`, order checkout, client portal) adds a provider selector populated from recipient’s active `payment_accounts`.
- When a provider is chosen, call the corresponding `PaymentGateway` to either:
  - Create a hosted checkout session URL (redirect), or
  - Execute a direct/off-session charge if requested (e.g., Stripe invoice payment with a saved card).

Server flow:

1) Build a `PaymentRequest` including: payer, payee, project/pitch/order context, currency, amount, commission, and metadata.
2) `GatewayRouterService` selects the provider based on recipient settings and payer selection.
3) Create session or charge; persist a `PaymentTransaction` as pending with provider identifiers.
4) Redirect payer or confirm charge result.
5) Webhook confirms success and triggers `PayoutProcessingService.schedule` when appropriate.

Commission handling:

- Platform-collect model: record commission internally; actual payout will send only net to the recipient.
- Direct-settlement model (if enabled for Square or optional for Stripe): use provider-specific fee fields (`application_fee_amount` in Stripe, `application_fee_money` in Square) and disable our payout scheduling for those transactions. Clearly mark in the UI and admin which transactions are “direct-settlement”.

---

## 7) Payout Scheduling

`PayoutProcessingService` remains the orchestrator. Changes:

- Select `PayoutGateway` based on the recipient’s default payout account for the transaction’s provider.
- If the transaction is “platform-collect”, execute a provider transfer/payout at hold release time.
- If “direct-settlement”, mark payout as non-applicable and skip.
- Persist `provider_transfer_id`, status, timestamps, and error metadata.

---

## 8) Webhooks

Add provider-specific webhook endpoints and unify event handling into provider-agnostic events.

- Stripe: Keep existing `Billing\WebhookController`. Internally route parsed events to `PaymentEventService` with normalized event shapes.
- PayPal: Add `POST /webhooks/paypal` with signature verification. Handle `CHECKOUT.ORDER.APPROVED`, `PAYMENT.CAPTURE.COMPLETED`, refund and dispute events.
- Square: Add `POST /webhooks/square` with signature verification. Handle payment updates, refunds, and disputes (if available).

Normalize key events:

- Payment succeeded/failed
- Refund succeeded/failed
- Dispute opened/closed

Each updates `PaymentTransaction` and, when relevant, schedules or cancels payouts.

---

## 9) Refunds and Disputes

- Refunds: Implement via `RefundGateway` per provider. If a payout was already executed (platform-collect), either reverse transfer (Stripe) or schedule a negative payout/offset entry. For direct-settlement providers, instruct provider-side refund and update MixPitch ledgers accordingly.
- Disputes: Subscribe to provider dispute webhooks, annotate transactions, and pause pending payouts for affected transactions until resolved. For platform-collect, be ready to reverse or offset.

---

## 10) Security, Compliance, and Risk

- Secrets: Store provider tokens encrypted (Square/PayPal). Leverage Laravel’s `encrypt`/`Hash` and keep secrets out of logs.
- KYC/Compliance: Stripe Connect Express handles KYC; PayPal Partner Referrals handles merchant onboarding; Square OAuth ensures seller account compliance. Document requirements and error states surfaced in `AccountStatus`.
- PCI: Prefer hosted checkout where possible. Avoid handling raw card data outside Stripe Elements flows.
- Idempotency: Use idempotency keys for all create/charge/payout actions across providers.

---

## 11) Configuration

Environment variables (examples):

```env
# Stripe (existing)
STRIPE_KEY=...
STRIPE_SECRET=...
STRIPE_WEBHOOK_SECRET=...

# PayPal Commerce Platform
PAYPAL_CLIENT_ID=...
PAYPAL_CLIENT_SECRET=...
PAYPAL_WEBHOOK_ID=...
PAYPAL_ENV=sandbox|live
PAYPAL_PARTNER_MERCHANT_ID=...     # Required for Partner Referrals API
PAYPAL_BN_CODE=...                 # Partner Build Notation code (required)

# Square Connect API
SQUARE_APPLICATION_ID=...
SQUARE_APPLICATION_SECRET=...
SQUARE_ENV=sandbox|production
SQUARE_WEBHOOK_SIGNATURE_KEY=...
SQUARE_OAUTH_REDIRECT_URI=...      # For marketplace OAuth flow
```

**Critical Configuration Notes:**
- **PayPal BN Code**: Required for Partner Referrals API access. Must apply to PayPal for marketplace partner status.
- **PayPal Balance Funding**: Platform must maintain adequate PayPal balance for Payouts API disbursements.
- **Square OAuth**: Marketplace applications require approval and proper OAuth redirect URIs configured.

Add `config/payments.php` to centralize provider config and feature flags (e.g., `direct_settlement.square = false` initially).

---

## 12) UI/UX Changes

- Producer settings page: unified “Payout Accounts” with enable/connect buttons for Stripe, PayPal, Square. Show readiness, errors, and a “Make Default” action.
- Checkout/payment pages: present provider options filtered to payee’s enabled accounts. Include explanatory text when certain providers imply instant settlement (no hold) vs scheduled payout.
- Admin (Filament): deprecate `StripeTransactionResource` → introduce `PaymentTransactionResource` with provider filter. Add dashboards per provider.

---

## 13) Migration and Backward Compatibility

Phase A (Refactor-in-place for Stripe):

- Extract `Stripe*Gateway` wrappers around current logic.
- Introduce `PaymentProviderManager` and route existing calls through it for Stripe paths.
- Add `payment_accounts` and migrate `users.stripe_account_id` into it. Keep reading `users.stripe_account_id` where needed during transition.
- Standardize Order flow to platform-collect model (recommended) or explicitly flag as direct-settlement and skip payouts for that flow.

Phase B (Add PayPal):

- Implement PayPal onboarding, checkout, payouts, and webhooks.
- Enable for a small cohort of producers behind a feature flag.

Phase C (Add Square):

- Implement Square OAuth onboarding and payments. Start with platform-collect if feasible; otherwise ship direct-settlement mode behind a feature flag and disable payout scheduling for Square until compliance confirms an acceptable hold strategy.

Data Migration:

- Backfill `payment_accounts` for existing Stripe-connected users.
- Introduce `PaymentTransaction` and alias existing `StripeTransaction` reads until UIs are ported.

---

## 14) Testing Strategy

- Unit tests per provider for gate­ways (charge/session creation, payouts, refunds).
- Contract tests for webhook parsing/verification.
- Feature tests across flows:
  - Pitch payment end-to-end (create → webhook → schedule payout → transfer).
  - Order checkout with provider selection.
  - Refund life cycle and payout reversal.
- Sandbox/live test checklists per provider.

---

## 15) Observability

- Structured logs with `provider`, `transaction_id`, `payout_schedule_id`.
- Add a `payments:diagnostics` page for provider health (API reachability, webhook status, pending payouts count).
- Alerts for webhook failures, payout failures, and excessive pending counts.

---

## 16) Work Breakdown (High-level)

1) Add `payment_accounts` table and model; admin UI to view records.
2) Extract Stripe into `StripePaymentGateway`/`StripePayoutGateway`; route current flows through interfaces.
3) Normalize payout semantics (prefer platform-collect) and align Order flow.
4) Implement provider routing and checkout UI provider selector.
5) Introduce `PaymentTransaction` and dual-write from Stripe paths; migrate Filament resource.
6) Add PayPal (onboarding, checkout, payouts, webhooks) behind feature flags.
7) Add Square (onboarding, checkout, webhooks); decide payout strategy per compliance; feature flag direct-settlement if needed.
8) Complete migration: deprecate `StripeTransactionResource`, remove direct Stripe calls from controllers in favor of gateways.

---

## 17) File-Level Impact Summary (Comprehensive Analysis)

### Controllers (Refactor Required)
**Immediate Updates Needed:**
- ✅ `app/Http/Controllers/OrderController.php` - **CRITICAL**: Currently uses Destination Charges, needs provider routing
- ✅ `app/Http/Controllers/PitchPaymentController.php` - **UPDATE**: Add provider selection logic  
- ✅ `app/Http/Controllers/Billing/BillingController.php` - **MINIMAL**: Already provider-agnostic for most functions
- ✅ `app/Http/Controllers/Billing/WebhookController.php` - **EXTEND**: Add PayPal/Square webhook handling
- 🔄 `app/Http/Controllers/StripeConnectController.php` - **RENAME**: To generic `PaymentAccountController.php`

**New Controllers Needed:**
- 🆕 `app/Http/Controllers/PayPalWebhookController.php` 
- 🆕 `app/Http/Controllers/SquareWebhookController.php`

### Services (Well-Positioned for Abstraction)
**Current Services to Refactor:**
- ✅ `app/Services/InvoiceService.php` - **EXTRACT**: Create `StripePaymentGateway` wrapper
- ✅ `app/Services/StripeConnectService.php` - **WRAP**: As `StripePayoutGateway` implementation
- ✅ `app/Services/PayoutProcessingService.php` - **MINIMAL**: Already provider-agnostic orchestrator

**New Services Needed:**
- 🆕 `app/Services/Payments/PaymentProviderManager.php` - Gateway resolver
- 🆕 `app/Services/Payments/GatewayRouterService.php` - Provider selection logic
- 🆕 `app/Services/Payments/Providers/StripePaymentGateway.php` - Wrap existing InvoiceService
- 🆕 `app/Services/Payments/Providers/StripePayoutGateway.php` - Wrap existing StripeConnectService  
- 🆕 `app/Services/Payments/Providers/PayPalPaymentGateway.php` - New implementation
- 🆕 `app/Services/Payments/Providers/PayPalPayoutGateway.php` - New implementation
- 🆕 `app/Services/Payments/Providers/SquarePaymentGateway.php` - New implementation
- 🆕 `app/Services/Payments/Providers/SquarePayoutGateway.php` - New implementation

### Models and Database
**Models to Update:**
- ✅ `app/Models/User.php` - **ADD**: Multi-provider account methods
- ✅ `app/Models/Transaction.php` - **NO CHANGE**: Already provider-agnostic
- ✅ `app/Models/StripeTransaction.php` - **TEMPLATE**: Use as model for PayPal/Square equivalents

**New Models Needed:**
- 🆕 `app/Models/PaymentAccount.php` - Multi-provider account management

**Database Migrations:**
- 🆕 `create_payment_accounts_table.php` - New table
- 🔄 `add_provider_field_to_payout_schedules.php` - Minor extension
- 🔄 `backfill_stripe_accounts_to_payment_accounts.php` - Data migration

### Views (UI Updates Required)
**Payment Flow Views:**
- 🔄 `resources/views/pitches/payment/overview.blade.php` - Add provider selector
- 🔄 `resources/views/orders/checkout.blade.php` - Add provider selector  
- 🔄 `resources/views/client_portal/show.blade.php` - Update for multi-provider

**Settings/Onboarding Views:**
- 🔄 `resources/views/billing/index.blade.php` - Multi-provider account management
- 🆕 `resources/views/payment-accounts/manage.blade.php` - New unified settings page

### Configuration
**New Config Files:**
- 🆕 `config/payments.php` - Centralized provider configuration
- 🔄 `config/services.php` - Add PayPal/Square credentials
- 🔄 `.env.example` - Add new environment variables

### Admin/Filament Resources
**Existing Resources to Update:**
- 🔄 `app/Filament/Resources/StripeTransactionResource.php` - Extend or create generic PaymentTransactionResource
- 🔄 Billing widgets - Update for multi-provider statistics

### Critical Implementation Notes
1. **Existing `InvoiceService.php` and `StripeConnectService.php` are production-tested** - wrap, don't rewrite
2. **`PayoutProcessingService.php` already orchestrates providers** - minimal changes needed
3. **Database schema is exceptionally provider-ready** - much less work than anticipated
4. **Webhook system is comprehensive** - extend existing patterns for new providers

---

## 18) Risks and Mitigations

### High-Priority Risks

**Square Hold Period Incompatibility**
- **Risk**: Square's marketplace model fundamentally conflicts with MixPitch's hold periods and payout scheduling
- **Impact**: Producers receive funds immediately, bypassing dispute protection and platform controls
- **Mitigation**: Implement explicit "instant settlement" mode with clear UI warnings; feature-flag Square per environment; consider Square as premium/low-risk producer option only

**PayPal Partner Approval Requirements**
- **Risk**: PayPal Partner Referrals API requires approval that may be denied or delayed
- **Impact**: Cannot implement PayPal marketplace features without partner status
- **Mitigation**: Apply for partner status early in development; have fallback to standard PayPal checkout without split-pay features

**PayPal Balance Management**
- **Risk**: Payouts API requires pre-funded PayPal balance; insufficient funds block disbursements
- **Impact**: Delayed producer payments if platform balance runs low
- **Mitigation**: Implement balance monitoring with automated top-ups; alerts for low balance conditions

### Medium-Priority Risks

**Provider Policy Mismatches**
- **Risk**: Each provider has different marketplace policies and fee structures
- **Mitigation**: Feature-flag direct-settlement modes; maintain platform-collect as canonical model; validate compliance early

**Webhook Security & Duplication**
- **Risk**: Multiple provider webhooks could cause double-processing or security vulnerabilities
- **Mitigation**: Provider-specific signature verification; idempotency keys; centralized event deduplication

**OAuth Token Management**
- **Risk**: Square requires encrypted storage of seller access tokens with refresh cycles
- **Mitigation**: Proper encryption at rest; token refresh procedures; secure key rotation protocols

---

## 19) Next Steps

- Approve architecture and data model.
- Implement Stripe refactor (Phase A) and align payout semantics.
- Ship PayPal integration behind feature flag (Phase B).
- Decide Square payout approach with compliance, then implement (Phase C).

Appendix: Key Current Files (for reference)

- `app/Http/Controllers/OrderController.php` (Destination Charges path)
- `app/Http/Controllers/Billing/BillingController.php` (Invoices / one-time charges)
- `app/Http/Controllers/PitchPaymentController.php` → `InvoiceService`
- `app/Services/StripeConnectService.php` (Connect + transfers)
- `app/Services/PayoutProcessingService.php` (hold + transfer)
- `app/Http/Controllers/Billing/WebhookController.php` (webhooks)
- `app/Http/Controllers/SubscriptionController.php` (subscriptions)

