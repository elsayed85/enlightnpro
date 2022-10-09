<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class EventCachingAnalyzer extends PerformanceAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Application event caching is configured properly.';

    /**
     * The severity of the analyzer.
     *
     * @var string|null
     */
    public $severity = self::SEVERITY_MINOR;

    /**
     * The time to fix in minutes.
     *
     * @var int|null
     */
    public $timeToFix = 5;

    /**
     * Determine whether the analyzer should be run in CI mode.
     *
     * @var bool
     */
    public static $runInCI = false;

    /**
     * @var int
     */
    protected $eventCount = 0;

    /**
     * Execute the analyzer.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function handle(Application $app, ConfigRepository $config)
    {
        if ($config->get('app.env') === 'local' && $app->eventsAreCached()) {
            $this->errorMessage = "Your app events are cached in a local environment. "
                ."This is not recommended for development because as you change your event files, "
                ."the changes will not be reflected unless you clear the cache.";

            $this->markFailed();
        } elseif ($config->get('app.env') !== 'local' && ! $app->eventsAreCached()) {
            $this->errorMessage = "Your app events are not cached in a non-local environment. Your "
                ."application scans and registers a total of {$this->eventCount} events on every request. "
                ."Event caching enables a performance improvement by caching a manifest of all your "
                ."application's events and listeners. It is recommended to enable this in production "
                ."for a performance boost. Remember to add the Artisan event:cache command to your "
                ."deployment script so that every time you deploy, the manifest cache is regenerated.";

            $this->markFailed();
        }
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        $events = [];

        foreach (app()->getProviders(EventServiceProvider::class) as $provider) {
            $providerEvents = array_merge_recursive($provider->shouldDiscoverEvents() ? $provider->discoverEvents() : [], $provider->listens());

            $events[get_class($provider)] = $providerEvents;
        }

        $this->eventCount = collect($events)->sum(function ($providerEvents) {
            return count($providerEvents);
        });

        return $this->eventCount == 0;
    }
}
