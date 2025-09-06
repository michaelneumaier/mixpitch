<?php

namespace App\Http\Controllers\Api\Zapier;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZapierApiKeyController extends ZapierApiController
{
    /**
     * Generate a new API key for Zapier integration
     */
    public function generate(Request $request): JsonResponse
    {
        if (!$this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        try {
            // Revoke existing Zapier tokens (following existing codebase pattern)
            $request->user()->tokens()
                ->where('name', config('zapier.api_token_name'))
                ->delete();

            // Create new token with Zapier-specific abilities
            $apiKey = $request->user()->createToken(
                config('zapier.api_token_name'),
                config('zapier.api_token_abilities')
            );

            // Log API key generation
            Log::info('Zapier API key generated', [
                'user_id' => $request->user()->id,
                'token_id' => $apiKey->accessToken->id,
            ]);

            return $this->successResponse([
                'api_key' => $apiKey->plainTextToken,
                'expires_at' => null, // Sanctum tokens don't expire by default
                'abilities' => config('zapier.api_token_abilities'),
            ], 'API key generated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to generate Zapier API key', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to generate API key', 500);
        }
    }

    /**
     * Revoke the current Zapier API key
     */
    public function revoke(Request $request): JsonResponse
    {
        try {
            $deletedCount = $request->user()->tokens()
                ->where('name', config('zapier.api_token_name'))
                ->delete();

            Log::info('Zapier API key revoked', [
                'user_id' => $request->user()->id,
                'deleted_tokens' => $deletedCount,
            ]);

            return $this->successResponse([
                'revoked_tokens' => $deletedCount,
            ], 'API key revoked successfully');

        } catch (\Exception $e) {
            Log::error('Failed to revoke Zapier API key', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to revoke API key', 500);
        }
    }

    /**
     * Get status of current Zapier API key
     */
    public function status(Request $request): JsonResponse
    {
        $token = $request->user()->tokens()
            ->where('name', config('zapier.api_token_name'))
            ->first();

        if (!$token) {
            return $this->successResponse([
                'has_token' => false,
                'created_at' => null,
                'last_used_at' => null,
            ]);
        }

        return $this->successResponse([
            'has_token' => true,
            'created_at' => $token->created_at->toISOString(),
            'last_used_at' => $token->last_used_at?->toISOString(),
            'abilities' => $token->abilities,
        ]);
    }

    /**
     * Test endpoint for Zapier authentication
     */
    public function test(Request $request): JsonResponse
    {
        if (!$this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (!$this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        // This endpoint is called by Zapier to verify the API key works
        $user = $request->user();

        return $this->successResponse([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'integration_status' => 'connected',
            'timestamp' => now()->toISOString(),
        ], 'Authentication successful');
    }
}