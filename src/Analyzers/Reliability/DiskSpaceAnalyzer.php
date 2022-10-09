<?php

namespace Enlightn\EnlightnPro\Analyzers\Reliability;

use Enlightn\Enlightn\Analyzers\Reliability\ReliabilityAnalyzer;

class DiskSpaceAnalyzer extends ReliabilityAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = "Your application has sufficient disk space.";

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
     * The current disk usage (in percentage).
     *
     * @var float
     */
    protected $diskUsage;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application is running out of disk space. It is currently at {$this->diskUsage}%.";
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $this->diskUsage = round(100 - (float) disk_free_space(app_path()) * 100 / disk_total_space(app_path()), 2);

        if ($this->diskUsage > config('enlightn.disk_usage_threshold', 90)) {
            $this->markFailed();
        }
    }
}
