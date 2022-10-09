<?php

namespace Enlightn\EnlightnPro\Analyzers\Reliability;

use Enlightn\Enlightn\Analyzers\Concerns\DetectsRedis;
use Enlightn\Enlightn\Analyzers\Reliability\ReliabilityAnalyzer;
use Illuminate\Contracts\Redis\Factory as RedisFactory;

class RedisEvictionPolicyAnalyzer extends ReliabilityAnalyzer
{
    use DetectsRedis;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Redis eviction policy is configured properly.';

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
    public $timeToFix = 10;

    /**
     * Determine whether the analyzer should be run in CI mode.
     *
     * @var bool
     */
    public static $runInCI = false;

    /**
     * Execute the analyzer.
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @return void
     */
    public function handle(RedisFactory $redis)
    {
        if (($policy = $redis->connection()->command('config', ['get', 'maxmemory-policy']))
            && isset($policy['maxmemory-policy'])
            && $policy['maxmemory-policy'] === 'noeviction'
            && config('session.driver') !== 'redis'
            && config('queue.connections.'.config('queue.default').'.driver') !== 'redis') {
            // For cache only servers, a noeviction policy is not recommended.
            $this->errorMessage = "By default, Redis has a 'noeviction' policy, which means that it errors out when the "
                ."memory limit is reached. Your Redis servers use the default, however your app seems to "
                ."use Redis as a cache-only server and for cache-only servers, we recommend changing the eviction "
                ."policy to 'allkeys-lfu' (on Redis 4+) or 'allkeys-lru' (on Redis 3+).";
            $this->markFailed();
        } elseif (isset($policy['maxmemory-policy']) && $policy['maxmemory-policy'] !== 'noeviction'
            && (config('session.driver') === 'redis'
                || config('queue.connections.'.config('queue.default').'.driver') === 'redis')) {
            $this->errorMessage = "Your application uses Redis for queues or sessions, which are meant to be "
                ."persistent. However, your Redis eviction policy is not set to 'noeviction'. This means "
                ."that as Redis reaches its memory limit, it will automatically start evicting keys instead "
                ."of raising an error. This is not recommended as this would result in lost jobs or sessions.";
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
        // Skip the analyzer for local environments or if the application does not use Redis.
        return $this->isLocalAndShouldSkip() || ! $this->appUsesRedis();
    }
}
