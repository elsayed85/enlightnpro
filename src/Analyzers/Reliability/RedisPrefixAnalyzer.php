<?php

namespace Enlightn\EnlightnPro\Analyzers\Reliability;

use Enlightn\Enlightn\Analyzers\Concerns\ParsesConfigurationFiles;
use Enlightn\Enlightn\Analyzers\Reliability\ReliabilityAnalyzer;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Enlightn\Enlightn\Analyzers\Concerns\DetectsRedis;

class RedisPrefixAnalyzer extends ReliabilityAnalyzer
{
    use ParsesConfigurationFiles, DetectsRedis;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Redis prefix is set to avoid collisions with other apps.';

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
        return "Your Redis prefix is too generic and may result in collisions with other apps "
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
        if (! ($prefix = $config->get('database.redis.options.prefix')) ||
            $prefix == 'laravel_database_') {
            $this->recordError('database', 'prefix', ['redis', 'options']);
        }
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        return ! $this->appUsesRedis();
    }
}
