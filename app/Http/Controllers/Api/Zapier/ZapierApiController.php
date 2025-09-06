<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class ZapierApiController extends Controller
{
    /**
     * Return a successful API response
     */
    protected function successResponse(mixed $data, ?string $message = null): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $data,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        return response()->json($response);
    }

    /**
     * Return an error API response
     */
    protected function errorResponse(string $message, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
        ], $statusCode);
    }

    /**
     * Check if Zapier integration is enabled for the user
     */
    protected function checkZapierEnabled(): bool
    {
        return config('zapier.enabled', false);
    }

    /**
     * Check if the current token has Zapier abilities
     */
    protected function checkZapierToken(): bool
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return false;
        }

        $token = $user->currentAccessToken();
        if (!$token) {
            return false;
        }

        // Check if this is a Zapier token by name and abilities
        return $token->name === config('zapier.api_token_name') &&
               $token->can('zapier-client-management');
    }

    /**
     * Generate client portal URL for a project
     * TODO: Verify actual route name from existing codebase
     */
    protected function generateClientPortalUrl(\App\Models\Project $project): string
    {
        // This may need to be updated based on actual route names
        // Check: php artisan route:list | grep client
        try {
            return route('client.portal.view', $project->id);
        } catch (\Exception $e) {
            // Fallback if route doesn't exist yet
            return url("/client/portal/{$project->id}");
        }
    }

    /**
     * Sanitize request data for logging (remove sensitive information)
     */
    protected function sanitizeRequestData(array $data): array
    {
        // Remove sensitive fields
        $sensitiveFields = ['password', 'api_key', 'token', 'secret'];
        
        foreach ($sensitiveFields as $field) {
            unset($data[$field]);
        }

        // Truncate large fields
        foreach ($data as $key => $value) {
            if (is_string($value) && strlen($value) > 1000) {
                $data[$key] = substr($value, 0, 1000) . '...';
            }
        }

        return $data;
    }
}