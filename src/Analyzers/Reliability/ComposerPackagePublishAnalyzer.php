<?php

namespace Enlightn\EnlightnPro\Analyzers\Reliability;

use Enlightn\Enlightn\Analyzers\Reliability\ReliabilityAnalyzer;
use Enlightn\Enlightn\Composer;
use Illuminate\Support\Str;

class ComposerPackagePublishAnalyzer extends ReliabilityAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = "Your application is configured to re-publish package assets on update.";

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
     * The packages that do not have the post-update-cmd script setup.
     *
     * @var array
     */
    public $affectedPackages = [];

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application's composer.json file does not include recommended post-update-cmd scripts to "
            ."re-publish your package assets on update. Affected packages include: ".$this->formatAffectedPackages();
    }

    /**
     * Execute the analyzer.
     *
     * @param Composer $composer
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle(Composer $composer)
    {
        $composerJson = $composer->getJson();

        $packages = [
            'horizon.use' => 'horizon:publish',
            'nova.url' => 'nova:publish',
            'telescope.driver' => 'telescope:publish',
        ];

        foreach ($packages as $configKey => $publishCommand) {
            if (is_null(config($configKey))) {
                // Package is not being used by the application.
                continue;
            }

            if (! isset($composerJson['scripts']['post-update-cmd'])) {
                $this->recordError($configKey);

                continue;
            }

            foreach ($composerJson['scripts']['post-update-cmd'] as $script) {
                if (Str::contains($script, $publishCommand)) {
                    continue 2;
                }
            }

            $this->recordError($configKey);
        }
    }

    /**
     * Record the error for the config key.
     *
     * @param string $configKey
     */
    protected function recordError(string $configKey)
    {
        $this->affectedPackages[] = ucfirst(Str::before($configKey, '.'));
        $this->markFailed();
    }

    /**
     * Format the affected packages for the error message.
     *
     * @return string
     */
    protected function formatAffectedPackages()
    {
        return collect($this->affectedPackages)->join(', ', ' and ');
    }
}
