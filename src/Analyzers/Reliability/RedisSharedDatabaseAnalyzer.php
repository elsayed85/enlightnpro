<?php

namespace Enlightn\EnlightnPro\Analyzers\Reliability;

use Enlightn\Enlightn\Analyzers\Reliability\ReliabilityAnalyzer;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class RedisSharedDatabaseAnalyzer extends ReliabilityAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Redis cache database is not shared to allow for safe flushing.';

    /**
     * The severity of the analyzer.
     *
     * @var string|null
     */
    public $severity = self::SEVERITY_MAJOR;

    /**
     * The time to fix in minutes.
     *
     * @var int|null
     */
    public $timeToFix = 2;

    /**
     * The services that share the same Redis database.
     *
     * @var array
     */
    public $sharedServices = [];

    /**
     * The Redis cache database used.
     *
     * @var string
     */
    public $cacheDatabase;

    /**
     * The Redis cache host used.
     *
     * @var string
     */
    public $cacheHost;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your Redis cache connection or database is shared with your {$this->getSharedServices()} "
            ."services. This means that clearing the cache with the Artisan cache:clear command will "
            ."also clear your {$this->getSharedServices()}. It is recommended to have separate "
            ."connections / databases for your non-cache services, which typically are not meant to be "
            ."cleared in production.";
    }

    /**
     * Execute the analyzer.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function handle(ConfigRepository $config)
    {
        $this->cacheDatabase = $this->getDatabaseFromConnection(
            $cacheConnection = $config->get('cache.stores.'.$config->get('cache.default').'.connection')
        );

        $this->cacheHost = $this->getHostFromConnection($cacheConnection);

        if ($config->get('queue.connections.'.$config->get('queue.default').'.driver') === 'redis') {
            $this->checkDatabaseCollision(
                $config->get('queue.connections.'.$config->get('queue.default').'.connection'),
                'queue'
            );
        }

        if ($config->get('session.driver') === 'redis') {
            $this->checkDatabaseCollision($config->get('session.connection', 'default') ?? 'default', 'session');
        }

        if ($config->get('broadcasting.default') === 'redis') {
            $this->checkDatabaseCollision(
                $config->get('broadcasting.connections.redis.connection', 'default'),
                'broadcasting'
            );
        }

        if ($config->get('horizon.use')) {
            $this->checkDatabaseCollision($config->get('horizon.use'), 'Horizon');
        }
    }

    /**
     * Get the names of the services that share the Redis database with the cache.
     *
     * @return string
     */
    public function getSharedServices()
    {
        return collect($this->sharedServices)->join(', ', ' and ');
    }

    /**
     * Check collision with the Redis cache database and register on collision.
     *
     * @param  string  $connectionName
     * @param  string  $service
     * @return void
     */
    public function checkDatabaseCollision(string $connectionName, string $service)
    {
        if ($this->getDatabaseFromConnection($connectionName) == $this->cacheDatabase
            && $this->getHostFromConnection($connectionName) == $this->cacheHost) {
            $this->registerSharedService($service);
            $this->markFailed();
        }
    }

    /**
     * Register a shared service.
     *
     * @param  string  $service
     * @return void
     */
    public function registerSharedService(string $service)
    {
        $this->sharedServices[] = $service;
    }


    /**
     * Get the Redis database from the connection name.
     *
     * @param  string  $connection
     * @return string
     */
    public function getDatabaseFromConnection(string $connection)
    {
        return config('database.redis.'.$connection.'.database');
    }

    /**
     * Get the Redis host from the connection name.
     *
     * @param  string  $connection
     * @return string
     */
    public function getHostFromConnection(string $connection)
    {
        return config('database.redis.'.$connection.'.host');
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        // Skip this analyzer if the default cache store is not Redis.
        return config('cache.stores.'.config('cache.default').'.driver') !== 'redis';
    }
}
