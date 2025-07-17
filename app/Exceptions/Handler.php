<?php

namespace App\Exceptions;

use App\Exceptions\Pitch\InvalidStatusTransitionException;
use App\Exceptions\Pitch\PitchException;
use App\Exceptions\Pitch\SnapshotException;
use App\Exceptions\Pitch\UnauthorizedActionException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // Log all pitch-related exceptions with additional context
        $this->reportable(function (PitchException $e) {
            Log::error('Pitch Exception: '.$e->getMessage(), [
                'exception' => get_class($e),
                'status_code' => $e->getStatusCode(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id() ?? 'unauthenticated',
            ]);
        });

        // Handle Invalid Status Transition Exceptions
        $this->renderable(function (InvalidStatusTransitionException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'current_status' => $e->getCurrentStatus(),
                    'target_status' => $e->getTargetStatus(),
                ], $e->getStatusCode());
            }

            return back()->withInput()->with('error', $e->getMessage());
        });

        // Handle Unauthorized Action Exceptions
        $this->renderable(function (UnauthorizedActionException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'action' => $e->getAction(),
                ], $e->getStatusCode());
            }

            return back()->withInput()->with('error', $e->getMessage());
        });

        // Handle Snapshot Exceptions
        $this->renderable(function (SnapshotException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'snapshot_id' => $e->getSnapshotId(),
                ], $e->getStatusCode());
            }

            return back()->withInput()->with('error', $e->getMessage());
        });

        // Handle general Pitch Exceptions
        $this->renderable(function (PitchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => $e->getMessage(),
                ], $e->getStatusCode());
            }

            return back()->withInput()->with('error', $e->getMessage());
        });

        // Default exception handler
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
