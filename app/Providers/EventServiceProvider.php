<?php

namespace App\Providers;

use App\Models\Pitch;
use App\Observers\PitchObserver;
use App\Models\Project;
use App\Observers\ProjectObserver;
use App\Models\ContestResult;
use App\Observers\ContestResultObserver;
use App\Events\NotificationCreated;
use App\Listeners\NotificationCreatedListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        NotificationCreated::class => [
            NotificationCreatedListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Register the PitchObserver with the Pitch model
        Pitch::observe(PitchObserver::class);
        // Register the ProjectObserver with the Project model
        Project::observe(ProjectObserver::class);
        // Register the ContestResultObserver with the ContestResult model
        ContestResult::observe(ContestResultObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
