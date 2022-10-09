<?php

namespace Enlightn\EnlightnPro\Analyzers\Security;

use Enlightn\Enlightn\Analyzers\Concerns\ParsesConfigurationFiles;
use Enlightn\Enlightn\Analyzers\Security\SecurityAnalyzer;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class NovaSecurityAnalyzer extends SecurityAnalyzer
{
    use ParsesConfigurationFiles;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Nova uses a separate sub-domain with its own set of cookies to protect against session hijacking.';

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
     * Execute the analyzer.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     * @return void
     */
    public function handle(ConfigRepository $config)
    {
        if (is_null($config->get('nova.domain'))) {
            $this->recordError('nova', 'domain');

            $this->errorMessage = 'Nova does not use a separate sub-domain and set of cookies. This exposes your '
                .'application to session hijacking, where if either your main application or Nova is compromised '
                .'(e.g. cookie is stolen), then the other would also be compromised. It is recommended to configure '
                .'Nova to use its own sub-domain.';
        } elseif (! is_null($config->get('session.domain'))) {
            $this->recordError('session', 'domain');

            $this->errorMessage = 'While Nova is currently using a separate domain, your application session '
                .'cookies are still shared with Nova. This exposes your application to session hijacking, where if '
                .'either your main application or Nova is compromised, then the other would also be compromised. '
                .'It is recommended to configure separate cookies by setting the session domain configuration to '
                .'null.';
        }
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        // Skip this analyzer if the app doesn't use Nova.
        return is_null(config('nova.url')) || $this->isLocalAndShouldSkip();
    }
}
