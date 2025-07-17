<?php

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class TipjarServiceProvider extends ServiceProvider
{
    /**
     * List of allowed tipjar domains
     *
     * @var array
     */
    public static $allowedDomains = [
        'paypal.me',
        'ko-fi.com',
        'buymeacoffee.com',
        'patreon.com',
        'venmo.com',
        'cashapp.com',
        'stripe.com',
        'gofundme.com',
        'tipjar.com',
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the tipjar domain validator
        Validator::extend('allowed_tipjar_domain', function ($attribute, $value, $parameters, $validator) {
            if (empty($value)) {
                return true; // Allow empty values (nullable)
            }

            // Parse the URL to get the domain
            $domain = parse_url($value, PHP_URL_HOST);

            // If parse_url doesn't return a host (e.g., for 'paypal.me/username')
            if (empty($domain)) {
                $parts = explode('/', $value);
                $domain = $parts[0];
            }

            // Remove www. prefix if present
            $domain = preg_replace('/^www\./', '', $domain);

            // Check if domain is in allowed list
            return in_array($domain, self::$allowedDomains);
        }, 'The :attribute must be from an approved tipjar service.');
    }
}
