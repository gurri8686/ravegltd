<?php

namespace App\Services;

use RuntimeException;

/**
 * Thin wrapper over cPanel's UAPI (Mysql module), authenticated with a cPanel
 * API token, so the app can CREATE DATABASE and grant privileges on shared
 * hosting (e.g. GoDaddy) where the app's MySQL user has no CREATE privilege.
 *
 * Configured via config/tenant.php (env CPANEL_HOST / CPANEL_USER /
 * CPANEL_API_TOKEN). Uses plain cURL so it needs no extra Composer package.
 */
class CpanelMysql
{
    private string $host;   // e.g. https://127.0.0.1:2083
    private string $user;   // cPanel account username
    private string $token;  // cPanel API token
    private bool $verify;   // verify the cPanel TLS cert (false for self-signed)

    public function __construct()
    {
        $cfg = (array) config('tenant.cpanel');

        $this->host   = rtrim((string) ($cfg['host'] ?? ''), '/');
        $this->user   = (string) ($cfg['user'] ?? '');
        $this->token  = (string) ($cfg['token'] ?? '');
        $this->verify = (bool) ($cfg['verify'] ?? false);

        if ($this->host === '' || $this->user === '' || $this->token === '') {
            throw new RuntimeException(
                'cPanel API is not configured — set CPANEL_HOST, CPANEL_USER and CPANEL_API_TOKEN.'
            );
        }
    }

    /**
     * Create a database. The name must already include the cPanel account
     * prefix (e.g. "fx8fdk9i85rt_acme_42_ravegltd").
     */
    public function createDatabase(string $name): void
    {
        $this->call('Mysql', 'create_database', ['name' => $name]);
    }

    /** Grant a cPanel MySQL user privileges on a database. */
    public function grantUser(string $database, string $user, string $privileges = 'ALL PRIVILEGES'): void
    {
        $this->call('Mysql', 'set_privileges_on_database', [
            'user'       => $user,
            'database'   => $database,
            'privileges' => $privileges,
        ]);
    }

    /**
     * Execute a UAPI call and return its `data`. Throws on a transport error or
     * a cPanel-level failure (status != 1).
     */
    private function call(string $module, string $function, array $params): array
    {
        $url = "{$this->host}/execute/{$module}/{$function}?" . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: cpanel {$this->user}:{$this->token}"],
            CURLOPT_SSL_VERIFYPEER => $this->verify,
            CURLOPT_SSL_VERIFYHOST => $this->verify ? 2 : 0,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new RuntimeException("cPanel API request failed ({$module}::{$function}): {$error}");
        }
        if ($code < 200 || $code >= 300) {
            throw new RuntimeException("cPanel API HTTP {$code} for {$module}::{$function}: " . substr((string) $body, 0, 300));
        }

        $json = json_decode((string) $body, true);
        if (!is_array($json)) {
            throw new RuntimeException("cPanel API returned a non-JSON response for {$module}::{$function}.");
        }

        // UAPI envelope: { "status": 1, "errors": null|[...], "data": ... }
        if ((int) ($json['status'] ?? 0) !== 1) {
            $errors = $json['errors'] ?? ['Unknown cPanel error'];
            throw new RuntimeException('cPanel API error: ' . implode('; ', (array) $errors));
        }

        return (array) ($json['data'] ?? []);
    }
}
