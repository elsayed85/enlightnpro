<?php

namespace Enlightn\EnlightnPro\Analyzers\Reliability;

use Enlightn\Enlightn\Analyzers\Reliability\ReliabilityAnalyzer;
use Enlightn\Enlightn\Filesystem;
use Illuminate\Support\Str;

class CacheBustingAnalyzer extends ReliabilityAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = "Your application uses cache busting.";

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
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application does not seem to use cache busting. Cache busting forces browsers to load "
            ."fresh assets instead of serving stale copies of the code. Without cache busting in place, "
            ."your customers might actually be served stale versions of your application.";
    }

    /**
     * Execute the analyzer.
     *
     * @param \Enlightn\Enlightn\Filesystem $files
     * @return void
     */
    public function handle(Filesystem $files)
    {
        $noBusting = $files->lines(public_path('mix-manifest.json'))->every(function ($line) {
            // If any file is versioned, we assume that versioning is used properly.
            return ! Str::contains($line, '?id=');
        });

        if ($noBusting) {
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
        // Skip the analyzer if the application doesn't use Laravel Mix.
        return ! file_exists(public_path('mix-manifest.json'));
    }
}
