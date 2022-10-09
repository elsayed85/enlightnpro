<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Concerns\AnalyzesMiddleware;
use Enlightn\Enlightn\Analyzers\Concerns\DetectsRedis;
use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Routing\Router;

class RedisThrottlingAnalyzer extends PerformanceAnalyzer
{
    use AnalyzesMiddleware, DetectsRedis;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application uses an efficient throttling mechanism.';

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
     * Create a new analyzer instance.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @param  \Illuminate\Contracts\Http\Kernel  $kernel
     * @return void
     */
    public function __construct(Router $router, Kernel $kernel)
    {
        $this->router = $router;
        $this->kernel = $kernel;
    }

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return 'Your application uses the "ThrottleRequests" middleware shipped with Laravel but does not use '
            .'the "ThrottleRequestsWithRedis" middleware, even though your application is using Redis. The '
            .'Redis specific middleware is more efficient in managing rate limiting using Redis and you '
            .'should consider using the Redis specific middleware instead.';
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     * @throws \ReflectionException
     */
    public function handle()
    {
        if ($this->appUsesMiddleware(ThrottleRequestsWithRedis::class)) {
            return;
        }

        $this->markFailed();
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function skip()
    {
        // Skip this analyzer if throttling or Redis isn't used in the application.
        return ! $this->appUsesMiddleware(ThrottleRequests::class) || ! $this->appUsesRedis();
    }
}
