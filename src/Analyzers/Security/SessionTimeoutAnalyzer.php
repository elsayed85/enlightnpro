<?php

namespace Enlightn\EnlightnPro\Analyzers\Security;

use Enlightn\Enlightn\Analyzers\Concerns\AnalyzesMiddleware;
use Enlightn\Enlightn\Analyzers\Concerns\ParsesConfigurationFiles;
use Enlightn\Enlightn\Analyzers\Security\SecurityAnalyzer;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;

class SessionTimeoutAnalyzer extends SecurityAnalyzer
{
    use ParsesConfigurationFiles, AnalyzesMiddleware;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'An appropriate session timeout is set.';

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
    public $timeToFix = 2;

    /**
     * Create a new analyzer instance.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @param  \Illuminate\Contracts\Http\Kernel  $kernel
     * @return void
     */
    public function __construct(Router $router, Kernel $kernel)
    {
        $this->router = $router;
        $this->kernel = $kernel;
    }

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "The session timeout represents the time interval during which if there is no "
                ."user activity, the user will be logged out. Session timeouts should be as low as "
                ."possible so that users that may be using public terminals are automatically logged "
                ."out after inactivity, thereby preventing user accounts from being compromised. "
                ."Your current session timeout is set too high. The Laravel default of 2 hours "
                ."seems like a smart choice for most applications.";
    }

    /**
     * Execute the analyzer.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function handle(ConfigRepository $config)
    {
        if ($config->get('session.lifetime', 0) > 1440) {
            // If the session timeout is greater than a day, that should raise a flag.
            $this->recordError('session', 'lifetime');
        }
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function skip()
    {
        // Skip the analyzer if the session auto-expires on close or if the app is stateless.
        return config('session.expire_on_close', false) || $this->appIsStateless();
    }
}
