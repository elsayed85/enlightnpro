<?php

namespace Enlightn\EnlightnPro\Analyzers\Security;

use Enlightn\Enlightn\Analyzers\Concerns\InspectsCode;
use Enlightn\Enlightn\Analyzers\Security\SecurityAnalyzer;
use Enlightn\Enlightn\Inspection\Inspector;
use Enlightn\Enlightn\Inspection\QueryBuilder;

class DebugStatementAnalyzer extends SecurityAnalyzer
{
    use InspectsCode;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application does not contain debug statements.';

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
    public $timeToFix = 10;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return 'Your application contains debug statements that may potentially output the result to the response. '
            .'This exposes your application to numerous security risks including dumping sensitive environment '
            .'variables or secrets, exposing PHP variables that may potentially result in code injection attacks, etc.';
    }

    /**
     * Execute the analyzer.
     *
     * @param \Enlightn\Enlightn\Inspection\Inspector $inspector
     * @return void
     */
    public function handle(Inspector $inspector)
    {
        $builder = new QueryBuilder;

        collect(config('enlightn.debug_blacklist', [
            'var_dump', 'dump', 'dd', 'print_r', 'var_export', 'debug_print_backtrace',
            'debug_backtrace', 'debug_zval_dump',
        ]))->each(function ($functionName) use ($builder) {
            $builder->doesntHaveFunctionCall($functionName);
        });

        $this->inspectCode($inspector, $builder);
    }
}
