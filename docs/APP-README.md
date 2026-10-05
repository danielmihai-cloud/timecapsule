# TimeCapsule — Laravel version

TimeCapsule lets you write a message to the future. You write a message, can attach one photo
or PDF, and pick the date and time it opens. The capsule stays **sealed** until then; after it opens, the
message and the attachment become visible. Opened capsules can also be shown on a **public wall**.

This folder is the **Laravel 12** version of the app (Blade templates, Eloquent, no npm/Vite).
The CSS is a plain file in `public/css/app.css`, and a small script in `public/js/app.js` adds a
date-time picker ([flatpickr](https://flatpickr.js.org/), MIT license, vendored in
`public/vendor/flatpickr`) and shows times in the visitor's time zone.

- [Requirements](#requirements)
- [Run without Docker](#run-without-docker)
- [Run with Docker](#run-with-docker)
- [Configuration](#configuration)
- [Open time and time zones](#open-time-and-time-zones)
- [How capsules open](#how-capsules-open)
- [Deploying to an Ubuntu server](#deploying-to-an-ubuntu-server)
- [Where things are](#where-things-are)

## Requirements

- **With Docker:** Docker Desktop (or Docker Engine) with Docker Compose v2. Nothing else.
- **Without Docker:** PHP 8.2+ ([XAMPP](https://www.apachefriends.org) works) with the extensions
  `pdo_pgsql`, `mbstring`, `fileinfo`, `openssl`, `curl`, `zip`, [Composer](https://getcomposer.org/download/) and a
  PostgreSQL database you can connect to.

Windows: see [Setting up a Windows computer](../README.md#setting-up-a-windows-computer).

## Run without Docker

1. Install the Composer packages and create the settings file:

   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

2. Open `.env` and enter your database: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`,
   `DB_PASSWORD`.

3. Create the tables and start the app:

   ```bash
   php artisan migrate --seed      # creates the tables and the built-in Guest user
   php artisan serve --port=8080
   ```

4. Open http://localhost:8080 and create an account.

Use an empty database: the other TimeCapsule projects create tables with the same names.

## Run with Docker

Start Docker Desktop first and wait until it is running.

```bash
docker compose up --build
```

Open http://localhost:8080. The container waits for the database, runs the migrations and the
seeder, and starts Apache. **Adminer** (a web UI for the database) runs at http://localhost:8081:
System `PostgreSQL`, Server `db`, Username `timecapsule`, Password `secret`, Database `timecapsule`.

```bash
docker compose logs -f app       # application logs
docker compose down -v           # stop and delete all data
```

Guest mode (no login) and other host ports (`APP_HOST_PORT`, default `8080`; `DB_HOST_PORT`,
default `5432`; `ADMINER_HOST_PORT`, default `8081`):

```bash
AUTH_ENABLED=false docker compose up -d            # macOS / Linux
$env:AUTH_ENABLED="false"; docker compose up -d    # Windows PowerShell
APP_HOST_PORT=9000 docker compose up               # macOS / Linux
$env:APP_HOST_PORT="9000"; docker compose up       # Windows PowerShell
```

In PowerShell the variable stays set until you close the window.

If `APP_KEY` is not set, the container generates a key on first start and stores it in
`storage/app/.app_key` inside the container. The key survives a restart, but not
`docker compose down` (the container is recreated and everybody is logged out). To use a fixed key,
create `.env` and run `php artisan key:generate` (step 2 above): Docker Compose reads the `.env` file
in this folder (also `AUTH_ENABLED` and `MAIL_DELAY_SECONDS`).

Uploaded files are kept in the `uploads` volume
(`/var/www/html/storage/app/uploads` inside the container), the database in the `dbdata` volume.

## Configuration

Everything is configured with environment variables in `.env` (see `.env.example`).
Laravel uses its own names for some of them:

| Variable | Default | Meaning |
|---|---|---|
| `APP_KEY` | – | Encryption key for sessions. Create it with `php artisan key:generate`. |
| `APP_URL` | `http://localhost:8080` | Public address of the app |
| `APP_DEBUG` | `false` | `true` shows error details. Never on a public server. |
| `DB_HOST` | `127.0.0.1` | PostgreSQL host |
| `DB_PORT` | `5432` | PostgreSQL port |
| `DB_DATABASE` | `timecapsule` | Database name (`DB_NAME` in the other versions) |
| `DB_USERNAME` | `timecapsule` | Database user (`DB_USER` in the other versions) |
| `DB_PASSWORD` | `secret` | Database password |
| `UPLOAD_DIR` | *(empty)* = `storage/app/uploads` | Folder for uploaded files (absolute, e.g. `C:\uploads` or `/var/uploads`, or relative to the project) |
| `MAX_UPLOAD_MB` | `5` | Maximum attachment size in MB |
| `MAIL_DELAY_SECONDS` | `3` | How long the simulated "email sending" takes when a capsule is created |
| `AUTH_ENABLED` | `true` | `false` turns off login: everybody uses the app as **Guest** |
| `SESSION_DRIVER` | `file` | Sessions are files in `storage/framework/sessions` |
| `LOG_CHANNEL` | `single` | `single` = `storage/logs/laravel.log`, `stderr` = console (Docker) |

The TimeCapsule settings are read in `config/timecapsule.php`.
After changing `.env` on a server where you ran `php artisan config:cache`, run it again.

PHP's own upload limits must be a bit larger than `MAX_UPLOAD_MB`:
`upload_max_filesize = 6M` and `post_max_size = 8M` in `php.ini`.

## Open time and time zones

The "Open at" field takes a date and a time in **your** time zone. The browser sends your local time
(`open_at`) and, via `public/js/app.js`, the same moment in UTC (`open_at_utc`); the app stores UTC
(`capsules.open_at`) and shows times in the visitor's time zone (pages render
`<time data-local datetime="…Z">… UTC</time>`, and the script rewrites the text).
Without JavaScript the browser's own date-time field is used, only `open_at` is sent, and it is
treated as UTC. Seconds are dropped, and the open time must be in the future.
The countdown shows minutes in the last hour, hours in the last day, and days before that.

## How capsules open

When a capsule's open time has passed, the next page request opens it and adds an "is now open"
notification. No cron job or background worker is needed.

This happens in the middleware `app/Http/Middleware/OpenDueCapsules.php` (every page except
`GET /health`), which calls `App\Services\CapsuleOpener::openDue()`. It runs one `UPDATE … RETURNING`
statement, so two requests at the same time never open the same capsule twice.

Quick test: `UPDATE capsules SET open_at = NOW() - interval '1 minute'`, then reload the page.

## Deploying to an Ubuntu server

Tested with Ubuntu 24.04 LTS (PHP 8.3 is the default version there). Run as a user with `sudo`.
These commands run on the Linux server (connect to it first, e.g. `ssh ubuntu@<server-ip>`).

1. **Install the packages**

   ```bash
   sudo apt update
   sudo apt install -y nginx postgresql git unzip \
       php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip
   # Composer
   curl -sS https://getcomposer.org/installer | php
   sudo mv composer.phar /usr/local/bin/composer
   ```

2. **Create the database**

   ```bash
   sudo -u postgres psql -c "CREATE USER timecapsule WITH PASSWORD 'choose-a-password';"
   sudo -u postgres psql -c "CREATE DATABASE timecapsule OWNER timecapsule;"
   ```

   To reach this database from another machine, set `listen_addresses = '*'` in
   `/etc/postgresql/16/main/postgresql.conf`, allow the client in `pg_hba.conf`
   and restart PostgreSQL (`sudo systemctl restart postgresql`).

3. **Copy the code and install the dependencies**

   ```bash
   sudo mkdir -p /var/www/timecapsule
   sudo chown $USER:www-data /var/www/timecapsule
   # copy this folder there, e.g. with git clone or scp, then:
   cd /var/www/timecapsule
   composer install --no-dev --optimize-autoloader
   ```

4. **Configure**

   ```bash
   cp .env.example .env
   nano .env          # APP_URL, DB_PASSWORD, APP_ENV=production, APP_DEBUG=false
   php artisan key:generate
   php artisan migrate --seed --force
   php artisan config:cache
   ```

5. **File permissions** — PHP-FPM runs as `www-data` and must write sessions, logs and uploads:

   ```bash
   sudo chown -R $USER:www-data storage bootstrap/cache
   sudo chmod -R ug+rwX storage bootstrap/cache
   ```

6. **PHP upload limits** — in `/etc/php/8.3/fpm/php.ini` set

   ```ini
   upload_max_filesize = 6M
   post_max_size = 8M
   ```

   then `sudo systemctl restart php8.3-fpm`.

7. **nginx** — use the sample site in [`deploy/nginx.conf`](deploy/nginx.conf):

   ```bash
   sudo cp deploy/nginx.conf /etc/nginx/sites-available/timecapsule
   sudo ln -s /etc/nginx/sites-available/timecapsule /etc/nginx/sites-enabled/
   sudo rm -f /etc/nginx/sites-enabled/default
   sudo nginx -t && sudo systemctl reload nginx
   ```

8. Open `http://<server address>/` in a browser. `http://<server address>/health` should return
   `{"status":"ok","db":"ok","hostname":"..."}`.

**Updating the app later:** copy the new code, then run
`composer install --no-dev --optimize-autoloader`, `php artisan migrate --force` and
`php artisan config:cache`.

## Where things are

| What | File |
|---|---|
| Routes | `routes/web.php` (`/health` is in `bootstrap/app.php`) |
| Login / registration | `app/Http/Controllers/AuthController.php` |
| `AUTH_ENABLED` switch (guest mode) | `app/Http/Middleware/CurrentUser.php` |
| Capsule pages, upload, download, delete | `app/Http/Controllers/CapsuleController.php` |
| Form validation | `app/Http/Requests/StoreCapsuleRequest.php` |
| Saving and reading uploaded files | `app/Services/CapsuleStorage.php` |
| Simulated email (notifications table) | `app/Services/Mailer.php` |
| Opening due capsules | `app/Http/Middleware/OpenDueCapsules.php`, `app/Services/CapsuleOpener.php` |
| Health check | `app/Http/Controllers/HealthController.php` |
| Settings | `config/timecapsule.php`, `.env` |
| Database tables | `database/migrations/`, Guest user: `database/seeders/DatabaseSeeder.php` |
| Templates | `resources/views/` (layout: `layouts/app.blade.php`, times: `components/local-time.blade.php`) |
| CSS, browser script, date-time picker | `public/css/app.css`, `public/js/app.js`, `public/vendor/flatpickr/` |
| Docker | `Dockerfile`, `docker-compose.yml`, `docker/` |
