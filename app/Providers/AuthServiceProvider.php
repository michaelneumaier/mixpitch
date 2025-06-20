<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\ServicePackage;
use App\Policies\ServicePackagePolicy;
use App\Models\Order;
use App\Policies\OrderPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Pitch::class => \App\Policies\PitchPolicy::class,
        \App\Models\Project::class => \App\Policies\ProjectPolicy::class,
        \App\Models\PitchFile::class => \App\Policies\PitchFilePolicy::class,
        \App\Models\ProjectFile::class => \App\Policies\ProjectFilePolicy::class,
        \App\Models\ServicePackage::class => \App\Policies\ServicePackagePolicy::class,
        Order::class => OrderPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
