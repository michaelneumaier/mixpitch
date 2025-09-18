<?php

namespace App\Livewire;

use App\Services\ZapierUsageTrackingService;
use App\Services\ZapierWebhookService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class ZapierIntegrationDashboard extends Component
{
    use AuthorizesRequests;

    // API Key Management
    public $hasApiKey = false;

    public $apiKey = null;

    public $showApiKey = false;

    public $apiKeyCreatedAt = null;

    // Usage Analytics
    public $usageStats = null;

    public $quotaStatus = null;

    public $webhookStats = null;

    // Modal States
    public $showGenerateKeyModal = false;

    public $showRevokeKeyModal = false;

    public $showUsageReportModal = false;

    // Form Data
    public $usageReportDays = 30;

    protected ZapierUsageTrackingService $usageService;

    protected ZapierWebhookService $webhookService;

    public function boot(ZapierUsageTrackingService $usageService, ZapierWebhookService $webhookService)
    {
        $this->usageService = $usageService;
        $this->webhookService = $webhookService;
    }

    public function mount()
    {
        $this->checkExistingApiKey();
        $this->loadUsageStats();
        $this->loadWebhookStats();
    }

    public function render()
    {
        return view('livewire.zapier-integration-dashboard', [
            'isZapierEnabled' => config('zapier.enabled', false),
            'availableEventTypes' => ZapierWebhookService::getAvailableEventTypes(),
        ]);
    }

    /**
     * Check if user already has a Zapier API key
     */
    public function checkExistingApiKey()
    {
        $user = Auth::user();

        // Check if user has a Zapier token
        $zapierToken = $user->tokens()
            ->where('name', config('zapier.api_token_name', 'Zapier Integration'))
            ->where('abilities', 'like', '%zapier-client-management%')
            ->first();

        if ($zapierToken) {
            $this->hasApiKey = true;
            $this->apiKeyCreatedAt = $zapierToken->created_at;
        }
    }

    /**
     * Load usage statistics
     */
    public function loadUsageStats()
    {
        if (! $this->hasApiKey) {
            return;
        }

        try {
            $user = Auth::user();
            $this->usageStats = $this->usageService->getUserUsageStats($user, 30);
            $this->quotaStatus = $this->usageService->getUsageQuotaStatus($user);
        } catch (\Exception $e) {
            \Log::error('Failed to load Zapier usage stats', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Load webhook statistics
     */
    public function loadWebhookStats()
    {
        if (! $this->hasApiKey) {
            return;
        }

        try {
            $user = Auth::user();
            $this->webhookStats = $this->webhookService->getWebhookStats($user);
        } catch (\Exception $e) {
            \Log::error('Failed to load webhook stats', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate new Zapier API key
     */
    public function generateApiKey()
    {
        try {
            $user = Auth::user();

            // Revoke existing token if any
            $existingTokens = $user->tokens()
                ->where('name', config('zapier.api_token_name', 'Zapier Integration'))
                ->get();

            foreach ($existingTokens as $token) {
                $token->delete();
            }

            // Create new token
            $token = $user->createToken(
                config('zapier.api_token_name', 'Zapier Integration'),
                config('zapier.api_token_abilities', ['zapier-client-management'])
            );

            $this->apiKey = $token->plainTextToken;
            $this->hasApiKey = true;
            $this->apiKeyCreatedAt = now();
            $this->showApiKey = true;
            $this->showGenerateKeyModal = false;

            // Load stats now that we have an API key
            $this->loadUsageStats();
            $this->loadWebhookStats();

            Toaster::success('Zapier API key generated successfully! Copy it now - you won\'t see it again.');

            \Log::info('Zapier API key generated', [
                'user_id' => $user->id,
                'token_name' => config('zapier.api_token_name'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to generate Zapier API key', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            Toaster::error('Failed to generate API key. Please try again.');
        }
    }

    /**
     * Revoke Zapier API key
     */
    public function revokeApiKey()
    {
        try {
            $user = Auth::user();

            // Revoke all Zapier tokens
            $tokens = $user->tokens()
                ->where('name', config('zapier.api_token_name', 'Zapier Integration'))
                ->get();

            foreach ($tokens as $token) {
                $token->delete();
            }

            // Also deactivate all webhooks
            \App\Models\ZapierWebhook::where('user_id', $user->id)
                ->update(['is_active' => false]);

            $this->hasApiKey = false;
            $this->apiKey = null;
            $this->apiKeyCreatedAt = null;
            $this->showRevokeKeyModal = false;
            $this->usageStats = null;
            $this->quotaStatus = null;
            $this->webhookStats = null;

            Toaster::success('Zapier API key revoked successfully. All webhooks have been deactivated.');

            \Log::info('Zapier API key revoked', [
                'user_id' => $user->id,
                'tokens_revoked' => $tokens->count(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to revoke Zapier API key', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            Toaster::error('Failed to revoke API key. Please try again.');
        }
    }

    /**
     * Toggle API key visibility
     */
    public function toggleApiKeyVisibility()
    {
        $this->showApiKey = ! $this->showApiKey;
    }

    /**
     * Copy API key to clipboard (handled by frontend)
     */
    public function copyApiKey()
    {
        Toaster::success('API key copied to clipboard!');
    }

    /**
     * Refresh usage statistics
     */
    public function refreshStats()
    {
        $this->loadUsageStats();
        $this->loadWebhookStats();
        Toaster::success('Statistics refreshed!');
    }

    /**
     * Generate usage report
     */
    public function generateUsageReport()
    {
        if (! $this->hasApiKey) {
            Toaster::error('API key required to generate usage reports.');

            return;
        }

        try {
            $user = Auth::user();
            $report = $this->usageService->generateUsageReport($user, $this->usageReportDays);

            // Store report in session for download
            session(['zapier_usage_report' => $report]);

            $this->showUsageReportModal = false;

            // Dispatch browser event to trigger download
            $this->dispatch('download-usage-report');

            Toaster::success("Usage report generated for {$this->usageReportDays} days!");

        } catch (\Exception $e) {
            \Log::error('Failed to generate usage report', [
                'user_id' => Auth::id(),
                'days' => $this->usageReportDays,
                'error' => $e->getMessage(),
            ]);

            Toaster::error('Failed to generate usage report. Please try again.');
        }
    }

    /**
     * Test API key functionality
     */
    public function testApiKey()
    {
        if (! $this->hasApiKey) {
            Toaster::error('No API key available to test.');

            return;
        }

        try {
            $user = Auth::user();
            $token = $user->tokens()
                ->where('name', config('zapier.api_token_name'))
                ->first();

            if (! $token) {
                Toaster::error('API key not found.');

                return;
            }

            // Test the auth endpoint
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token->plain_text_token ?? 'test',
                'Accept' => 'application/json',
            ])->get(config('app.url').'/api/zapier/auth/test');

            if ($response->successful()) {
                Toaster::success('API key is working correctly!');
            } else {
                Toaster::error('API key test failed: '.$response->body());
            }

        } catch (\Exception $e) {
            \Log::error('API key test failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            Toaster::error('API key test failed. Please check your connection.');
        }
    }

    /**
     * Get quota status color for UI
     */
    public function getQuotaStatusColor(string $period): string
    {
        if (! $this->quotaStatus) {
            return 'gray';
        }

        $percentage = $this->quotaStatus['percentage_used'][$period] ?? 0;

        if ($percentage >= 90) {
            return 'red';
        }
        if ($percentage >= 75) {
            return 'yellow';
        }
        if ($percentage >= 50) {
            return 'blue';
        }

        return 'green';
    }

    /**
     * Get usage trend indication
     */
    public function getUsageTrend(): array
    {
        if (! $this->usageStats || ! isset($this->usageStats['daily_usage']) || count($this->usageStats['daily_usage']) < 7) {
            return ['trend' => 'stable', 'percentage' => 0];
        }

        $dailyUsage = $this->usageStats['daily_usage'];
        $recent7Days = array_slice($dailyUsage, -7, 7);
        $previous7Days = array_slice($dailyUsage, -14, 7);

        $recentSum = array_sum(array_column($recent7Days, 'requests'));
        $previousSum = array_sum(array_column($previous7Days, 'requests'));

        if ($previousSum == 0) {
            return ['trend' => 'stable', 'percentage' => 0];
        }

        $change = (($recentSum - $previousSum) / $previousSum) * 100;

        if ($change > 10) {
            return ['trend' => 'up', 'percentage' => round($change, 1)];
        } elseif ($change < -10) {
            return ['trend' => 'down', 'percentage' => round(abs($change), 1)];
        }

        return ['trend' => 'stable', 'percentage' => round(abs($change), 1)];
    }
}
