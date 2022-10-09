<?php

namespace Enlightn\EnlightnPro\Analyzers\Security;

use Enlightn\Enlightn\Analyzers\Concerns\InspectsCode;
use Enlightn\Enlightn\Analyzers\Security\SecurityAnalyzer;
use Enlightn\Enlightn\Inspection\Inspector;
use Enlightn\Enlightn\Inspection\QueryBuilder;

class EvalAnalyzer extends SecurityAnalyzer
{
    use InspectsCode;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application does not use the dangerous "eval" PHP function.';

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
        return 'Your application uses the dangerous "eval" PHP function that allows execution of arbitrary PHP code. '
            .'This exposes your application to code injection attacks. Although proper input validation may '
            .'reduce the risk significantly, it is still advised to use alternative and safer ways to achieve '
            .'the desired outcome.';
    }

    /**
     * Execute the analyzer.
     *
     * @param \Enlightn\Enlightn\Inspection\Inspector $inspector
     * @return void
     */
    public function handle(Inspector $inspector)
    {
        $builder = (new QueryBuilder())->doesntHaveEvalExpression();

        $this->inspectCode($inspector, $builder);
    }
}
