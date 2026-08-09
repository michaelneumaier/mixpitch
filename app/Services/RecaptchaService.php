<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaService
{
    protected const SITEVERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public function isEnabled(): bool
    {
        return (bool) config('recaptcha.enabled')
            && config('recaptcha.api_site_key')
            && config('recaptcha.api_secret_key');
    }

    /**
     * Verify a reCAPTCHA token against Google's siteverify endpoint.
     */
    public function validate(?string $token, ?string $ip = null): bool
    {
        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('recaptcha.timeout', 10))
                ->post(self::SITEVERIFY_URL, array_filter([
                    'secret' => config('recaptcha.api_secret_key'),
                    'response' => $token,
                    'remoteip' => $ip,
                ]));
        } catch (\Throwable $e) {
            Log::warning('reCAPTCHA verification request failed', ['error' => $e->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('reCAPTCHA verification returned non-2xx response', ['status' => $response->status()]);

            return false;
        }

        $result = $response->json();

        if (! ($result['success'] ?? false)) {
            return false;
        }

        $minScore = config('recaptcha.min_score');

        if ($minScore !== null && array_key_exists('score', $result)) {
            return $result['score'] >= (float) $minScore;
        }

        return true;
    }

    /**
     * Render the reCAPTCHA v3 script tags, mirroring the API previously
     * provided by biscolab/laravel-recaptcha's htmlScriptTagJsApi().
     *
     * @param  array{action?: string, callback_then?: string}  $config
     */
    public function htmlScriptTagJsApi(array $config = []): string
    {
        if (! $this->isEnabled()) {
            return '';
        }

        $siteKey = config('recaptcha.api_site_key');
        $action = $config['action'] ?? 'homepage';
        $callback = $config['callback_then'] ?? null;

        $encodedSiteKey = json_encode($siteKey);
        $encodedAction = json_encode(['action' => $action]);
        $then = $callback
            ? '.then(function (token) { '.$callback.'(token); })'
            : '';

        return <<<HTML
            <script src="https://www.google.com/recaptcha/api.js?render={$siteKey}"></script>
            <script>
                grecaptcha.ready(function () {
                    grecaptcha.execute({$encodedSiteKey}, {$encodedAction}){$then};
                });
            </script>
            HTML;
    }
}
