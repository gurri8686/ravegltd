# R & A Veg Ltd (ravegltd)

Laravel **multi-domain** business app (sales / purchases / inventory) with a
per-vendor **database-per-tenant** model. Each domain/subdomain is mapped to its
own database, and the page UI (lists, tables, cards) is a JavaScript bundle
served from `public/cdn`.

> **Read this first — it prevents "my local looks different from live".**
> The visible UI does **not** come only from PHP/Blade. It is built from extra
> pieces (a JS/CSS bundle + per-domain `.env` + the local web-server setup).
> The PHP code is identical everywhere (so *functionality* is the same), but if
> any of those extra pieces are missing or stale on a machine, the **UI** looks
> different. The steps below make every machine match.

---

## Requirements (match these versions)

| Tool | Version |
|------|---------|
| PHP | 8.0 – 8.2 (tested on **8.2**) |
| Composer | latest |
| MySQL / MariaDB | XAMPP default is fine |
| Apache | XAMPP / WAMP / Laragon — app is served via a **vhost**, *not* `php artisan serve` (multi-domain needs a real hostname) |
| Node.js + npm | **16+** — *only* needed if you change frontend assets (see [Frontend / Assets](#frontend--assets)) |

> Tip: pin Node to one version across the team (add a `.nvmrc`). Different
> Node/npm versions can produce slightly different CSS/JS builds.

---

## How the UI loads (important)

- The page content (supplier/customer lists, tables, cards) is rendered by a
  **JS bundle** in **`public/cdn`** (`js/manifest.js` + `js/vendor.js` +
  `js/app.js` + `css/app.css`). The app loads it from the **`CDN_DOMAIN`** env
  value — locally `http://ravegltd.localhost/cdn` (i.e. this same site's
  `public/cdn` folder).
- **`public/cdn`, `public/css/app.css` and `public/js/app.js` are committed to
  git.** So a fresh clone already has the exact same UI as production — you do
  **not** need to run `npm` just to run the app.
- ⚠️ **Therefore:** if you change any CSS / JS / React source, you **must rebuild
  and commit the built files**, otherwise other machines (and new clones) will
  show **old UI**. See [Frontend / Assets](#frontend--assets).

---

## Setup (step by step)

### 1. Clone & PHP dependencies
```bash
git clone https://github.com/gurri8686/ravegltd.git
cd ravegltd
composer install
```

### 2. Environment (per-domain `.env`)
This app uses [gecche/laravel-multidomain](https://github.com/gecche/laravel-multidomain):
each host has its **own** env file inside the **`envs/`** folder, named
`.env.<host>` (e.g. `envs/.env.ravegltd.localhost`).

Create `envs/.env.ravegltd.localhost` with at least:
```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=                 # generate it (next command)
APP_DEBUG=true
APP_URL=http://ravegltd.localhost
CDN_DOMAIN=http://ravegltd.localhost/cdn   # <- UI bundle loads from here; must be correct

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ts.ravegltd.com   # this domain's tenant DB
DB_USERNAME=root
DB_PASSWORD=
ORG_DB_DATABASE=ts_organizations   # control-plane DB (holds the `sites` table)
```
Generate the app key for this domain:
```bash
php artisan key:generate --domain=ravegltd.localhost
```
> Never commit real `.env` files / passwords. Copy values from the team.

### 3. Hosts file
Add to `C:\Windows\System32\drivers\etc\hosts` (needs admin):
```
127.0.0.1   ravegltd.localhost
```
*(Modern browsers also auto-resolve `*.ravegltd.localhost` to localhost, used for
vendor subdomains.)*

### 4. Apache vhost
Point the host to `public/` and allow vendor subdomains:
```apache
<VirtualHost *:80>
    ServerName ravegltd.localhost
    ServerAlias *.ravegltd.localhost
    DocumentRoot "d:/path/to/ravegltd/public"
    <Directory "d:/path/to/ravegltd/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
The CDN bundle is served from this same site under `/cdn` (it's `public/cdn`), so
**no separate CDN vhost is needed.** Restart Apache after editing.

> If you run multiple sites and one vhost uses a broad `ServerAlias *.localhost`,
> keep the **specific** hosts listed **before** it, or the wildcard will steal
> them.

### 5. Database
1. Create the two databases: the tenant DB (e.g. `ts.ravegltd.com`) and
   `ts_organizations`.
2. Load data: import the provided SQL dump, **or** build from scratch:
   ```bash
   php artisan migrate --domain=ravegltd.localhost
   php artisan db:seed --domain=ravegltd.localhost   # superadmin + roles
   ```
3. **Register the domain** in the control-plane `sites` table (this is required —
   `DomainsMiddleware` returns **404 on every page** if the host isn't there):

   In `ts_organizations.sites`, add a row:
   `subdomain = ravegltd.localhost`, `domain = ravegltd.localhost`,
   `database = ts.ravegltd.com`, `status = 1` (+ any `api_key`/`api_secret`).

### 6. Permissions
```bash
php artisan permissions:update --domain=ravegltd.localhost
```

### 7. Run
Open **http://ravegltd.localhost** — and the superadmin panel at
**http://ravegltd.localhost/admin/login**.

---

## Frontend / Assets

You only touch this when you **change** CSS / JS:
```bash
npm install        # first time (use `npm ci` for an exact, locked install)
npm run dev        # quick build  (or `npm run prod` for production/minified)
```
This rebuilds `public/css/app.css` and `public/js/app.js`.

**Rule (prevents "old CSS on other machines"):** after any frontend change,
**rebuild and commit the built files together with your source change.** The
shared UI components/bundle in `public/cdn` come from the separate **ts-cdn**
project — rebuild there and copy its output into `public/cdn`, then commit.

---

## Multi-domain commands

- Add a domain: `php artisan domain:add site1.com`
- Remove a domain: `php artisan domain:remove site1.com`
- Update a value across all env files:
  `php artisan domain:update_env --domain_values='{"KEY":"VALUE"}'`
- Each site has its own `envs/.env.<host>` file with its own values.

---

## Routes / permissions

No need to insert permissions into the `permissions` table by hand:
```bash
php artisan permissions:update --domain=ravegltd.localhost
```

---

## Troubleshooting (common local issues)

| Symptom | Cause / Fix |
|---------|-------------|
| **Every page 404s** (even though Apache/PHP are fine) | The host isn't in `ts_organizations.sites`. Add the row (Setup step 5.3). |
| **Pages are blank except the Dashboard** | The CDN UI bundle isn't loading. Check `public/cdn` exists, `CDN_DOMAIN` is correct, and the vhost serves `/cdn`. In the browser **Network/Console** tab, look for failed `cdn/js/*` or `app.css`. |
| **Cards/tables show old styling** | Built CSS is stale (built files are committed but weren't rebuilt). Rebuild + commit (see Frontend / Assets), then hard-refresh (`Ctrl+Shift+R`). |
| **Wrong data on a subdomain** | The host → database mapping comes from the `sites` table (`DomainsMiddleware`). Check the row's `database` value. |
| **Changed `.env` / mail not taking effect** | Multi-domain caches env; restart Apache fully after editing env files. |

---

## Notes

- App is served via Apache vhost, **not** `php artisan serve`.
- Per-domain DB switching happens in `app/Http/Middleware/DomainsMiddleware.php`
  (host → `sites` row → database).
- Superadmin panel: `/admin/login` (role-gated).
