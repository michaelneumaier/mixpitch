<?php

namespace App\Http\Controllers;

use App\Services\PayoutAccountManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PayoutSetupController extends Controller
{
    protected PayoutAccountManagementService $accountService;

    public function __construct(PayoutAccountManagementService $accountService)
    {
        $this->accountService = $accountService;
        $this->middleware('auth');
    }

    /**
     * Show the multi-provider payout setup page
     */
    public function index(): View
    {
        $user = Auth::user();
        $accountSummary = $this->accountService->getAccountSummary($user);

        return view('payouts.setup', [
            'user' => $user,
            'accountSummary' => $accountSummary,
        ]);
    }

    /**
     * Get account summary as JSON (for AJAX updates)
     */
    public function summary(Request $request)
    {
        $user = Auth::user();
        $accountSummary = $this->accountService->getAccountSummary($user);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $accountSummary,
            ]);
        }

        return redirect()->route('payouts.setup.index');
    }
}
