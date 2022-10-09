<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Concerns\DetectsRedis;
use Enlightn\Enlightn\Analyzers\Concerns\InspectsCode;
use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use Enlightn\Enlightn\Inspection\Inspector;
use Enlightn\Enlightn\Inspection\QueryBuilder;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\RateLimitedWithRedis;

class RedisRateLimitingAnalyzer extends PerformanceAnalyzer
{
    use InspectsCode, DetectsRedis;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application uses an efficient rate limiting mechanism for queued jobs.';

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
     * The inspector instance.
     *
     * @var \Enlightn\Enlightn\Inspection\Inspector
     */
    protected $inspector;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return 'Your application uses the "RateLimited" job middleware shipped with Laravel but does not use '
            .'the "RateLimitedWithRedis" middleware, even though your application is using Redis. The '
            .'Redis specific job middleware is more efficient in managing rate limiting using Redis and you '
            .'should consider using the Redis specific middleware instead.';
    }

    /**
     * Create a new analyzer instance.
     *
     * @param \Enlightn\Enlightn\Inspection\Inspector $inspector
     */
    public function __construct(Inspector $inspector)
    {
        $this->inspector = $inspector;
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $this->inspectCode(
            $this->inspector,
            (new QueryBuilder())->usesClass(RateLimitedWithRedis::class)
        );
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        // Skip this analyzer if Redis or rate limiting isn't used in the application.
        return ! $this->appUsesRedis() || ! class_exists(RateLimited::class)
            || $this->passesCodeInspection(
                $this->inspector,
                (new QueryBuilder())->doesntUseClass(RateLimited::class)
            );
    }
}
