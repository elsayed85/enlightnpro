<?php

namespace Enlightn\EnlightnPro\Analyzers\Reliability;

use Enlightn\Enlightn\Analyzers\Concerns\ParsesConfigurationFiles;
use Enlightn\Enlightn\Analyzers\Reliability\ReliabilityAnalyzer;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class HorizonPrefixAnalyzer extends ReliabilityAnalyzer
{
    use ParsesConfigurationFiles;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Horizon prefix is set to avoid collisions with other apps.';

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
    public $timeToFix = 1;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your Horizon prefix is too generic and may result in collisions with other apps "
            ."that share the same Redis servers.";
    }

    /**
     * Execute the analyzer.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function handle(ConfigRepository $config)
    {
        if (! ($prefix = $config->get('horizon.prefix')) ||
            $prefix == 'laravel_horizon:') {
            $this->recordError('horizon', 'prefix');
        }
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        // Skip this analyzer if the app doesn't use Horizon.
        return is_null(config('horizon.use'));
    }
}
