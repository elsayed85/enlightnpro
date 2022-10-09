<?php

namespace Enlightn\EnlightnPro\Analyzers\Security;

use Enlightn\Enlightn\Analyzers\Concerns\ParsesPHPStanAnalysis;
use Enlightn\Enlightn\Analyzers\Security\SecurityAnalyzer;
use Enlightn\Enlightn\PHPStan;

class ExtractAnalyzer extends SecurityAnalyzer
{
    use ParsesPHPStanAnalysis;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application is exposed to extract code injection vulnerabilities.';

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
    public $timeToFix = 5;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application uses the PHP extract function on untrusted user data. This can be very dangerous "
            ."as it can overwrite variables including super globals. This could expose your application to code "
            ."injection attacks.";
    }

    /**
     * Execute the analyzer.
     *
     * @param \Enlightn\Enlightn\PHPStan $phpStan
     * @return void
     */
    public function handle(PHPStan $phpStan)
    {
        $this->parsePHPStanAnalysis($phpStan, 'extract on untrusted user data');
    }
}
