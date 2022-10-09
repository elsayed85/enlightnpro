<?php

namespace Enlightn\EnlightnPro\Analyzers\Reliability;

use Enlightn\Enlightn\Analyzers\Concerns\ParsesConfigurationFiles;
use Enlightn\Enlightn\Analyzers\Reliability\ReliabilityAnalyzer;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class QueueBlockingAnalyzer extends ReliabilityAnalyzer
{
    use ParsesConfigurationFiles;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Queue blocking is setup properly when using Redis queues.';

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
        return "Setting `block_for` to 0 will cause queue workers to block indefinitely until a job is available. "
            ."This will also prevent signals such as SIGTERM (for terminating the queue worker) from being handled "
            ."until the next job has been processed. Either set this value to null or greater than zero.";
    }

    /**
     * Execute the analyzer.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function handle(ConfigRepository $config)
    {
        $connection = $config->get('queue.default');
        $blockFor = $config->get("queue.connections.{$connection}.block_for");

        if (! is_null($blockFor) && $blockFor == 0) {
            $this->recordError('queue', 'block_for', ['connections', $connection]);
        }
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        // Skip the analyzer if application does not use the Redis queue driver.
        return config('queue.connections.'.config('queue.default').'.driver') !== 'redis';
    }
}
