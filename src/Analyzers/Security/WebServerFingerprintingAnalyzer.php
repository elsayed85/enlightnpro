<?php

namespace Enlightn\EnlightnPro\Analyzers\Security;

use Enlightn\Enlightn\Analyzers\Concerns\AnalyzesHeaders;
use Enlightn\Enlightn\Analyzers\Concerns\AnalyzesMiddleware;
use Enlightn\Enlightn\Analyzers\Security\SecurityAnalyzer;
use GuzzleHttp\Client;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;

class WebServerFingerprintingAnalyzer extends SecurityAnalyzer
{
    use AnalyzesHeaders, AnalyzesMiddleware;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your web server does not expose details about the vendor or version of the server.';

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
     * The Server headers returned by the application.
     *
     * @var array
     */
    protected $serverHeaders = [];

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
        return "Your web server exposes information about its vendor and/or version. This allows potential attackers "
            ."to conduct fingerprinting of your web server and exploit unpatched versions if any. The information "
            ."revealed is: {$this->formatHeaders()}.";
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $this->serverHeaders = $this->getHeadersOnUrl($this->findLoginRoute(), 'Server');

        if (Str::contains(strtolower(implode(';', $this->serverHeaders)), ['nginx/', 'apache/'])) {
            $this->markFailed();
        }
    }

    /**
     * @return string
     */
    protected function formatHeaders()
    {
        return collect($this->serverHeaders)->map(function ($header) {
            return "[{$header}]";
        })->join(', ', ' and ');
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        return $this->isLocalAndShouldSkip();
    }
}
