<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant database provider
    |--------------------------------------------------------------------------
    | How a brand-new tenant database is CREATED during vendor provisioning:
    |
    |   'native' — run `CREATE DATABASE` directly. Works where the app's MySQL
    |              user has the CREATE privilege (local XAMPP root, a VPS).
    |   'cpanel' — create it through cPanel's UAPI and grant the app's MySQL user
    |              ALL privileges on it. Use this on shared hosting (e.g. GoDaddy),
    |              where the app's MySQL user CANNOT run `CREATE DATABASE`.
    |
    | Everything after creation (clone schema, copy RBAC, seed admin) is plain
    | SQL run as the app's MySQL user and is identical for both providers.
    */
    'provider' => env('TENANT_DB_PROVIDER', 'native'),

    /*
    |--------------------------------------------------------------------------
    | Tenant database name prefix
    |--------------------------------------------------------------------------
    | Prepended to every tenant database name. On cPanel the account prefix is
    | MANDATORY and looks like 'fx8fdk9i85rt_'. Leave empty for local / VPS.
    | Keep names short — MySQL caps a database name at 64 chars (prefix included).
    */
    'db_prefix' => env('TENANT_DB_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | cPanel UAPI credentials (only used when provider = 'cpanel')
    |--------------------------------------------------------------------------
    | Create an API token in cPanel → Security → Manage API Tokens.
    | From the server itself, https://127.0.0.1:2083 avoids DNS/cert issues.
    */
    'cpanel' => [
        'host'   => env('CPANEL_HOST', 'https://127.0.0.1:2083'),
        'user'   => env('CPANEL_USER'),
        'token'  => env('CPANEL_API_TOKEN'),
        'verify' => env('CPANEL_VERIFY_TLS', false),

        // The MySQL user the app connects as — gets ALL PRIVILEGES on each new
        // tenant DB so the single app user can switch between tenant databases.
        // Defaults to the app's own DB user.
        'grant_user' => env('TENANT_DB_GRANT_USER', env('DB_USERNAME')),
    ],
];
