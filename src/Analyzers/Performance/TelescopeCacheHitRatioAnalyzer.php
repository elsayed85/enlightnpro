<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use Enlightn\EnlightnPro\Analyzers\Concerns\AnalyzesTelescopeEntries;
use Enlightn\EnlightnPro\TelescopeEntry;

class TelescopeCacheHitRatioAnalyzer extends PerformanceAnalyzer
{
    use AnalyzesTelescopeEntries;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application has a healthy cache hit ratio.';

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
    public $timeToFix = 30;

    /**
     * Determine whether the analyzer should be run in CI mode.
     *
     * @var bool
     */
    public static $runInCI = false;

    /**
     * The application's cache hit ratio.
     *
     * @var float
     */
    protected $cacheHitRatio;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application does not have a healthy cache hit ratio. Typically, this should be at the "
            ."80%+ mark, but your application's cache hit ratio is: {$this->cacheHitRatio}%. There could be "
            ."a number of reasons for this. Some common ones include: keys getting expired too soon or key "
            ."eviction due to low memory availability.";
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $hits = $this->executeQuery($this->calculateCacheHits())->first()->totalEntries ?? 0;
        $misses = $this->executeQuery($this->calculateCacheMisses())->first()->totalEntries ?? 0;

        if (($hits + $misses) == 0) {
            // No hits or misses, so it's best to just skip this analyzer.
            $this->markSkipped();
            return;
        }

        $this->cacheHitRatio = round($hits * 100 / ($hits + $misses));

        if ($this->cacheHitRatio < 80) {
            $this->markFailed();
        }
    }

    /**
     * Calculate the cache hits.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function calculateCacheHits()
    {
        return TelescopeEntry::where('type', 'cache')
                ->selectTotalEntries()
                ->whereParam('type', 'hit');
    }

    /**
     * Calculate the cache misses.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function calculateCacheMisses()
    {
        return TelescopeEntry::where('type', 'cache')
            ->selectTotalEntries()
            ->whereParam('type', 'missed');
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        // Skip this analyzer if the app doesn't use Telescope or if the app is in a local environment.
        return is_null(config('telescope.driver')) || $this->isLocalAndShouldSkip();
    }
}
