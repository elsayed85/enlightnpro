<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use Enlightn\EnlightnPro\Analyzers\Concerns\AnalyzesRoutes;
use Enlightn\EnlightnPro\Analyzers\Concerns\AnalyzesTelescopeEntries;
use Enlightn\EnlightnPro\TelescopeEntry;
use Illuminate\Support\Facades\DB;
use Throwable;

class TelescopeDuplicateQueryAnalyzer extends PerformanceAnalyzer
{
    use AnalyzesTelescopeEntries, AnalyzesRoutes;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application does not have duplicate queries.';

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
     * @var string
     */
    protected $duplicateQueryRoutes;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application has some duplicate queries in the following routes: ".$this->duplicateQueryRoutes;
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $this->duplicateQueryRoutes = $this->executeQuery($this->findDuplicateQueryRoutes())
            ->map(function ($requestEntry) {
                try {
                    return '['.$requestEntry->method.'] '.$this->getRouteForUrl($requestEntry->uri, $requestEntry->method);
                } catch (Throwable $_) {
                    return '['.$requestEntry->method.'] '.$requestEntry->uri;
                }
            })->unique()->join(', ', ' and ');

        if (! empty($this->duplicateQueryRoutes)) {
            $this->markFailed();
        }
    }

    /**
     * Get the duplicate queries.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function findDuplicateQueryRoutes()
    {
        // We get all the URI and method combinations that have batches with duplicate queries.
        return TelescopeEntry::where('type', 'request')
                ->whereIn('batch_id', function ($query) {
                    // In this subquery, we select the batches that have duplicate queries.
                    return $query->select('batch_id')
                        ->from('telescope_entries')
                        ->where('type', 'query')
                        ->groupBy('batch_id', 'content->sql')
                        ->having(DB::raw('count(uuid)'), '>', 1);
                })->selectTotalEntries()
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
