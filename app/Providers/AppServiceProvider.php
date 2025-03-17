<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the custom Livewire component to override Jetstream defaults
        \Livewire\Livewire::component('profile.update-profile-information-form', \App\Http\Livewire\Profile\UpdateProfileInformationForm::class);
    }
}
