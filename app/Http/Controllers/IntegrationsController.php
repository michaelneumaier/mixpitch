<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class IntegrationsController extends Controller
{
    public function __construct(
        protected GoogleDriveService $googleDriveService
    ) {}

    /**
     * Display the integrations dashboard
     */
    public function index(): View
    {
        $user = Auth::user();

        // Get integration statuses
        $integrations = [
            'zapier' => [
                'name' => 'Zapier',
                'description' => 'Automate workflows and connect MixPitch with 5,000+ apps',
                'status' => $this->getZapierStatus($user),
                'icon' => 'zapier',
                'route' => 'integrations.zapier.dashboard',
                'setup_route' => 'integrations.zapier.dashboard',
            ],
            'google_drive' => [
                'name' => 'Google Drive',
                'description' => 'Sync and backup your project files to Google Drive',
                'status' => $this->getGoogleDriveStatus($user),
                'icon' => 'google-drive',
                'route' => 'integrations.google-drive.setup',
                'setup_route' => 'integrations.google-drive.setup',
            ],
        ];

        return view('integrations.index', compact('integrations'));
    }

    /**
     * Get Zapier integration status
     */
    protected function getZapierStatus($user): array
    {
        $hasApiKey = ! empty($user->zapier_api_key);

        return [
            'connected' => $hasApiKey,
            'connected_at' => $user->zapier_connected_at,
            'needs_setup' => ! $hasApiKey,
            'stats' => $hasApiKey ? $this->getZapierStats($user) : null,
        ];
    }

    /**
     * Get Google Drive integration status
     */
    protected function getGoogleDriveStatus($user): array
    {
        $connectionStatus = $this->googleDriveService->getConnectionStatus($user);

        return [
            'connected' => $connectionStatus['connected'],
            'connected_at' => $connectionStatus['connected_at'],
            'needs_setup' => ! $connectionStatus['connected'] || $connectionStatus['needs_reauth'],
            'needs_reauth' => $connectionStatus['needs_reauth'] ?? false,
            'stats' => $connectionStatus['connected'] ? $this->getGoogleDriveStats($user) : null,
        ];
    }

    /**
     * Get Zapier usage statistics
     */
    protected function getZapierStats($user): array
    {
        // Get basic Zapier stats - you may want to expand this based on your existing Zapier implementation
        return [
            'webhooks_received' => $user->zapier_webhooks_count ?? 0,
            'last_activity' => $user->zapier_last_activity_at,
        ];
    }

    /**
     * Get Google Drive usage statistics
     */
    protected function getGoogleDriveStats($user): array
    {
        return $this->googleDriveService->getBackupStats($user);
    }
}
