<?php

namespace App\Http\Controllers;

use App\Services\Payouts\PayPalProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PayPalController extends Controller
{
    protected PayPalProvider $paypalProvider;

    public function __construct(PayPalProvider $paypalProvider)
    {
        $this->paypalProvider = $paypalProvider;
    }

    /**
     * Handle PayPal onboarding return
     * Called when user completes PayPal Connect flow
     */
    public function onboardingReturn(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Authentication required');
        }

        // PayPal returns these parameters after onboarding
        $merchantId = $request->input('merchantId');
        $merchantIdInPayPal = $request->input('merchantIdInPayPal');
        $permissionsGranted = $request->input('permissionsGranted', 'true');
        $accountStatus = $request->input('accountStatus');
        $consentStatus = $request->input('consentStatus');
        $productIntentId = $request->input('productIntentId');
        $isEmailConfirmed = $request->input('isEmailConfirmed', 'false');

        try {
            // Complete the onboarding process
            $result = $this->paypalProvider->completeOnboarding(
                $user,
                $merchantIdInPayPal ?: $merchantId,
                $permissionsGranted
            );

            if ($result['success']) {
                Log::info('PayPal onboarding completed successfully', [
                    'user_id' => $user->id,
                    'merchant_id' => $merchantIdInPayPal ?: $merchantId,
                    'permissions' => $permissionsGranted,
                ]);

                return redirect()->route('payouts.setup.index')
                    ->with('success', 'PayPal account connected successfully!');
            } else {
                Log::error('PayPal onboarding completion failed', [
                    'user_id' => $user->id,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);

                return redirect()->route('payouts.setup.index')
                    ->with('error', 'Failed to complete PayPal setup. Please try again.');
            }

        } catch (\Exception $e) {
            Log::error('PayPal onboarding return error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'request_params' => $request->all(),
            ]);

            return redirect()->route('payouts.setup.index')
                ->with('error', 'An error occurred during PayPal setup. Please contact support.');
        }
    }

    /**
     * Handle PayPal webhook events
     * Called by PayPal for important account/payment events
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            // Validate webhook signature
            $headers = $request->headers->all();
            $body = $request->getContent();

            if (! $this->paypalProvider->validateWebhookSignature($headers, $body)) {
                Log::warning('PayPal webhook signature validation failed', [
                    'headers' => $headers,
                ]);

                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $event = $request->json()->all();
            $eventType = $event['event_type'] ?? 'unknown';

            Log::info('PayPal webhook received', [
                'event_type' => $eventType,
                'event_id' => $event['id'] ?? null,
            ]);

            // Handle different webhook events
            switch ($eventType) {
                case 'MERCHANT.ONBOARDING.COMPLETED':
                    $this->handleMerchantOnboardingCompleted($event);
                    break;

                case 'MERCHANT.PARTNER-CONSENT.REVOKED':
                    $this->handlePartnerConsentRevoked($event);
                    break;

                case 'PAYMENT.CAPTURE.COMPLETED':
                    $this->handlePaymentCaptureCompleted($event);
                    break;

                case 'PAYMENT.CAPTURE.REFUNDED':
                    $this->handlePaymentCaptureRefunded($event);
                    break;

                default:
                    Log::info('Unhandled PayPal webhook event type', [
                        'event_type' => $eventType,
                    ]);
                    break;
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('PayPal webhook processing error', [
                'error' => $e->getMessage(),
                'request_body' => $request->getContent(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle merchant onboarding completed webhook
     */
    protected function handleMerchantOnboardingCompleted(array $event): void
    {
        // Extract merchant info from webhook
        $merchantId = $event['resource']['merchant_id'] ?? null;

        if (! $merchantId) {
            Log::warning('PayPal onboarding completed webhook missing merchant ID', $event);

            return;
        }

        // Find user by stored onboarding link
        $onboardingRecord = \DB::table('paypal_onboarding_links')
            ->where('merchant_id', $merchantId)
            ->whereNull('completed_at')
            ->first();

        if (! $onboardingRecord) {
            Log::warning('No matching onboarding record found for merchant', [
                'merchant_id' => $merchantId,
            ]);

            return;
        }

        // Update account status
        \App\Models\UserPayoutAccount::where('user_id', $onboardingRecord->user_id)
            ->where('provider', 'paypal')
            ->update([
                'status' => 'active',
                'is_verified' => true,
                'verified_at' => now(),
                'paypal_payments_receivable' => true,
            ]);

        Log::info('PayPal merchant onboarding completed via webhook', [
            'user_id' => $onboardingRecord->user_id,
            'merchant_id' => $merchantId,
        ]);
    }

    /**
     * Handle partner consent revoked webhook
     */
    protected function handlePartnerConsentRevoked(array $event): void
    {
        $merchantId = $event['resource']['merchant_id'] ?? null;

        if (! $merchantId) {
            Log::warning('PayPal consent revoked webhook missing merchant ID', $event);

            return;
        }

        // Disable the account
        \App\Models\UserPayoutAccount::where('account_id', $merchantId)
            ->where('provider', 'paypal')
            ->update([
                'status' => 'disabled',
                'is_verified' => false,
                'paypal_payments_receivable' => false,
            ]);

        Log::info('PayPal partner consent revoked', [
            'merchant_id' => $merchantId,
        ]);
    }

    /**
     * Handle payment capture completed webhook
     */
    protected function handlePaymentCaptureCompleted(array $event): void
    {
        // Handle successful payment processing
        $paymentId = $event['resource']['id'] ?? null;
        $amount = $event['resource']['amount'] ?? null;

        Log::info('PayPal payment capture completed', [
            'payment_id' => $paymentId,
            'amount' => $amount,
        ]);

        // Update your payment records, send notifications, etc.
        // Implementation depends on your payment tracking system
    }

    /**
     * Handle payment capture refunded webhook
     */
    protected function handlePaymentCaptureRefunded(array $event): void
    {
        // Handle refund processing
        $refundId = $event['resource']['id'] ?? null;
        $amount = $event['resource']['amount'] ?? null;

        Log::info('PayPal payment refunded', [
            'refund_id' => $refundId,
            'amount' => $amount,
        ]);

        // Update your refund records, send notifications, etc.
        // Implementation depends on your refund tracking system
    }
}
