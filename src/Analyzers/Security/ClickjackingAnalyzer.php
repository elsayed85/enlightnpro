<?php

namespace Enlightn\EnlightnPro\Analyzers\Security;

use Enlightn\Enlightn\Analyzers\Concerns\AnalyzesHeaders;
use Enlightn\Enlightn\Analyzers\Security\SecurityAnalyzer;
use GuzzleHttp\Client;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Middleware\FrameGuard;
use Illuminate\Routing\Router;
use Enlightn\Enlightn\Analyzers\Concerns\AnalyzesMiddleware;

class ClickjackingAnalyzer extends SecurityAnalyzer
{
    use AnalyzesMiddleware, AnalyzesHeaders;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application sets appropriate HTTP headers to protect against clickjacking attacks.';

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
        $this->client = new Client();
    }

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return 'Your application is not adequately protected from clickjacking attacks. The X-Frame-Options HTTP header '
            .'is not set appropriately and this exposes your application to clickjacking security risks. Laravel '
            .'provides an out-of-the-box middleware called "FrameGuard" to protect against such attacks. A very '
            .'simple fix could be to simply include the middleware in your web middleware group. Alternatively, '
            .'you may also configure this on your web server (Nginx or Apache).';
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     * @throws \ReflectionException
     */
    public function handle()
    {
        if ($this->appUsesMiddleware(FrameGuard::class)
            || $this->headerExistsOnLoginRoute()) {
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
        // Skip this analyzer if the app is stateless (e.g. API only apps).
        return $this->appIsStateless();
    }

    /**
     * Determine if the X-Frame-Options header exists on the login route.
     *
     * @return bool
     */
    protected function headerExistsOnLoginRoute()
    {
        return $this->headerExistsOnUrl($this->findLoginRoute(), 'X-Frame-Options');
    }
}
