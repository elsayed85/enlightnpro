<?php

namespace Enlightn\EnlightnPro\Analyzers\Reliability;

use Enlightn\Enlightn\Analyzers\Concerns\DetectsRedis;
use Enlightn\Enlightn\Analyzers\Reliability\ReliabilityAnalyzer;
use Illuminate\Support\Facades\Redis;
use Throwable;

class RedisStatusAnalyzer extends ReliabilityAnalyzer
{
    use DetectsRedis;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = "Redis is accessible.";

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
    public $timeToFix = 5;

    /**
     * Determine whether the analyzer should be run in CI mode.
     *
     * @var bool
     */
    public static $runInCI = false;

    /**
     * The connections that are not accessible.
     *
     * @var string
     */
    protected $failedConnections;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application's Redis connection(s) is/are not accessible: ".$this->failedConnections;
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $redisConnectionsToCheck = config(
            'enlightn.redis_connections',
            collect(config('database.redis', []))->except(['client', 'options'])->keys()->toArray()
        );

        $this->failedConnections = collect($redisConnectionsToCheck)->filter()->reject(function ($name) {
           try {
               return Redis::connection($name)->command('ping', ['hello']) === 'hello';
           } catch (Throwable $e) {
               return false;
           }
        })->join(', ', ' and ');

        if (! empty($this->failedConnections)) {
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
       return ! $this->appUsesRedis();
    }
}
