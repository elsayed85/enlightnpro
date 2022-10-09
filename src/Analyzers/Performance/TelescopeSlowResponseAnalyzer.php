<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use Enlightn\EnlightnPro\Analyzers\Concerns\AnalyzesRoutes;
use Enlightn\EnlightnPro\Analyzers\Concerns\AnalyzesTelescopeEntries;
use Enlightn\EnlightnPro\TelescopeEntry;
use Throwable;

class TelescopeSlowResponseAnalyzer extends PerformanceAnalyzer
{
    use AnalyzesTelescopeEntries, AnalyzesRoutes;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application does not have slow responses.';

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
     * The slow response URIs.
     *
     * @var string
     */
    public $slowResponseUris;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application has some routes with slow responses. There are a number of ways to improve response "
            ."time including delegating to background jobs and caching queries/response content. It is recommended "
            ."to investigate the following URIs for slow responses: ".$this->slowResponseUris;
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $this->slowResponseUris = $this->executeQuery($this->findSlowResponseUris())->map(function($requestEntry) {
            try {
                return '['.$requestEntry->method.'] '.$this->getRouteForUrl($requestEntry->uri, $requestEntry->method);
            } catch (Throwable $_) {
                return '['.$requestEntry->method.'] '.$requestEntry->uri;
            }
        })->unique()->join(', ', ' and ');

        if (! empty($this->slowResponseUris)) {
            $this->markFailed();
        }
    }

    /**
     * Get the slow queries.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function findSlowResponseUris()
    {
        return TelescopeEntry::where('type', 'request')
                ->whereParam('duration', '>', config('enlightn.slow_response_threshold', 500))
                ->selectTotalEntries()
                ->addParams(['uri', 'method'])
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
