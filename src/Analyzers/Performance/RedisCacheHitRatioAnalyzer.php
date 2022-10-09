<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use Illuminate\Support\Facades\Redis;

class RedisCacheHitRatioAnalyzer extends PerformanceAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application has a healthy Redis cache hit ratio.';

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
     * The application's cache hit ratio.
     *
     * @var float
     */
    protected $cacheHitRatio;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application does not have a healthy cache hit ratio. Typically, this should be at the "
            ."80%+ mark, but your application's cache hit ratio is: {$this->cacheHitRatio}%. There could be "
            ."a number of reasons for this. Some common ones include: keys getting expired too soon or key "
            ."eviction due to low memory availability.";
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $stats = Redis::connection(config('cache.stores.'.config('cache.default').'.connection'))
                ->command('info', ['stats']);

        $hits = (float) ($stats['keyspace_hits'] ?? $stats['Stats']['keyspace_hits'] ?? 0);
        $misses = (float) ($stats['keyspace_misses'] ?? $stats['Stats']['keyspace_misses'] ?? 0);

        if (($hits + $misses) == 0) {
            // No hits or misses, so it's best to just skip this analyzer.
            $this->markSkipped();
            return;
        }

        $this->cacheHitRatio = round($hits * 100 / ($hits + $misses));

        if ($this->cacheHitRatio < 80) {
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
        // Skip this analyzer if the application does not use a Redis cache driver or if the app is in a local env.
        return config('cache.stores.'.config('cache.default').'.driver') !== 'redis'
            || $this->isLocalAndShouldSkip();
    }
}
