<?php

namespace App\Http\Controllers;

use App\Exceptions\GoogleDrive\GoogleDriveAuthException;
use App\Exceptions\GoogleDrive\GoogleDriveFileException;
use App\Exceptions\GoogleDrive\GoogleDriveQuotaException;
use App\Services\GoogleDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class GoogleDriveIntegrationController extends Controller
{
    protected GoogleDriveService $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
        $this->middleware('auth');
    }

    /**
     * Show Google Drive integration setup page
     */
    public function setup(): View
    {
        $user = Auth::user();
        $connectionStatus = $this->googleDriveService->getConnectionStatus($user);

        return view('integrations.google-drive.setup', [
            'user' => $user,
            'connectionStatus' => $connectionStatus,
        ]);
    }

    /**
     * Start Google Drive OAuth flow
     */
    public function connect(): RedirectResponse
    {
        try {
            $authUrl = $this->googleDriveService->getAuthUrl();

            Log::info('Google Drive OAuth flow started', [
                'user_id' => Auth::id(),
            ]);

            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('Failed to start Google Drive OAuth flow', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('integrations.google-drive.setup')
                ->with('error', 'Failed to connect to Google Drive. Please try again.');
        }
    }

    /**
     * Handle Google Drive OAuth callback
     */
    public function callback(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($request->has('error')) {
            Log::warning('Google Drive OAuth callback error', [
                'user_id' => $user->id,
                'error' => $request->get('error'),
                'error_description' => $request->get('error_description'),
            ]);

            return redirect()->route('integrations.google-drive.setup')
                ->with('error', 'Google Drive connection was cancelled or failed.');
        }

        if (! $request->has('code')) {
            return redirect()->route('integrations.google-drive.setup')
                ->with('error', 'Invalid Google Drive authorization response.');
        }

        try {
            $this->googleDriveService->handleCallback($request->get('code'), $user);

            Log::info('Google Drive connected successfully', [
                'user_id' => $user->id,
            ]);

            return redirect()->route('integrations.google-drive.setup')
                ->with('success', 'Google Drive connected successfully!');

        } catch (GoogleDriveAuthException $e) {
            Log::error('Google Drive OAuth callback failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('integrations.google-drive.setup')
                ->with('error', 'Failed to connect Google Drive: '.$e->getMessage());
        }
    }

    /**
     * Disconnect Google Drive
     */
    public function disconnect(): RedirectResponse
    {
        $user = Auth::user();

        try {
            $this->googleDriveService->disconnect($user);

            Log::info('Google Drive disconnected', [
                'user_id' => $user->id,
            ]);

            return redirect()->route('integrations.google-drive.setup')
                ->with('success', 'Google Drive disconnected successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to disconnect Google Drive', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('integrations.google-drive.setup')
                ->with('error', 'Failed to disconnect Google Drive. Please try again.');
        }
    }

    /**
     * Browse Google Drive files (AJAX endpoint)
     */
    public function browse(Request $request): JsonResponse
    {
        $user = Auth::user();

        try {
            $files = $this->googleDriveService->listFiles(
                $user,
                $request->get('folder_id'),
                (int) $request->get('per_page', 50),
                $request->get('page_token')
            );

            return response()->json([
                'success' => true,
                'data' => $files,
            ]);

        } catch (GoogleDriveAuthException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Google Drive authentication failed. Please reconnect your account.',
                'needs_reauth' => true,
            ], 401);

        } catch (GoogleDriveFileException $e) {
            Log::error('Google Drive file browsing failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load Google Drive files. Please try again.',
            ], 500);
        }
    }

    /**
     * Import file from Google Drive
     */
    public function importFile(Request $request): JsonResponse
    {
        $request->validate([
            'file_id' => 'required|string',
        ]);

        $user = Auth::user();

        try {
            $fileData = $this->googleDriveService->downloadFile($user, $request->get('file_id'));

            Log::info('Google Drive file imported successfully', [
                'user_id' => $user->id,
                'file_id' => $request->get('file_id'),
                'file_name' => $fileData['name'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $fileData,
                'message' => 'File imported successfully from Google Drive.',
            ]);

        } catch (GoogleDriveAuthException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Google Drive authentication failed. Please reconnect your account.',
                'needs_reauth' => true,
            ], 401);

        } catch (GoogleDriveQuotaException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Insufficient storage space to import this file.',
                'quota_exceeded' => true,
            ], 422);

        } catch (GoogleDriveFileException $e) {
            Log::error('Google Drive file import failed', [
                'user_id' => $user->id,
                'file_id' => $request->get('file_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to import file from Google Drive: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get connection status (AJAX endpoint)
     */
    public function status(): JsonResponse
    {
        $user = Auth::user();
        $connectionStatus = $this->googleDriveService->getConnectionStatus($user);

        return response()->json([
            'success' => true,
            'data' => $connectionStatus,
        ]);
    }
}
