<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use Enlightn\EnlightnPro\Analyzers\Concerns\AnalyzesTelescopeEntries;
use Enlightn\EnlightnPro\TelescopeEntry;

class TelescopeNonQueuedNotificationAnalyzer extends PerformanceAnalyzer
{
    use AnalyzesTelescopeEntries;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = "Your application's notifications are queued.";

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
     * The list of non-queued notifications.
     *
     * @var string
     */
    protected $nonQueuedNotifications;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application has some notifications that are not queued. It is generally considered "
            ."a good practice to queue notifications for faster response times and the ability to retry "
            ."them in case of external service outages. Notifications include: {$this->nonQueuedNotifications}.";
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $this->nonQueuedNotifications = $this->executeQuery($this->findNonQueuedNotifications())->map(function($entry) {
           return '['.$entry->notification.']';
        })->join(', ', ' and ');

        if (! empty($this->nonQueuedNotifications)) {
            $this->markFailed();
        }
    }

    /**
     * Get the slow queries.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function findNonQueuedNotifications()
    {
        return TelescopeEntry::where('type', 'notification')
                ->whereParam('queued', false)
                ->selectTotalEntries()
                ->addParams(['notification'])
                ->groupByColNumbers([2])
                ->orderByTotalEntries();
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        // Skip this analyzer if the app doesn't use Telescope.
        return is_null(config('telescope.driver'));
    }
}
