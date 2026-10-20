<?php

namespace Tests\Support;

use App\Models\Router;
use App\Services\Config\ConfigExporter;

class FakeExporter implements ConfigExporter
{
    public string $config = "/ip address\nadd address=192.168.88.1/24 interface=ether1\n";

    public bool $fail = false;

    public int $calls = 0;

    public function export(Router $router): string
    {
        $this->calls++;

        if ($this->fail) {
            throw new \RuntimeException('SSH login failed');
        }

        return $this->config;
    }
}
