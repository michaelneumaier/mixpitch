<?php

namespace App\Http\Controllers;

use App\Models\RefundRequest;
use App\Models\Pitch;
use App\Services\RefundRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class RefundRequestController extends Controller
{
    protected RefundRequestService $refundService;

    public function __construct(RefundRequestService $refundService)
    {
        $this->refundService = $refundService;
        $this->middleware('auth')->except(['create', 'store']);
    }

    /**
     * Display producer's refund requests
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $refundRequestsQuery = RefundRequest::where('producer_user_id', $user->id)
            ->with(['pitch.project', 'payoutSchedule'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $refundRequestsQuery->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $refundRequestsQuery->where('created_at', '>=', $request->date_from);
        }

        $refundRequests = $refundRequestsQuery->paginate(20);

        // Get statistics
        $statistics = [
            'total_requests' => RefundRequest::where('producer_user_id', $user->id)->count(),
            'pending_requests' => RefundRequest::where('producer_user_id', $user->id)
                ->where('status', RefundRequest::STATUS_REQUESTED)->count(),
            'approved_requests' => RefundRequest::where('producer_user_id', $user->id)
                ->where('status', RefundRequest::STATUS_APPROVED)->count(),
            'denied_requests' => RefundRequest::where('producer_user_id', $user->id)
                ->where('status', RefundRequest::STATUS_DENIED)->count(),
        ];

        return view('refund-requests.index', [
            'refundRequests' => $refundRequests,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Show detailed refund request
     */
    public function show(RefundRequest $refundRequest): View
    {
        $user = Auth::user();

        // Ensure user can only view their own refund requests
        if ($refundRequest->producer_user_id !== $user->id) {
            abort(403, 'Unauthorized access to refund request.');
        }

        $refundRequest->load(['pitch.project', 'payoutSchedule', 'approvedBy', 'deniedBy']);

        return view('refund-requests.show', [
            'refundRequest' => $refundRequest,
        ]);
    }

    /**
     * Show client refund request form
     */
    public function create(Pitch $pitch): View
    {
        // Check if pitch is eligible for refund
        $eligibility = $this->refundService->checkRefundEligibility($pitch);

        if (!$eligibility['eligible']) {
            abort(403, $eligibility['reason']);
        }

        return view('refund-requests.create', [
            'pitch' => $pitch,
            'eligibility' => $eligibility,
        ]);
    }

    /**
     * Store client refund request
     */
    public function store(Request $request, Pitch $pitch): RedirectResponse
    {
        $request->validate([
            'client_email' => 'required|email',
            'reason' => 'required|string|min:10|max:1000',
            'additional_details' => 'nullable|string|max:2000',
        ]);

        try {
            $refundRequest = $this->refundService->createRefundRequest(
                $pitch,
                $request->client_email,
                $request->reason,
                [
                    'additional_details' => $request->additional_details,
                    'submitted_via' => 'web_form',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            return redirect()->route('refund-requests.confirmation', $refundRequest)
                ->with('success', 'Your refund request has been submitted successfully.');

        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'general' => [$e->getMessage()]
            ]);
        }
    }

    /**
     * Show refund request confirmation
     */
    public function confirmation(RefundRequest $refundRequest): View
    {
        $refundRequest->load(['pitch.project']);

        return view('refund-requests.confirmation', [
            'refundRequest' => $refundRequest,
        ]);
    }

    /**
     * Producer approves refund request
     */
    public function approve(Request $request, RefundRequest $refundRequest): RedirectResponse
    {
        $user = Auth::user();

        if ($refundRequest->producer_user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->refundService->approveRefund(
                $refundRequest,
                $user,
                $request->notes
            );

            return redirect()->route('refund-requests.show', $refundRequest)
                ->with('success', 'Refund request approved successfully. The refund will be processed shortly.');

        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Producer denies refund request
     */
    public function deny(Request $request, RefundRequest $refundRequest): RedirectResponse
    {
        $user = Auth::user();

        if ($refundRequest->producer_user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        try {
            $this->refundService->denyRefund(
                $refundRequest,
                $user,
                $request->reason
            );

            return redirect()->route('refund-requests.show', $refundRequest)
                ->with('success', 'Refund request denied. The case has been escalated to platform mediation.');

        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * API endpoint for client refund eligibility check
     */
    public function checkEligibility(Pitch $pitch): \Illuminate\Http\JsonResponse
    {
        $eligibility = $this->refundService->checkRefundEligibility($pitch);

        return response()->json([
            'success' => true,
            'eligibility' => $eligibility,
        ]);
    }

    /**
     * API endpoint for refund request statistics
     */
    public function statistics(): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();

        $statistics = [
            'total_requests' => RefundRequest::where('producer_user_id', $user->id)->count(),
            'by_status' => RefundRequest::where('producer_user_id', $user->id)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'recent_requests' => RefundRequest::where('producer_user_id', $user->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
            'pending_response' => RefundRequest::where('producer_user_id', $user->id)
                ->where('status', RefundRequest::STATUS_REQUESTED)
                ->where('response_deadline', '>', now())
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
        ]);
    }
}
