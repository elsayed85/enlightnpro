<?php

namespace Enlightn\EnlightnPro\Analyzers\Security;

use Enlightn\Enlightn\Analyzers\Concerns\ParsesPHPStanAnalysis;
use Enlightn\Enlightn\Analyzers\Security\SecurityAnalyzer;
use Enlightn\Enlightn\PHPStan;

class UnrestrictedFileUploadAnalyzer extends SecurityAnalyzer
{
    use ParsesPHPStanAnalysis;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application is not exposed to unrestricted file upload vulnerabilities.';

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
        return "Your application allows user controlled data to construct the path of a file upload. This could expose "
            ."your application to unrestricted file upload attacks.";
    }

    /**
     * Execute the analyzer.
     *
     * @param \Enlightn\Enlightn\PHPStan $phpStan
     * @return void
     */
    public function handle(PHPStan $phpStan)
    {
        $this->parsePHPStanAnalysis($phpStan, 'unrestricted file upload');
    }
}
