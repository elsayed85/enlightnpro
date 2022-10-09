<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Concerns\ParsesPHPStanAnalysis;
use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use Enlightn\Enlightn\PHPStan;

class CommandConstructorInjectionAnalyzer extends PerformanceAnalyzer
{
    use ParsesPHPStanAnalysis;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Constructor injection is not used in non-lazy loaded commands.';

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
    public $timeToFix = 10;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return 'Laravel instantiates all non-lazy loaded commands to be registered in the console application '
                .'so it is not recommended to use constructor injection in non-lazy loaded commands as all the '
                .'command dependencies are loaded in memory, regardless of whether the command is called or not. '
                .'It is recommended to move the dependencies to the handle method instead.';
    }

    /**
     * Execute the analyzer.
     *
     * @param \Enlightn\Enlightn\PHPStan $phpStan
     * @return void
     */
    public function handle(PHPStan $phpStan)
    {
        $this->parsePHPStanAnalysis($phpStan, 'constructor injection in non-lazy loaded command');
    }
}
