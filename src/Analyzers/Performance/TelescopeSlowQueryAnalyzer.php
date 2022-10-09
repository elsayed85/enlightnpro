<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use Enlightn\EnlightnPro\Analyzers\Concerns\AnalyzesTelescopeEntries;
use Enlightn\EnlightnPro\TelescopeEntry;

class TelescopeSlowQueryAnalyzer extends PerformanceAnalyzer
{
    use AnalyzesTelescopeEntries;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application does not have slow queries.';

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
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application has some slow queries. There are a number of ways to improve query performance "
            ."including index optimization, limiting data, table normalization or de-normalization and query caching.";
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $this->executeQuery($this->findSlowQueries())->each(function ($entry) {
            $this->addTrace(trim($entry['file'], '"\''), (int) $entry['line']);
        });
    }

    /**
     * Get the slow queries.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function findSlowQueries()
    {
        return TelescopeEntry::where('type', 'query')
                ->whereParam('slow', true)
                ->selectTotalEntries()
                ->addParams(['file', 'line'])
                ->groupByColNumbers([2, 3])
                ->orderByTotalEntries();
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        // Skip this analyzer if the app doesn't use Telescope.
        return is_null(config('telescope.driver'));
    }
}
