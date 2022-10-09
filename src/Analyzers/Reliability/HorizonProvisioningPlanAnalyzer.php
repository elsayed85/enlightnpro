<?php

namespace Enlightn\EnlightnPro\Analyzers\Reliability;

use Enlightn\Enlightn\Analyzers\Concerns\ParsesConfigurationFiles;
use Enlightn\Enlightn\Analyzers\Reliability\ReliabilityAnalyzer;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class HorizonProvisioningPlanAnalyzer extends ReliabilityAnalyzer
{
    use ParsesConfigurationFiles;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = "Horizon has a provisioning plan configured for the current environment.";

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
        return "Horizon does not have a provisioning plan configured for your current environment. Please add "
            ."your current environment: [".config('app.env')."] as a key to the horizon.environments "
            ."configuration to be able to use Horizon in this environment.";
    }

    /**
     * Execute the analyzer.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     * @return void
     */
    public function handle(ConfigRepository $config)
    {
        if (is_null($config->get('horizon.environments.'.$config->get('app.env')))) {
            $this->recordError('horizon', 'environments');
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
