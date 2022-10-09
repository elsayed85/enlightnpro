<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Concerns\ParsesConfigurationFiles;
use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Symfony\Component\Finder\Finder;

class CdnAnalyzer extends PerformanceAnalyzer
{
    use ParsesConfigurationFiles;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application uses a CDN to serve assets.';

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
    public $timeToFix = 30;

    /**
     * Determine whether the analyzer should be run in CI mode.
     *
     * @var bool
     */
    public static $runInCI = false;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application is currently not using a CDN. If you wish to further enhance your application "
            ."performance and response time, you should consider using a CDN to serve your assets.";
    }

    /**
     * Execute the analyzer.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     * @return void
     */
    public function handle(ConfigRepository $config)
    {
        if (! $config->get('app.asset_url') && ! $config->get('app.mix_url')) {
            $this->recordError('app', 'asset_url');
        }
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        // Skip the analyzer if it's a local env or if there is no CDN and no asset to serve (e.g. API only apps).
        return $this->isLocalAndShouldSkip() || (! config('app.asset_url') && ! config('app.mix_url')
            && ((new Finder)->in(public_path())->exclude('vendor')->name([
            // This is not an exhaustive list, just a list of common extensions for asset files.
            '*.jpg', '*.jpeg', '*.png', '*.bmp', '*.pdf', '*.gif', '*.webp',
            '*.tiff', '*.tif', '*.svg', '*.eps', '*.otf', '*.ttf', '*.js',
            '*.css',
            ])->files()->count() == 0));
    }
}
