<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;

class XdebugAnalyzer extends PerformanceAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application does not have Xdebug loaded on production.';

    /**
     * The severity of the analyzer.
     *
     * @var string|null
     */
    public $severity = self::SEVERITY_CRITICAL;

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
        return "Your application has Xdebug loaded in a non-local environment. This has a significant performance "
            ."impact and can also lead to security issues. It is strongly recommended to disable Xdebug in non-local "
            ."environments (staging or production).";
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        if (extension_loaded('xdebug')) {
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
        // Skip the analyzer if it's a local or test env.
        return in_array(config('app.env'), ['local', 'testing', 'test']);
    }
}
