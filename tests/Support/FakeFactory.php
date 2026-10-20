<?php

namespace Tests\Support;

use App\Models\Router;
use App\Services\Mikrotik\Gateway;
use App\Services\Mikrotik\MikrotikFactory;

class FakeFactory extends MikrotikFactory
{
    public function __construct(public FakeGateway $gateway = new FakeGateway())
    {
    }

    public function make(Router $router): Gateway
    {
        return $this->gateway;
    }
}
