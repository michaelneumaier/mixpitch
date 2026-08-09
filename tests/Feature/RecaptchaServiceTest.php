<?php

namespace Tests\Feature;

use App\Services\RecaptchaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function enableRecaptcha(): void
    {
        config([
            'recaptcha.enabled' => true,
            'recaptcha.api_site_key' => 'test-site-key',
            'recaptcha.api_secret_key' => 'test-secret-key',
        ]);
    }

    public function test_validate_returns_true_for_successful_verification(): void
    {
        $this->enableRecaptcha();
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true, 'score' => 0.9]),
        ]);

        $this->assertTrue(app('recaptcha')->validate('valid-token', '127.0.0.1'));

        Http::assertSent(function ($request) {
            return $request['secret'] === 'test-secret-key'
                && $request['response'] === 'valid-token'
                && $request['remoteip'] === '127.0.0.1';
        });
    }

    public function test_validate_returns_false_when_google_rejects_token(): void
    {
        $this->enableRecaptcha();
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false]),
        ]);

        $this->assertFalse(app('recaptcha')->validate('bad-token'));
    }

    public function test_validate_returns_false_when_score_is_below_minimum(): void
    {
        $this->enableRecaptcha();
        config(['recaptcha.min_score' => 0.5]);
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true, 'score' => 0.2]),
        ]);

        $this->assertFalse(app('recaptcha')->validate('low-score-token'));
    }

    public function test_validate_returns_false_for_empty_token_without_calling_google(): void
    {
        $this->enableRecaptcha();
        Http::fake();

        $this->assertFalse(app('recaptcha')->validate(null));
        $this->assertFalse(app('recaptcha')->validate(''));

        Http::assertNothingSent();
    }

    public function test_validate_returns_false_when_request_fails(): void
    {
        $this->enableRecaptcha();
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(null, 500),
        ]);

        $this->assertFalse(app('recaptcha')->validate('token'));
    }

    public function test_script_tag_contains_site_key_and_action(): void
    {
        $this->enableRecaptcha();

        $html = app('recaptcha')->htmlScriptTagJsApi([
            'action' => 'register',
            'callback_then' => 'recaptchaCallback',
        ]);

        $this->assertStringContainsString('recaptcha/api.js?render=test-site-key', $html);
        $this->assertStringContainsString('"action":"register"', $html);
        $this->assertStringContainsString('recaptchaCallback(token)', $html);
    }

    public function test_script_tag_is_empty_when_disabled(): void
    {
        config(['recaptcha.enabled' => false]);

        $this->assertSame('', app('recaptcha')->htmlScriptTagJsApi(['action' => 'register']));
    }

    public function test_registration_requires_recaptcha_token_when_enabled(): void
    {
        $this->enableRecaptcha();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'captcha-required@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('recaptcha');
        $this->assertGuest();
    }

    public function test_registration_fails_with_invalid_recaptcha_token(): void
    {
        $this->enableRecaptcha();
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false]),
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'captcha-invalid@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'g-recaptcha-response' => 'bad-token',
        ]);

        $response->assertSessionHasErrors('recaptcha');
        $this->assertGuest();
    }

    public function test_registration_succeeds_with_valid_recaptcha_token(): void
    {
        $this->enableRecaptcha();
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true, 'score' => 0.9]),
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'captcha-valid@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'g-recaptcha-response' => 'good-token',
        ]);

        $this->assertAuthenticated();
    }

    public function test_registration_skips_recaptcha_when_disabled(): void
    {
        config(['recaptcha.enabled' => false]);
        Http::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'captcha-disabled@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        Http::assertNothingSent();
    }

    public function test_service_is_bound_as_singleton(): void
    {
        $this->assertInstanceOf(RecaptchaService::class, app('recaptcha'));
        $this->assertSame(app('recaptcha'), app('recaptcha'));
    }
}
