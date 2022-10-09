<?php

namespace Enlightn\EnlightnPro\Analyzers\Reliability;

use Enlightn\Enlightn\Analyzers\Reliability\ReliabilityAnalyzer;

class StorageLinkAnalyzer extends ReliabilityAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = "The storage symbolic links are created.";

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
     * The links that are not accessible.
     *
     * @var string
     */
    protected $failedLinks;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application's symbolic links have not been created: ".$this->failedLinks;
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $this->failedLinks = collect($this->links())->reject(function ($target, $link) {
            return file_exists($link);
        })->keys()->join(', ', ' and ');

        if (! empty($this->failedLinks)) {
            $this->markFailed();
        }
    }

    /**
     * Get the symbolic links that are configured for the application.
     *
     * @return array
     */
    protected function links()
    {
        return config('filesystems.links', [public_path('storage') => storage_path('app/public')]);
    }
}
