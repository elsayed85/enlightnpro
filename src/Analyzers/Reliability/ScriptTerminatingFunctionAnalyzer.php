<?php

namespace Enlightn\EnlightnPro\Analyzers\Reliability;

use Enlightn\Enlightn\Analyzers\Concerns\InspectsCode;
use Enlightn\Enlightn\Analyzers\Reliability\ReliabilityAnalyzer;
use Enlightn\Enlightn\Inspection\Inspector;
use Enlightn\Enlightn\Inspection\QueryBuilder;

class ScriptTerminatingFunctionAnalyzer extends ReliabilityAnalyzer
{
    use InspectsCode;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = "Your application does not use unrecommended script termination functions.";

    /**
     * The severity of the analyzer.
     *
     * @var string|null
     */
    public $severity = self::SEVERITY_MINOR;

    /**
     * The time to fix in minutes.
     *
     * @var int|null
     */
    public $timeToFix = 5;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application uses unrecommended script termination functions. "
            ."It is recommended to return the exit status for CLI Commands and use the abort helper "
            ."for web requests.";
    }

    /**
     * Execute the analyzer.
     *
     * @param Inspector $inspector
     * @return void
     */
    public function handle(Inspector $inspector)
    {
        $builder = (new QueryBuilder())->doesntHaveExitStatement();

        collect(config('enlightn.terminating_function_blacklist', [
            'exit', 'die',
        ]))->each(function ($functionName) use ($builder) {
            $builder->doesntHaveFunctionCall($functionName);
        });

        $this->inspectCode($inspector, $builder);
    }
}
