<?php

namespace App\Http\Controllers;

use App\Services\StripeConnectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class StripeConnectController extends Controller
{
    protected StripeConnectService $stripeConnectService;

    public function __construct(StripeConnectService $stripeConnectService)
    {
        $this->stripeConnectService = $stripeConnectService;
        $this->middleware('auth');
    }

    /**
     * Show Stripe Connect setup page
     */
    public function setup(): View
    {
        $user = Auth::user();
        $accountStatus = $this->stripeConnectService->getDetailedAccountStatus($user);

        return view('stripe-connect.setup', [
            'user' => $user,
            'accountStatus' => $accountStatus,
        ]);
    }

    /**
     * Start Stripe Connect onboarding process
     */
    public function startOnboarding(): RedirectResponse
    {
        $user = Auth::user();

        $result = $this->stripeConnectService->createOnboardingLink($user);

        if ($result['success']) {
            Log::info('Stripe Connect onboarding started', [
                'user_id' => $user->id,
                'onboarding_url' => $result['url'],
            ]);

            return redirect($result['url']);
        }

        return redirect()->route('stripe.connect.setup')
            ->withErrors(['error' => 'Failed to start onboarding: '.$result['error']]);
    }

    /**
     * Handle successful return from Stripe Connect onboarding
     */
    public function onboardingReturn(Request $request): RedirectResponse
    {
        $user = Auth::user();

        Log::info('Stripe Connect onboarding return', [
            'user_id' => $user->id,
            'stripe_account_id' => $user->stripe_account_id,
        ]);

        // Check account status
        $accountStatus = $this->stripeConnectService->getDetailedAccountStatus($user);

        if ($accountStatus['status'] === 'active') {
            return redirect()->route('stripe.connect.setup')
                ->with('success', 'Your Stripe Connect account has been successfully set up! You can now receive payouts.');
        } elseif (in_array($accountStatus['status'], ['pending_verification', 'under_review'])) {
            return redirect()->route('stripe.connect.setup')
                ->with('info', 'Your account setup is almost complete. Stripe may need additional information before you can receive payouts.');
        } else {
            return redirect()->route('stripe.connect.setup')
                ->with('warning', 'Account setup is incomplete. Please complete all required information.');
        }
    }

    /**
     * Handle refresh from Stripe Connect onboarding (when user needs to restart)
     */
    public function onboardingRefresh(Request $request): RedirectResponse
    {
        $user = Auth::user();

        Log::info('Stripe Connect onboarding refresh', [
            'user_id' => $user->id,
        ]);

        // Create a new onboarding link
        $result = $this->stripeConnectService->createOnboardingLink($user);

        if ($result['success']) {
            return redirect($result['url']);
        }

        return redirect()->route('stripe.connect.setup')
            ->withErrors(['error' => 'Failed to refresh onboarding: '.$result['error']]);
    }

    /**
     * Access Stripe Connect dashboard
     */
    public function dashboard(): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->stripe_account_id) {
            return redirect()->route('stripe.connect.setup')
                ->withErrors(['error' => 'You need to set up your Stripe Connect account first.']);
        }

        $result = $this->stripeConnectService->createLoginLink($user);

        if ($result['success']) {
            Log::info('Stripe Connect dashboard access', [
                'user_id' => $user->id,
                'dashboard_url' => $result['url'],
            ]);

            return redirect($result['url']);
        }

        return redirect()->route('stripe.connect.setup')
            ->withErrors(['error' => 'Failed to access dashboard: '.$result['error']]);
    }

    /**
     * Get account status via AJAX
     */
    public function accountStatus(): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $accountStatus = $this->stripeConnectService->getDetailedAccountStatus($user);

        return response()->json([
            'success' => true,
            'status' => $accountStatus,
        ]);
    }

    /**
     * Check if user can receive payouts
     */
    public function payoutEligibility(): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $canReceivePayouts = $this->stripeConnectService->isAccountReadyForPayouts($user);

        return response()->json([
            'success' => true,
            'can_receive_payouts' => $canReceivePayouts,
            'account_status' => $this->stripeConnectService->getDetailedAccountStatus($user),
        ]);
    }
}
