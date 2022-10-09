<?php

namespace Enlightn\EnlightnPro\Analyzers\Security;

use Enlightn\Enlightn\Analyzers\Concerns\ParsesPHPStanAnalysis;
use Enlightn\Enlightn\Analyzers\Security\SecurityAnalyzer;
use Enlightn\Enlightn\PHPStan;

class CommandInjectionAnalyzer extends SecurityAnalyzer
{
    use ParsesPHPStanAnalysis;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application is not exposed to command injection attacks.';

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
    public $timeToFix = 5;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return 'Your application executes a shell command constructed with unescaped user provided data. This '
            .'can be very dangerous and exposes your application to command injection attacks. It is important '
            .'to use the escapeshellarg or escapeshellcmd functions to escape user provided data to stay secure.';
    }

    /**
     * Execute the analyzer.
     *
     * @param \Enlightn\Enlightn\PHPStan $PHPStan
     * @return void
     */
    public function handle(PHPStan $PHPStan)
    {
        $this->parsePHPStanAnalysis($PHPStan, 'command injection attack');
    }
}
