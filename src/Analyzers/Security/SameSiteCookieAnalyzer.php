<?php

namespace Enlightn\EnlightnPro\Analyzers\Security;

use Enlightn\Enlightn\Analyzers\Concerns\ParsesConfigurationFiles;
use Enlightn\Enlightn\Analyzers\Security\SecurityAnalyzer;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class SameSiteCookieAnalyzer extends SecurityAnalyzer
{
    use ParsesConfigurationFiles;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Cookies have a secure Same-Site attribute.';

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
     * The Same-Site attribute set in configuration.
     *
     * @var string
     */
    public $sameSite;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your app cookies are insecure as the Same-Site attribute is set to [{$this->sameSite}]. "
            ."This exposes your application to possible CSRF attacks. It is recommended to set the attribute "
            ."to [lax] or [strict]";
    }

    /**
     * Execute the analyzer.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function handle(ConfigRepository $config)
    {
        if (! in_array($this->sameSite = $config->get('session.same_site', 'null'), ['lax', 'strict'])) {
            $this->sameSite = is_null($this->sameSite) ? 'null' : $this->sameSite;

            // Only lax and strict are recommended.
            $this->recordError('session', 'same_site');
        }
    }
}
