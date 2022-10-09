<?php

namespace Enlightn\EnlightnPro\Analyzers\Performance;

use Enlightn\Enlightn\Analyzers\Performance\PerformanceAnalyzer;
use Illuminate\Routing\Router;

class FallbackRouteAnalyzer extends PerformanceAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application does not rely on fallback routes.';

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
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application registers one or more fallback routes. Fallback routes are typically not "
            ."considered as a good practice, especially if SEO matters for your application. Developers "
            ."sometimes turn to fallback routes for redirecting to a single page app that handles all "
            ."requests and renders a 404 not found page if the route is not registered on the frontend. "
            ."This however leads to poor SEO because even though a page not found was rendered, the "
            ."response code is still 200. These are called soft 404 pages and can hurt your search engine "
            ."ranking.";
    }

    /**
     * Execute the analyzer.
     *
     * @param \Illuminate\Routing\Router $router
     * @return void
     */
    public function handle(Router $router)
    {
        if ((collect($router->getRoutes()))->filter(function ($route) {
            return $route->isFallback ?? false;
        })->count() > 0) {
            $this->markFailed();
        }
    }
}
