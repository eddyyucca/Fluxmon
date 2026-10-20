<?php

namespace App\Services\Config;

use App\Models\Router;
use phpseclib4\Net\SSH2;

class SshConfigExporter implements ConfigExporter
{
    public function export(Router $router): string
    {
        $ssh = new SSH2($router->host, $router->ssh_port, $router->timeout + 5);

        try {
            if (! $ssh->login($router->username, $router->password)) {
                throw new \RuntimeException("SSH login to {$router->host}:{$router->ssh_port} failed. Enable the SSH service on the router and use an account that may log in over SSH.");
            }
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \RuntimeException("Cannot open SSH to {$router->host}:{$router->ssh_port}: ".$e->getMessage(), 0, $e);
        }

        $this->verifyHostKey($router, $ssh);

        $ssh->setTimeout(60);
        $output = (string) $ssh->exec('/export terse');

        // Very old RouterOS builds do not know "terse".
        if ($output === '' || stripos($output, 'expected end of command') !== false) {
            $output = (string) $ssh->exec('/export');
        }
        $ssh->disconnect();

        if (trim($output) === '') {
            throw new \RuntimeException('The router returned an empty export.');
        }

        return $this->normalise($output);
    }

    /** Trust on first use: pin the host key, then refuse if it ever changes. */
    private function verifyHostKey(Router $router, SSH2 $ssh): void
    {
        $key = $ssh->getServerPublicHostKey();
        if ($key === null) {
            return;
        }

        $fingerprint = hash('sha256', $key);
        if ($router->ssh_fingerprint === null) {
            $router->forceFill(['ssh_fingerprint' => $fingerprint])->save();

            return;
        }

        if (! hash_equals($router->ssh_fingerprint, $fingerprint)) {
            throw new \RuntimeException('The router\'s SSH host key changed since the first backup. If you reinstalled the router, clear the pinned key in the router settings; otherwise someone may be intercepting the connection.');
        }
    }

    /** Drop the timestamp header so identical configs hash identically. */
    private function normalise(string $export): string
    {
        $lines = preg_split('/\R/', rtrim($export));
        $lines = array_filter($lines, fn (string $l) => ! preg_match('/^#.*\bby RouterOS\b/', $l));

        return implode("\n", $lines)."\n";
    }
}
