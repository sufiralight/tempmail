<?php

namespace App\Installation;

use Illuminate\Http\Request;

final class InstallationAccess
{
    public function installed(): bool
    {
        $path = config('installer.path').'/installed';

        return file_exists($path) || is_link($path);
    }

    /**
     * @return array{local: bool, host: string, port: int, scope: string}|null
     */
    public function forRequest(Request $request): ?array
    {
        $path = config('installer.path').'/enable.json';

        if ($this->installed() || is_link($path) || ! is_file($path)) {
            return null;
        }

        $contents = @file_get_contents($path, length: 4097);
        $gate = is_string($contents) && strlen($contents) <= 4096 ? json_decode($contents, true) : null;

        if (! is_array($gate)) {
            return null;
        }

        $peer = (string) $request->server('REMOTE_ADDR');
        $httpHost = strtolower((string) $request->server('HTTP_HOST'));
        $scope = hash('sha256', $contents);

        foreach ($request->headers->keys() as $header) {
            if ($header === 'forwarded' || str_starts_with($header, 'x-forwarded-')) {
                return null;
            }
        }

        if (($gate['mode'] ?? null) === 'local') {
            $loopback = ['127.0.0.1', '::1'];
            $localServer = PHP_SAPI === 'cli-server'
                || (app()->environment('local') && in_array($request->server('SERVER_ADDR'), $loopback, true));

            if (! $localServer || ! in_array($peer, $loopback, true)
                || ! preg_match('/\A(?:localhost|127\.0\.0\.1|\[::1\])(?::[0-9]{1,5})?\z/', $httpHost)) {
                return null;
            }

            return ['local' => true, 'host' => '127.0.0.1', 'port' => 3306, 'scope' => $scope];
        }

        $domain = $gate['host'] ?? null;
        $operator = $gate['operator_ip'] ?? null;
        $dbHost = $gate['database_host'] ?? null;
        $dbPort = $gate['database_port'] ?? null;

        if (($gate['mode'] ?? null) !== 'production'
            || ! is_string($domain) || ! $this->validHostname($domain)
            || ! is_string($operator) || ! filter_var($operator, FILTER_VALIDATE_IP)
            || inet_pton($peer) !== inet_pton($operator)
            || ! in_array($httpHost, [strtolower($domain), strtolower($domain).':443'], true)
            || ! in_array(strtolower((string) $request->server('HTTPS')), ['on', '1'], true)
            || ! is_string($dbHost) || ! $this->validHostname($dbHost)
            || ! is_int($dbPort) || $dbPort < 1 || $dbPort > 65535) {
            return null;
        }

        return ['local' => false, 'host' => strtolower($dbHost), 'port' => $dbPort, 'scope' => $scope];
    }

    private function validHostname(string $host): bool
    {
        return strlen($host) <= 253
            && preg_match('/\A[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?\z/i', $host) === 1
            && filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }
}
