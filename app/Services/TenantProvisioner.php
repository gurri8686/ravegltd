<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Provisions a brand-new database for a vendor (database-per-tenant model).
 *
 * Given a vendor (a users row in the MAIN db) and a subdomain, it:
 *   1. creates a new database  `<firstname>_<nnnnn>`
 *   2. clones the schema of the main db (CREATE TABLE ... LIKE — structure only)
 *   3. copies the RBAC reference data (roles/permissions) so logins work
 *   4. creates the vendor's admin user inside the new db
 *
 * Pure SQL, so it works on any MySQL/MariaDB where the connection user may
 * CREATE DATABASE (local root, a VPS, or cPanel with the privilege / API).
 * The returned database name is stored on the site so the multidomain layer
 * routes the subdomain to it.
 */
class TenantProvisioner
{
    /**
     * RBAC / reference tables copied (with data) into the new tenant db.
     * NOTE: only role/permission STRUCTURE — never the user-specific
     * model_has_roles / model_has_permissions (those reference the main db's
     * user ids). The new admin user's role is assigned separately.
     */
    private const REFERENCE_TABLES = [
        'roles', 'permissions', 'role_has_permissions',
        'permission_groups', 'permission_modules', 'permission_role',
        'role_group', 'permission_url_models',
    ];

    /**
     * @return string the new database name
     */
    public function provisionForVendor(object $vendor): string
    {
        $main   = DB::connection('mysql')->getDatabaseName();
        $dbName = $this->databaseName($vendor);

        // 1. Create the database ONLY if it doesn't already exist.
        //    'native' -> direct CREATE DATABASE (local root / VPS).
        //    'cpanel' -> create via cPanel UAPI + grant the app's MySQL user,
        //                because on shared hosting (GoDaddy) the app user can't
        //                run CREATE DATABASE. Configured in config/tenant.php.
        if (!$this->databaseExists($dbName)) {
            $this->createDatabase($dbName);
        }

        // 2. clone every table's structure from the main db
        foreach ($this->tablesOf($main) as $table) {
            DB::statement("CREATE TABLE IF NOT EXISTS `{$dbName}`.`{$table}` LIKE `{$main}`.`{$table}`");
        }

        // 3. copy RBAC / reference data so roles & permissions exist
        foreach (self::REFERENCE_TABLES as $table) {
            if ($this->tableExists($main, $table)) {
                DB::statement("INSERT INTO `{$dbName}`.`{$table}` SELECT * FROM `{$main}`.`{$table}`");
            }
        }

        // 4. create the vendor's admin user inside the new db
        $this->seedAdminUser($dbName, $vendor);

        return $dbName;
    }

    /**
     * Create the tenant database using the configured provider:
     *   native: a direct CREATE DATABASE (needs a privileged MySQL user).
     *   cpanel: cPanel UAPI creates it and grants the app's MySQL user ALL on it
     *           (shared hosting, where CREATE DATABASE is blocked for the app user).
     */
    private function createDatabase(string $dbName): void
    {
        if (config('tenant.provider') === 'cpanel') {
            $cpanel = new CpanelMysql();
            $cpanel->createDatabase($dbName);
            $cpanel->grantUser($dbName, (string) config('tenant.cpanel.grant_user'));

            // Pick up the freshly granted privileges before cloning tables into it.
            DB::purge('mysql');
            DB::reconnect('mysql');

            return;
        }

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /** Build a short, STABLE database name: [prefix]<firstname>_<nnnnn>. */
    public function databaseName(object $vendor): string
    {
        $first = $vendor->first_name ?: ($vendor->username ?? 'vendor');
        $slug  = Str::slug($first, '_') ?: 'vendor';

        // A 5-digit suffix that LOOKS random but is DERIVED from the vendor id,
        // so it is stable: databaseName() is called multiple times (create the
        // DB, record it on the site, later find/reuse it) and every call must
        // return the same name. A truly random suffix would differ each call
        // and break provisioning. (id -> crc32 -> 10000..99999)
        $suffix = abs(crc32('tenant|' . $vendor->id)) % 90000 + 10000;

        // Prefix is empty on this server (no db-name prefix enforced); empty locally.
        return config('tenant.db_prefix', '') . strtolower($slug . '_' . $suffix);
    }

    private function tablesOf(string $database): array
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', $database)
            ->where('table_type', 'BASE TABLE')
            ->pluck('table_name')->all();
    }

    private function tableExists(string $database, string $table): bool
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', $database)->where('table_name', $table)->exists();
    }

    /** Does a database with this name already exist on the server? */
    private function databaseExists(string $database): bool
    {
        return DB::table('information_schema.schemata')
            ->where('schema_name', $database)->exists();
    }

    /** Recreate the vendor as an admin user inside their own database. */
    private function seedAdminUser(string $dbName, object $vendor): void
    {
        $c   = $this->tenantConnection($dbName);
        $now = now();

        $userId = $c->table('users')->insertGetId([
            'first_name' => $vendor->first_name ?? 'Admin',
            'last_name'  => $vendor->last_name ?? '',
            'email'      => $vendor->email,
            'username'   => $vendor->username ?: $vendor->email,
            'phone'      => $vendor->phone ?? null,
            'role'       => 'admin',
            // copy the existing password hash so they log in with the same password
            'password'   => $vendor->password,
            'is_active'  => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // assign the spatie 'admin' role in the new db (role row was copied above)
        $adminRoleId = $c->table('roles')->where('name', 'admin')->value('id');
        if ($adminRoleId) {
            $c->table('model_has_roles')->insert([
                'role_id'    => $adminRoleId,
                'model_type' => 'App\\Models\\User',
                'model_id'   => $userId,
            ]);
        }
    }

    /** A runtime connection pointed at the new tenant database. */
    private function tenantConnection(string $dbName)
    {
        config(['database.connections._tenant' => array_merge(
            config('database.connections.mysql'),
            ['database' => $dbName]
        )]);
        DB::purge('_tenant');

        return DB::connection('_tenant');
    }
}
