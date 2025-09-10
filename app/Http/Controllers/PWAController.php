<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PWAController extends Controller
{
    /**
     * Get PWA manifest dynamically
     */
    public function manifest(): JsonResponse
    {
        $manifest = [
            'name' => config('app.name').' - Music Collaboration Platform',
            'short_name' => config('app.name'),
            'description' => 'Connect with producers, submit pitches, and collaborate on music projects',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'theme_color' => '#1f2937',
            'background_color' => '#ffffff',
            'lang' => app()->getLocale(),
            'dir' => 'ltr',
            'categories' => ['music', 'entertainment', 'productivity'],
            'icons' => $this->getIconsArray(),
            'shortcuts' => $this->getShortcuts(),
        ];

        // Add screenshots if they exist
        $screenshots = $this->getScreenshots();
        if (! empty($screenshots)) {
            $manifest['screenshots'] = $screenshots;
        }

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour
    }

    /**
     * Get PWA status and configuration
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'pwa_enabled' => true,
            'offline_support' => true,
            'push_notifications' => false, // Can be enabled later
            'background_sync' => true,
            'install_prompt' => true,
            'service_worker' => '/sw.js',
            'manifest' => '/site.webmanifest',
            'cache_version' => 'mixpitch-v1.0.0',
            'features' => [
                'offline_browsing' => true,
                'cached_content' => true,
                'install_prompt' => true,
                'background_sync' => true,
                'push_notifications' => false,
            ],
        ]);
    }

    /**
     * Clear PWA cache (admin only)
     */
    public function clearCache(): JsonResponse
    {
        // Only allow authenticated admin users
        if (! Auth::check() || ! Auth::user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            // Clear application cache
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'PWA cache cleared successfully',
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get offline-ready URLs for caching
     */
    public function offlineUrls(): JsonResponse
    {
        $urls = [
            '/',
            '/offline',
            '/dashboard',
            '/projects',
        ];

        // Add authenticated user specific URLs
        if (Auth::check()) {
            $urls[] = '/profile';
            $urls[] = '/billing';
        }

        return response()->json([
            'urls' => $urls,
            'static_assets' => [
                '/css/app.css',
                '/css/custom.css',
                '/css/homepage.css',
                '/js/app.js',
                '/logo.svg',
                '/logo.png',
            ],
        ]);
    }

    /**
     * Handle PWA install analytics (optional)
     */
    public function installEvent(Request $request): JsonResponse
    {
        $event = $request->input('event'); // 'prompt_shown', 'accepted', 'dismissed'
        $userAgent = $request->userAgent();

        // Log PWA install events for analytics
        \Log::info('PWA Install Event', [
            'event' => $event,
            'user_agent' => $userAgent,
            'user_id' => Auth::id(),
            'timestamp' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get browserconfig.xml for Windows tiles
     */
    public function browserconfig()
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<browserconfig>
    <msapplication>
        <tile>
            <square150x150logo src="/icons/icon-144x144.png"/>
            <TileColor>#1f2937</TileColor>
        </tile>
    </msapplication>
</browserconfig>';

        return response($xml)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=86400'); // Cache for 24 hours
    }

    /**
     * Get icons array for manifest
     */
    private function getIconsArray(): array
    {
        $sizes = [72, 96, 128, 144, 152, 192, 384, 512];
        $icons = [];

        foreach ($sizes as $size) {
            $icons[] = [
                'src' => "/icons/icon-{$size}x{$size}.png",
                'sizes' => "{$size}x{$size}",
                'type' => 'image/png',
                'purpose' => 'maskable any',
            ];
        }

        return $icons;
    }

    /**
     * Get shortcuts for manifest
     */
    private function getShortcuts(): array
    {
        $shortcuts = [
            [
                'name' => 'Browse Projects',
                'short_name' => 'Projects',
                'description' => 'View available music projects',
                'url' => '/projects',
                'icons' => [
                    [
                        'src' => '/icons/icon-96x96.png',
                        'sizes' => '96x96',
                    ],
                ],
            ],
        ];

        // Add authenticated user shortcuts
        if (Auth::check()) {
            $shortcuts[] = [
                'name' => 'My Dashboard',
                'short_name' => 'Dashboard',
                'description' => 'Access your personal dashboard',
                'url' => '/dashboard',
                'icons' => [
                    [
                        'src' => '/icons/icon-96x96.png',
                        'sizes' => '96x96',
                    ],
                ],
            ];
        }

        return $shortcuts;
    }

    /**
     * Get screenshots for manifest (if they exist)
     */
    private function getScreenshots(): array
    {
        $screenshots = [];

        if (file_exists(public_path('images/screenshot-wide.png'))) {
            $screenshots[] = [
                'src' => '/images/screenshot-wide.png',
                'sizes' => '1280x720',
                'type' => 'image/png',
                'form_factor' => 'wide',
                'label' => 'MixPitch Desktop Interface',
            ];
        }

        if (file_exists(public_path('images/screenshot-narrow.png'))) {
            $screenshots[] = [
                'src' => '/images/screenshot-narrow.png',
                'sizes' => '390x844',
                'type' => 'image/png',
                'form_factor' => 'narrow',
                'label' => 'MixPitch Mobile Interface',
            ];
        }

        return $screenshots;
    }
}
