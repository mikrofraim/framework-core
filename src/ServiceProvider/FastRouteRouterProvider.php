<?php

declare(strict_types=1);

namespace Mikrofraim\ServiceProvider;

use Mikrofraim\Http\Router\FastRouteRouter;
use Mikrofraim\ServiceProvider;

class FastRouteRouterProvider extends ServiceProvider
{
    public function createService(): FastRouteRouter
    {
        return new FastRouteRouter(
            $this->config->get('basePath') . '/config/routes.php'
        );
    }
}
