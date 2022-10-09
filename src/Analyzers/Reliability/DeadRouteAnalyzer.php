<?php

namespace Enlightn\EnlightnPro\Analyzers\Reliability;

use Enlightn\Enlightn\Analyzers\Reliability\ReliabilityAnalyzer;
use Exception;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Reflector;
use Illuminate\Support\Str;

class DeadRouteAnalyzer extends ReliabilityAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application does not contain any dead routes.';

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
    public $timeToFix = 10;

    /**
     * A collection of dead routes.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $deadRoutes;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application contains some dead routes, which have invalid methods or actions (e.g. controllers "
            ."that do not exist or methods that cannot be invoked, etc.). Dead routes include {$this->formatDeadRoutes()}";
    }

    /**
     * Execute the analyzer.
     *
     * @param Router $router
     * @return void
     */
    public function handle(Router $router)
    {
        $this->deadRoutes = collect($router->getRoutes())->filter(function ($route) {
            try {
                return $this->routeHasInvalidMethods($route) || $this->routeHasInvalidAction($route);
            } catch (Exception $e) {
                return true;
            }
        })->map(function ($route) {
            // Prettify dead routes to display in error message
            return '['.implode(',', $route->methods()).'] '.$route->uri();
        });;

        if ($this->deadRoutes->count() > 0) {
            $this->markFailed();
        }
    }

    /**
     * Determine whether the route method is valid.
     *
     * @param Route $route
     * @return bool
     */
    protected function routeHasInvalidMethods(Route $route)
    {
        return ! empty(array_diff(array_map('strtoupper', $route->methods()), Router::$verbs));
    }

    /**
     * Determine whether the route action is valid.
     *
     * @param Route $route
     * @return bool
     */
    protected function routeHasInvalidAction(Route $route)
    {
        if (empty($route->action)) {
            return true;
        }

        if ($route->getActionName() === 'Closure') {
            return false;
        }

        if (! isset($route->action['uses']) || empty($route->action['uses'])) {
            return true;
        }

        $callback = Str::parseCallback($route->action['uses']);

        if (! class_exists($callback[0]) || ! method_exists($callback[0], $callback[1])
            || ! $this->isCallable($callback)) {
                return true;
        }

        return false;
    }

    /**
     * Determine whether the callback is callable.
     *
     * @param  mixed  $callback
     * @return bool
     */
    protected function isCallable($callback) {
        if (class_exists(Reflector::class) && method_exists(Reflector::class, 'isCallable')) {
            // This check is added for Laravel 6-7x compatibility.
            return Reflector::isCallable($callback);
        } else {
            return is_callable($callback);
        }
    }

    /**
     * @return string
     */
    protected function formatDeadRoutes()
    {
        return $this->deadRoutes->join(', ', ' and ');
    }
}
