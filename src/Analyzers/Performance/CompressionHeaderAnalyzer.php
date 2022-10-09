<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Concerns\AnalyzesHeaders;
use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use GuzzleHttp\Client;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class CompressionHeaderAnalyzer extends PerformanceAnalyzer
{
    use AnalyzesHeaders;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = "Appropriate compression headers are set for your application's assets.";

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
     * The list of uncached assets.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $unCompressedAssets;

    /**
     * Create a new analyzer instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application does not set appropriate compression headers on your compiled Laravel Mix assets. "
            ."To improve performance, it is recommended to enable compression for your JS and CSS files via "
            ."your web server configuration. Your uncompressed assets include: {$this->formatUncachedAssets()}.";
    }

    /**
     * Execute the analyzer.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     * @return void
     */
    public function handle(Filesystem $files)
    {
        $manifest = json_decode($files->get(public_path('mix-manifest.json')), true);

        $this->unCompressedAssets = collect();

        foreach ($manifest as $key => $value) {
            if (is_string($value) && Str::is(['*.js', '*.css'], $key)
                && ! $this->headerExistsOnUrl(asset($key), [
                    'Vary', 'Content-Encoding', 'x-encoded-content-encoding',
                ], [
                    'headers' => ['Accept-Encoding' => 'gzip, deflate, br']
                ])) {
                // We only check the js and css files for compression as most images/media formats are already compressed.
                $this->unCompressedAssets->push($key);
            }
        }

        if ($this->unCompressedAssets->count() > 0) {
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
        // Skip the analyzer if it's a local env or if the application does not use Laravel Mix.
        return $this->isLocalAndShouldSkip() || ! file_exists(public_path('mix-manifest.json'));
    }

    /**
     * @return string
     */
    protected function formatUncachedAssets()
    {
        return $this->unCompressedAssets->map(function ($file) {
            return "[{$file}]";
        })->join(', ', ' and ');
    }
}
