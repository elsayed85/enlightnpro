<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use Enlightn\EnlightnPro\Analyzers\Concerns\AnalyzesRoutes;
use Enlightn\EnlightnPro\Analyzers\Concerns\AnalyzesTelescopeEntries;
use Enlightn\EnlightnPro\TelescopeEntry;
use Throwable;

class TelescopeModelHydrationAnalyzer extends PerformanceAnalyzer
{
    use AnalyzesTelescopeEntries, AnalyzesRoutes;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application does not hydrate too many models during requests.';

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
    protected $hydrationIntensiveRoutes;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application hydrates too many models for certain routes: {$this->hydrationIntensiveRoutes}. "
            ."This can impact your application performance and response time. It is recommended to investigate "
            ."if these hydrations can be removed through efficient or bulk queries and if not, you should ensure "
            ."that model chunking or lazy collections are used to avoid high memory usage.";
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $this->hydrationIntensiveRoutes = $this->executeQuery($this->findHydrationIntensiveRoutes())
            ->map(function ($requestEntry) {
                try {
                    return '['.$requestEntry->method.'] '.$this->getRouteForUrl($requestEntry->uri, $requestEntry->method);
                } catch (Throwable $_) {
                    return '['.$requestEntry->method.'] '.$requestEntry->uri;
                }
            })->unique()->join(', ', ' and ');

        if (! empty($this->hydrationIntensiveRoutes)) {
            $this->markFailed();
        }
    }

    /**
     * Get the memory intensive routes.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function findHydrationIntensiveRoutes()
    {
        $hydrationLimit = config('enlightn.hydration_limit', 50);
        $telescopeConnection = config('telescope.storage.database.connection');

        return TelescopeEntry::where('type', 'request')
            ->whereIn('batch_id', function ($query) use ($telescopeConnection, $hydrationLimit) {
                // In this subquery, we select the batches that have duplicate queries.
                return $query->select('batch_id')
                    ->from('telescope_entries')
                    ->where('type', 'model')
                    ->groupBy('batch_id')
                    ->when(
                        config('database.connections.'.$telescopeConnection.'.driver') == 'pgsql',
                        function ($query) use ($hydrationLimit) {
                            // PostgreSQL requires an implicit cast to integer for comparison.
                            return $query->whereRaw('cast("content"->>\'count\' as integer) > ?', [$hydrationLimit]);
                        },
                        function ($query) use ($hydrationLimit) {
                            return $query->where('content->count', '>', $hydrationLimit);
                        }
                    )
                    ->where('content->action', 'retrieved');
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
