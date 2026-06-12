# ravegltd — project notes

R & A Veg Ltd inventory/accounting app (Laravel backend + React UI).
Production: **https://ravegltd.kodionsoftwares.com**

## Frontend / React UI build  ← read this before touching the UI

The app UI is a **React bundle** built from this repo's own source and served as a "CDN" bundle.
(The source was moved in from the separate `ts-cdn` repo so ravegltd is self-contained. `ts-cdn`
is still the shared source for the **ts-organizations** app, so UI changes made here do **not**
propagate there, and vice-versa.)

- **Source:** `src/resources/js/` — entry `src/resources/js/app.js`; React screens in `components/`
  (`*App.js`), plus `elements/`, `hooks/`; styles in `src/resources/css/app.css`.
- **Build:** laravel-mix (`webpack.mix.js`). Output → **`public/cdn/js/`** (`app.js`, `vendor.js`,
  `manifest.js`) and **`public/cdn/css/app.css`**. `postinstall.js` copies TinyMCE → `public/cdn/tinymce`.

  ```
  npm install      # first time (installs the tree pinned by the committed package-lock.json)
  npm run watch    # rebuild on save while developing
  npm run prod     # production (minified) build → public/cdn
  ```

- **How the app loads it:** `resources/views/layouts/main.blade.php` references
  `{{ env('CDN_DOMAIN') }}/js/{manifest,vendor,app}.js` and `/css/app.css`.
  - local: `CDN_DOMAIN=http://ravegltd.localhost/cdn`  (in `envs/.env.ravegltd.localhost`)
  - prod:  `CDN_DOMAIN=https://ravegltd.kodionsoftwares.com/cdn`

- **⚠ Do NOT `npm update` webpack.** The lockfile pins **webpack 5.103.0**. Webpack 5.107+ removed
  `webpack/lib/SizeFormatHelpers`, which laravel-mix 6 requires — the build then crashes with
  `Cannot find module 'webpack/lib/SizeFormatHelpers'`. Keep `package-lock.json` committed.

- The legacy **Vue** scaffold (`resources/js/` → `public/js/app.js`) only powers the auth/login
  pages; its build line is left commented in `webpack.mix.js`. Don't confuse the two `app.js` files.

## Deploy (GoDaddy / cPanel) — the server does NOT build

`.cpanel.yml` is a cPanel **git deploy**: it just copies `app/ config/ database/ public/ resources/
routes/` (+ artisan/composer files) to `public_html/ravegltd2/`. There is **no npm/build step on the
server**, and `node_modules/` and `envs/` are not copied. Therefore:

1. `npm run prod` locally → 2. **commit the regenerated `public/cdn`** (it is git-tracked) →
3. push → cPanel deploys. The server serves the committed bundle verbatim.
**If you forget to build + commit `public/cdn`, prod shows no UI change.**

The production env (DB creds, `CDN_DOMAIN`, mail, …) lives only on the server in `envs/` (not in git).

## Env & running locally

- Env files live in **`envs/`** (`envs/.env` default + `envs/.env.ravegltd.localhost` per-domain),
  NOT a root `.env`. Mail/env changes need a **full Apache restart** — XAMPP workers cache env via
  `putenv()` and won't pick up edits until the process is killed.
- Run via **XAMPP Apache vhost `ravegltd.localhost`** (not `php artisan serve`). The host must exist as
  a row in `ts_organizations.sites` (DomainsMiddleware 404s otherwise) plus a Windows hosts entry.
  Per-domain DB = `ts.ravegltd.com`; control-plane DB = `ts_organizations`.
