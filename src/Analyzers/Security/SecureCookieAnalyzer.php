<?php

namespace Enlightn\EnlightnPro\Analyzers\Security;

use Enlightn\Enlightn\Analyzers\Concerns\ParsesConfigurationFiles;
use Enlightn\Enlightn\Analyzers\Security\SecurityAnalyzer;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class SecureCookieAnalyzer extends SecurityAnalyzer
{
    use ParsesConfigurationFiles;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Cookies are secured with HTTPS.';

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
    public $timeToFix = 1;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your app session cookies are insecure as the Secure option is disabled in your "
            ."session configuration. This exposes your application to possible man-in-the-middle "
            ."attacks.";
    }

    /**
     * Execute the analyzer.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function handle(ConfigRepository $config)
    {
        if ($config->get('session.secure') === false) {
            $this->recordError('session', 'secure');
        }
    }
}
