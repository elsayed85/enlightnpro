<?php

namespace Enlightn\EnlightnPro\Analyzers\Security;

use Enlightn\Enlightn\Analyzers\Concerns\InspectsCode;
use Enlightn\Enlightn\Analyzers\Security\SecurityAnalyzer;
use Enlightn\Enlightn\Inspection\Inspector;
use Enlightn\Enlightn\Inspection\QueryBuilder;
use Illuminate\Support\Facades\DB;
use mysqli;
use PDO;

class SqlInjectionAnalyzer extends SecurityAnalyzer
{
    use InspectsCode;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application does not use native PHP database code that is prone to SQL injection.';

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
    public $timeToFix = 30;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application uses dangerous code such as direct interaction with the PDO object, "
            ."native PHP database functions or the Laravel DB facade's unprepared method. These "
            ."methods or objects are prone to SQL injection attacks, and Laravel takes care of "
            ."this to a large extent through it's Eloquent or DB facade methods using bindings. "
            ."While carefully crafted code with proper bindings and data validation can mitigate "
            ."this risk, it's always best to use higher level libraries for database queries and "
            ."drop any native PHP database code.";
    }

    /**
     * Execute the analyzer.
     *
     * @param \Enlightn\Enlightn\Inspection\Inspector $inspector
     * @return void
     */
    public function handle(Inspector $inspector)
    {
        $builder = (new QueryBuilder())
            ->doesntInstantiate(PDO::class)
            ->doesntInstantiate(mysqli::class);

        collect(config('enlightn.unsafe_sql_functions', array_merge([
            'mysqli_connect', 'mysqli_execute', 'mysqli_stmt_execute', 'mysqli_stmt_close', 'mysqli_stmt_fetch',
            'mysqli_stmt_get_result', 'mysqli_stmt_more_results', 'mysqli_stmt_next_result', 'mysqli_stmt_prepare',
            'mysqli_close', 'mysqli_commit', 'mysqli_begin_transaction', 'mysqli_init', 'mysqli_insert_id',
            'mysqli_prepare', 'mysqli_query', 'mysqli_real_connect', 'mysqli_real_query', 'mysqli_store_result',
            'mysqli_use_result', 'mysqli_multi_query',
        ], [
            'pg_connect', 'pg_close', 'pg_affected_rows', 'pg_delete', 'pg_execute', 'pg_fetch_all', 'pg_fetch_result',
            'pg_fetch_row', 'pg_fetch_all_columns', 'pg_fetch_array', 'pg_fetch_assoc', 'pg_fetch_object', 'pg_flush',
            'pg_insert', 'pg_get_result', 'pg_pconnect', 'pg_prepare', 'pg_query', 'pg_query_params', 'pg_select',
            'pg_send_execute', 'pg_send_prepare', 'pg_send_query', 'pg_send_query_params', 'pg_affected_rows',
        ])))->each(function ($functionName) use ($builder) {
            $builder->doesntHaveFunctionCall($functionName);
        });

        $builder->doesntHaveStaticCall(DB::class, 'unprepared');

        $this->inspectCode($inspector, $builder);
    }
}
