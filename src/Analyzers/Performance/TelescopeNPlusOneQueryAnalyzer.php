<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use Enlightn\EnlightnPro\Analyzers\Concerns\AnalyzesTelescopeEntries;
use Enlightn\EnlightnPro\TelescopeEntry;
use Illuminate\Support\Facades\DB;

class TelescopeNPlusOneQueryAnalyzer extends PerformanceAnalyzer
{
    use AnalyzesTelescopeEntries;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application does not have N+1 queries.';

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
        return "Your application has some N+1 queries. This can impact application performance and result in "
            ."slower response times. It is recommended to use eager-loading to remove these N+1 queries.";
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $this->executeQuery($this->findNPlusOneQueries())
            ->each(function ($entry) {
                $this->addTrace($entry->file, $entry->line);
            });
    }

    /**
     * Get the duplicate queries.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function findNPlusOneQueries()
    {
        $telescopeConnection = config('telescope.storage.database.connection');

        // We select the queries that have the same hash (of the unprepared SQL query), line number and file
        // within a request (same batch) but different bindings (> 1 distinct prepared SQL queries). This can
        // only presumably happen in a for loop that accesses relational properties without eager loading.
        $subquery = TelescopeEntry::select('batch_id')
                ->addParams(['line', 'file', 'hash'])
                ->where('type', 'query')
                ->groupByColNumbers([1, 2, 3, 4])
                ->when(config('database.connections.'.$telescopeConnection.'.driver') == 'pgsql',
                    function ($query) {
                        return $query->havingRaw('count(distinct("content"->>\'sql\')) > ?', [1]);
                    }, function ($query) {
                        return $query->havingRaw("count(distinct(content->>'$.sql')) > ?", [1]);
                    });

        return DB::connection($telescopeConnection)
                ->table($subquery, 'sub')
                ->select(['line', 'file'])
                ->groupByRaw("line")
                ->groupByRaw("file");
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        // Skip this analyzer if the app doesn't use Telescope or does not use Mysql or PostgreSQL.
        return is_null(config('telescope.driver')) || ! in_array(
                config('database.connections.'.config('telescope.storage.database.connection').'.driver'),
                ['pgsql', 'mysql']
            );
    }
}
