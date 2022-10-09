<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Concerns\AnalyzesMiddleware;
use Enlightn\Enlightn\Analyzers\Concerns\DetectsHttps;
use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use GuzzleHttp\Client;
use Illuminate\Routing\Router;
use Throwable;

class HttpTwoAnalyzer extends PerformanceAnalyzer
{
    use DetectsHttps, AnalyzesMiddleware;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application supports the HTTP/2 protocol.';

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
     * The Guzzle client instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * The HTTP protocol version supported by the application.
     *
     * @var string
     */
    protected $protocolVersion = 'Unknown';

    /**
     * Create a new analyzer instance.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
        $this->client = new Client();
    }

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application currently does not support the HTTP/2 protocol. It currently supports HTTP "
            ."version [$this->protocolVersion].";
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $response = $this->client->get($this->findLoginRoute(), [
                'curl' => [
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
                ],
            ]);

            $this->protocolVersion = $response->getProtocolVersion();

            if ($this->protocolVersion !== '2') {
                $this->markFailed();
            }
        } catch (Throwable $e) {
            return;
        }
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        // Skip the analyzer if the app is not HTTPS because HTTP/2 is only supported by popular browsers over HTTPS.
        // Also, skip if the curl extension is not installed or if the curl version does not support HTTP/2.
        return ! $this->appIsHttpsOnly() ||
            ! (extension_loaded('curl') && curl_version()["features"] && CURL_VERSION_HTTP2 !== 0);
    }

    /**
     * Set the Guzzle client.
     *
     * @param \GuzzleHttp\Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }
}
