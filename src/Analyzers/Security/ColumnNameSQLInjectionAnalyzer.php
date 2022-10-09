<?php

namespace Enlightn\EnlightnPro\Analyzers\Security;

use Enlightn\Enlightn\Analyzers\Concerns\ParsesPHPStanAnalysis;
use Enlightn\Enlightn\Analyzers\Security\SecurityAnalyzer;
use Enlightn\Enlightn\PHPStan;

class ColumnNameSQLInjectionAnalyzer extends SecurityAnalyzer
{
    use ParsesPHPStanAnalysis;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application is not exposed to column name SQL Injection attacks.';

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
    public $timeToFix = 10;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application allows user controlled request data to dictate the column names in a SQL query. "
            ."This exposes your application to SQL injection attacks as PDO does not support binding column names.";
    }

    /**
     * Execute the analyzer.
     *
     * @param \Enlightn\Enlightn\PHPStan $phpStan
     * @return void
     */
    public function handle(PHPStan $phpStan)
    {
        $this->parsePHPStanAnalysis($phpStan, 'allows user input to dictate column names');
    }
}
