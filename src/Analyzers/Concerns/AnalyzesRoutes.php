<?php

namespace Enlightn\EnlightnPro\Analyzers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;

trait AnalyzesRoutes
{
    /**
     * Get the route URI from the URL and method.
     *
     * @param string $url
     * @param string $method
     * @return string
     */
    protected function getRouteForUrl(string $url, string $method)
    {
        /** @var \Illuminate\Http\Request $request */
        $request = app(Request::class);

        /** @var \Illuminate\Routing\Router $router */
        $router = app(Router::class);

        return $router->getRoutes()->match($request->create($url, $method))->uri();
    }
}
