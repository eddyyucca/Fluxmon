<?php

namespace App\Services\Config;

use App\Models\Router;

/**
 * Produces the router's configuration as RouterOS script text.
 * (The RouterOS API cannot run /export, so the real implementation uses SSH.)
 */
interface ConfigExporter
{
    /** @throws \RuntimeException when the router cannot be reached or refuses the export */
    public function export(Router $router): string;
}
