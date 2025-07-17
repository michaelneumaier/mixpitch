<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LicenseTemplate;
use Illuminate\Support\Facades\Auth;

class LicenseController extends Controller
{
    /**
     * Get license preview content
     */
    public function preview(LicenseTemplate $license)
    {
        try {
            // Check if user can access this license
            if (! $this->canUserAccessLicense($license)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this license template',
                ], 403);
            }

            // Get the rendered license content and properly format it
            $content = $license->generateLicenseContent();

            // Convert newlines to HTML breaks and escape content properly
            $formattedContent = nl2br(e($content));

            return response()->json([
                'success' => true,
                'license' => [
                    'id' => $license->id,
                    'name' => $license->name,
                    'description' => $license->description,
                    'category' => $license->category,
                    'content' => $formattedContent,
                    'license_terms' => $license->terms,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading license preview',
            ], 500);
        }
    }

    /**
     * Check if user can access license
     */
    private function canUserAccessLicense(LicenseTemplate $license): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // User can access their own licenses
        if ($license->user_id === $user->id) {
            return true;
        }

        // User can access system templates
        if ($license->is_system_template || $license->user_id === null) {
            return true;
        }

        // User can access marketplace licenses
        if (isset($license->is_marketplace) && $license->is_marketplace) {
            return true;
        }

        // User can access public licenses
        if ($license->is_public) {
            return true;
        }

        return false;
    }
}
