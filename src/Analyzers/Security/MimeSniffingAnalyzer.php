<?php

namespace Enlightn\EnlightnPro\Analyzers\Security;

use Enlightn\Enlightn\Analyzers\Concerns\AnalyzesHeaders;
use Enlightn\Enlightn\Analyzers\Security\SecurityAnalyzer;
use GuzzleHttp\Client;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Enlightn\Enlightn\Analyzers\Concerns\AnalyzesMiddleware;

class MimeSniffingAnalyzer extends SecurityAnalyzer
{
    use AnalyzesMiddleware, AnalyzesHeaders;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application includes the X-Content-Type-Options header to protect against MIME sniffing attacks.';

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
        return "Your application does not include an X-Content-Type-Options response header that tells browsers to "
            ."not ignore explicitly defined content types. This may expose your application to MIME sniffing attacks.";
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->headerExistsOnUrl($this->findLoginRoute(), 'X-Content-Type-Options')) {
            $this->markFailed();
        }
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function skip()
    {
        // Skip this analyzer if the app is stateless or does not use cookies.
        return $this->appIsStateless() || ! $this->appUsesCookies();
    }
}
