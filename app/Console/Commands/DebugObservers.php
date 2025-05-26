<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use ReflectionClass;

class DebugObservers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:observers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug observers registered in the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking registered observers...');
        
        $eventDispatcher = Event::getFacadeRoot();
        $reflectionClass = new ReflectionClass($eventDispatcher);
        
        // Attempt to get the listeners property
        $listenersProperty = $reflectionClass->getProperty('listeners');
        $listenersProperty->setAccessible(true);
        $listeners = $listenersProperty->getValue($eventDispatcher);
        
        // Observers are registered as listeners for Eloquent events
        $this->info('Eloquent model observers events:');
        $this->newLine();
        
        $observerEvents = array_filter(array_keys($listeners), function ($event) {
            return strpos($event, 'eloquent.') === 0;
        });
        
        foreach ($observerEvents as $event) {
            $this->info("Event: {$event}");
            
            foreach ($listeners[$event] as $listener) {
                if (is_array($listener) && count($listener) === 2) {
                    if (is_object($listener[0])) {
                        $listenerClass = get_class($listener[0]);
                        $this->info("  - Observer: {$listenerClass}::{$listener[1]}");
                    } else {
                        $this->info("  - Listener: {$listener[0]}::{$listener[1]}");
                    }
                } else if (is_string($listener)) {
                    $this->info("  - Listener: {$listener}");
                } else {
                    $this->info("  - Listener: " . gettype($listener));
                }
            }
            
            $this->newLine();
        }
        
        // Specifically check for App\Models\Project observers
        $this->info('Looking for Project model observers:');
        $projectEvents = array_filter(array_keys($listeners), function ($event) {
            return strpos($event, 'eloquent.') === 0 && 
                   strpos($event, 'App\\Models\\Project') !== false;
        });
        
        if (empty($projectEvents)) {
            $this->warn('No Project model observer events found!');
        } else {
            foreach ($projectEvents as $event) {
                $this->info("Event: {$event}");
                foreach ($listeners[$event] as $listener) {
                    if (is_array($listener) && count($listener) === 2) {
                        if (is_object($listener[0])) {
                            $listenerClass = get_class($listener[0]);
                            $this->info("  - Observer: {$listenerClass}::{$listener[1]}");
                        } else {
                            $this->info("  - Listener: {$listener[0]}::{$listener[1]}");
                        }
                    }
                }
            }
        }
        
        return self::SUCCESS;
    }
}
