<?php

namespace App\Services\Mikrotik;

use App\Models\Router;

/**
 * Single place that decides how a Router record becomes a Gateway.
 * Tests bind a fake factory so no real router is needed.
 */
class MikrotikFactory
{
    public function make(Router $router): Gateway
    {
        return new RouterOsGateway($router);
    }
}
