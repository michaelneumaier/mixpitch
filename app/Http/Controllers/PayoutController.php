<?php

namespace App\Http\Controllers;

use App\Models\PayoutSchedule;
use App\Models\RefundRequest;
use App\Services\PayoutProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PayoutController extends Controller
{
    protected PayoutProcessingService $payoutService;

    public function __construct(PayoutProcessingService $payoutService)
    {
        $this->payoutService = $payoutService;
        $this->middleware('auth');
    }

    /**
     * Display the producer payout dashboard
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Get filter parameters
        $filters = $request->only(['status', 'workflow_type', 'date_from']);

        // Get payout history with filters
        $payoutsQuery = PayoutSchedule::where('producer_user_id', $user->id)
            ->with(['project', 'pitch', 'contestPrize', 'transaction', 'refundRequests'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $payoutsQuery->where('status', $request->status);
        }

        if ($request->filled('workflow_type')) {
            $payoutsQuery->where('workflow_type', $request->workflow_type);
        }

        if ($request->filled('date_from')) {
            $payoutsQuery->where('created_at', '>=', $request->date_from);
        }

        $payouts = $payoutsQuery->paginate(20);

        // Get statistics
        $statistics = $this->getProducerStatistics($user);

        // Get recent refund requests
        $refundRequests = RefundRequest::where('producer_user_id', $user->id)
            ->with(['pitch.project'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('payouts.index', [
            'payouts' => $payouts,
            'statistics' => $statistics,
            'refundRequests' => $refundRequests,
        ]);
    }

    /**
     * Show detailed payout information
     */
    public function show(PayoutSchedule $payout): View
    {
        $user = Auth::user();

        // Ensure user can only view their own payouts
        if ($payout->producer_user_id !== $user->id) {
            abort(403, 'Unauthorized access to payout information.');
        }

        $payout->load(['project', 'pitch', 'contestPrize', 'transaction', 'refundRequests']);

        return view('payouts.show', [
            'payout' => $payout,
        ]);
    }

    /**
     * Get producer statistics
     */
    private function getProducerStatistics($user): array
    {
        $payouts = PayoutSchedule::where('producer_user_id', $user->id);

        return [
            'total_earnings' => $payouts->where('status', PayoutSchedule::STATUS_COMPLETED)->sum('net_amount'),
            'completed_payouts' => $payouts->where('status', PayoutSchedule::STATUS_COMPLETED)->count(),
            'pending_payouts' => $payouts->whereIn('status', [
                PayoutSchedule::STATUS_SCHEDULED,
                PayoutSchedule::STATUS_PROCESSING,
            ])->count(),
            'total_gross' => $payouts->where('status', PayoutSchedule::STATUS_COMPLETED)->sum('gross_amount'),
            'total_commission' => $payouts->where('status', PayoutSchedule::STATUS_COMPLETED)->sum('commission_amount'),
            'failed_payouts' => $payouts->where('status', PayoutSchedule::STATUS_FAILED)->count(),
            'cancelled_payouts' => $payouts->where('status', PayoutSchedule::STATUS_CANCELLED)->count(),
        ];
    }

    /**
     * Export payout history as CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        $payouts = PayoutSchedule::where('producer_user_id', $user->id)
            ->with(['project', 'pitch', 'contestPrize'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'payout-history-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($payouts) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Date',
                'Project',
                'Pitch',
                'Type',
                'Status',
                'Gross Amount',
                'Commission Rate',
                'Commission Amount',
                'Net Amount',
                'Release Date',
                'Transfer ID',
            ]);

            // CSV data
            foreach ($payouts as $payout) {
                fputcsv($file, [
                    $payout->created_at->format('Y-m-d'),
                    $payout->project->name ?? 'N/A',
                    $payout->pitch->title ?? 'N/A',
                    ucfirst($payout->workflow_type),
                    ucfirst($payout->status),
                    '$'.number_format($payout->gross_amount, 2),
                    $payout->commission_rate.'%',
                    '$'.number_format($payout->commission_amount, 2),
                    '$'.number_format($payout->net_amount, 2),
                    $payout->hold_release_date ? $payout->hold_release_date->format('Y-m-d') : 'N/A',
                    $payout->stripe_transfer_id ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get payout statistics for AJAX requests
     */
    public function statistics(): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $statistics = $this->getProducerStatistics($user);

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
        ]);
    }
}
