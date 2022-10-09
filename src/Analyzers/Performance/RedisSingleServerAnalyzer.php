<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Concerns\ParsesConfigurationFiles;
use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\ConfigurationUrlParser;
use Illuminate\Support\Str;
use Enlightn\Enlightn\Analyzers\Concerns\DetectsRedis;
use Throwable;

class RedisSingleServerAnalyzer extends PerformanceAnalyzer
{
    use ParsesConfigurationFiles, DetectsRedis;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Redis is configured properly on single server setups.';

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
    public $timeToFix = 30;

    /**
     * Determine whether the analyzer should be run in CI mode.
     *
     * @var bool
     */
    public static $runInCI = false;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "When Redis is running on the same server as your app, it is recommended to use unix "
                ."sockets to improve performance by upto 50% (official Redis.io benchmark).";
    }

    /**
     * Execute the analyzer.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function handle(ConfigRepository $config)
    {
        $connections = collect($config->get('database.redis', []))->except(['client', 'options']);

        $parser = new ConfigurationUrlParser;

        $connections = $connections->map(function ($connection) use ($parser) {
            try {
                return $parser->parseConfiguration($connection);
            } catch (Throwable $e) {
                return $connection;
            }
        });

        if ($connections->contains('host', '127.0.0.1')
            && ! $connections->contains(function ($conf) {
                // Check if there are any connections that are local but don't use unix sockets
                return (isset($conf['scheme']) && $conf['scheme'] === 'unix')
                    || (isset($conf['host']) && Str::contains($conf['host'], '.sock'));
            })) {
            // On same server setups, it is recommended to use unix sockets
            $this->recordError('database', 'redis');
        }
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        return $this->isLocalAndShouldSkip() || ! $this->appUsesRedis();
    }
}
