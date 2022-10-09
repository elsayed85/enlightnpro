<?php

namespace Enlightn\EnlightnPro\Analyzers\Security;

use Enlightn\Enlightn\Analyzers\Concerns\AnalyzesHeaders;
use Enlightn\Enlightn\Analyzers\Security\SecurityAnalyzer;
use Enlightn\Enlightn\Inspection\Reflector;
use GuzzleHttp\Client;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Routing\Router;
use Enlightn\Enlightn\Analyzers\Concerns\AnalyzesMiddleware;
use Fideloper\Proxy\TrustProxies;
use Illuminate\Support\Str;

class HostInjectionAnalyzer extends SecurityAnalyzer
{
    use AnalyzesMiddleware, AnalyzesHeaders;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application is protected against host injection attacks.';

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
     * The name of the injected host.
     *
     * @var string
     */
    protected $injectedHost;

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
        $this->injectedHost = 'evil.com';
    }

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return 'Your application is vulnerable to a host injection attack. Laravel provides an '
            .'out-of-the-box middleware called "TrustHosts" to protect against such attacks. A very '
            .'simple fix could be to simply include the middleware in your web middleware group.';
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $this->performSoftCheck();

        if (! in_array(config('app.env'), ['local', 'testing'])) {
            // Don't run hard checks in local, test or CI environments.
            $this->performHardCheck();
        }
    }

    /**
     * @return string
     */
    public function getInjectedHost()
    {
        return $this->injectedHost;
    }

    /**
     * Perform a hard check by sending a request and inspecting response headers/body.
     *
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function performHardCheck()
    {
        if ($this->isVulnerableForHeader('X-Forwarded-Host') ||
            $this->isVulnerableForHeader('Host')) {
            $this->markFailed();
        }
    }

    /**
     * Check if the app is vulnerable to host injection using the given header.
     *
     * @param string $header
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function isVulnerableForHeader($header)
    {
        $options = [
            'allow_redirects' => false,
            'headers' => [
                $header => $this->injectedHost,
            ],
            'http_errors' => false,
        ];

        $response = $this->client->get($this->findLoginRoute(), $options);

        if (collect($response->getHeaders())->contains(function ($values, $headerName) {
            return Str::contains(implode(',', $values), $this->injectedHost);
        })) {
            // Injected host found its way to the response headers (e.g. Location header), so the app is indeed vulnerable.
            return true;
        }

        if (Str::contains((string) $response->getBody(), $this->injectedHost)) {
            // Injected host found its way to the response body (e.g. URLs), so the app is indeed vulnerable.
            return true;
        }

        return false;
    }

    /**
     * Perform a soft check using the configured middleware.
     *
     * @return void
     */
    protected function performSoftCheck()
    {
        if ($this->appUsesMiddleware(TrustProxies::class)
            && $this->trustedProxiesAreSetup()
            && ! $this->appUsesMiddleware(TrustHosts::class)) {
            $this->markFailed();
        }
    }

    /**
     * Determine whether trusted proxies are setup.
     *
     * @return bool
     * @throws \Illuminate\Contracts\Container\BindingResolutionException|\ReflectionException
     */
    protected function trustedProxiesAreSetup()
    {
        if (! empty(config('trustedproxy.proxies'))) {
            return true;
        }

        $middleware = app()->make(TrustProxies::class);
        $proxies = Reflector::get($middleware, 'proxies');

        return ! (empty($proxies) && is_null(config('trustedproxy.proxies')));
    }
}
