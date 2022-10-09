<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use Enlightn\EnlightnPro\Analyzers\Concerns\AnalyzesRoutes;
use Enlightn\EnlightnPro\Analyzers\Concerns\AnalyzesTelescopeEntries;
use Enlightn\EnlightnPro\TelescopeEntry;
use Throwable;

class TelescopeMemoryIntensiveRequestAnalyzer extends PerformanceAnalyzer
{
    use AnalyzesTelescopeEntries, AnalyzesRoutes;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application does not use too much memory while handling requests.';

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
    protected $memoryIntensiveRoutes;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application uses too much memory for certain routes: {$this->memoryIntensiveRoutes}. "
            ."There may be a variety of methods that can help you reduce memory usage including identifying "
            ."memory leaks, using lazy collections, avoiding model hydrations, caching, chunking and disabling "
            ."dev or debug packages in production.";
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $this->memoryIntensiveRoutes = $this->executeQuery($this->findMemoryIntensiveRoutes())
            ->map(function ($requestEntry) {
                try {
                    return '['.$requestEntry->method.'] '.$this->getRouteForUrl($requestEntry->uri, $requestEntry->method);
                } catch (Throwable $_) {
                    return '['.$requestEntry->method.'] '.$requestEntry->uri;
                }
            })->unique()->join(', ', ' and ');

        if (! empty($this->memoryIntensiveRoutes)) {
            $this->markFailed();
        }
    }

    /**
     * Get the memory intensive routes.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function findMemoryIntensiveRoutes()
    {
        $requestBenchmark = config('enlightn.request_memory_limit', 50);

        return TelescopeEntry::where('type', 'request')
                ->when(
                    config('database.connections.'.config('telescope.storage.database.connection').'.driver') == 'pgsql',
                    function ($query) use ($requestBenchmark) {
                        // PostgreSQL requires an implicit cast to integer for comparison.
                        return $query->whereRaw('cast("content"->>\'memory\' as integer) > ?', [$requestBenchmark]);
                    },
                    function ($query) use ($requestBenchmark) {
                        return $query->whereParam('memory', '>', $requestBenchmark);
                    }
                )
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
