<?php

namespace Enlightn\EnlightnPro\Analyzers\Reliability;

use Enlightn\Enlightn\Analyzers\Reliability\ReliabilityAnalyzer;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Str;

class FailedJobTimeoutAnalyzer extends ReliabilityAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = "Your jobs are not timing out.";

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
     * Determine whether the analyzer should be run in CI mode.
     *
     * @var bool
     */
    public static $runInCI = false;

    /**
     * @var string
     */
    public $timedOutJobClasses;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Some of your jobs are timing out and unable to complete processing. You should consider "
            ."either increasing the timeout for these jobs or breaking the job down into smaller jobs. "
            ."The jobs that are timing out include: ".$this->timedOutJobClasses.".";
    }

    /**
     * Execute the analyzer.
     *
     * @param \Illuminate\Queue\Failed\FailedJobProviderInterface $failedJobProvider
     * @return void
     */
    public function handle(FailedJobProviderInterface $failedJobProvider)
    {
        $this->timedOutJobClasses = collect($failedJobProvider->all())->filter(function ($failedJob) {
            // First, we reject all the jobs that have failed due to a job exception during processing.
            return Str::contains($failedJob->exception, MaxAttemptsExceededException::class);
        })->map(function ($failedJob) {
            $payload = json_decode($failedJob->payload, true);

            // Next, we filter out all jobs that have a maxExceptions set, because they may have failed
            // due to an exception trigger (we cannot be sure if it was a timeout that cause the failure).
            if (isset($payload['maxExceptions'])) {
                return null;
            } else {
                return $payload['displayName'] ?? null;
            }
        })->filter()->countBy()->sortByDesc(function ($count) {
            return $count;
        })->keys()->map(function ($timedOutJobClass) {
            return '['.$timedOutJobClass.']';
        })->join(', ', ' and ');

        if (! empty($this->timedOutJobClasses)) {
            $this->markFailed();
        }
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        // Skip the analyzer if the application doesn't use queues.
        return in_array(config('queue.connections.'.config('queue.default').'.driver'), ['sync', 'null']);
    }
}
