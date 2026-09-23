# Local Development Setup

How to run FAKT locally on Windows.

## Prerequisites (one-time)

- **PHP 8.3+** — if `php -v` doesn't work in a new terminal, install it:
  ```powershell
  winget install --id PHP.PHP.8.3 -e
  ```
  If winget's download link is stale (404), install manually instead:
  1. Download the NTS zip from https://windows.php.net/downloads/releases/ (e.g. `php-8.3.33-nts-Win32-vs16-x64.zip`)
  2. Extract to `C:\Users\<you>\AppData\Local\Programs\php`
  3. Copy `php.ini-development` to `php.ini` in that folder, then uncomment (`extension=`) these lines: `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `pdo_sqlite`, `sqlite3`, `zip`
  4. Add that folder to your user `PATH` (System Properties → Environment Variables)

- **Composer** — if `composer -v` doesn't work:
  ```powershell
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php composer-setup.php --install-dir="C:\Users\<you>\AppData\Local\Programs\php" --filename=composer.phar
  ```
  Then create `composer.bat` next to it containing: `@echo off` / `php "%~dp0composer.phar" %*`

- **Node.js** (already required for this project) — `node -v` / `npm -v` should both work.

> After changing `PATH`, open a **new** terminal — existing ones keep the old `PATH`.

## First-time project setup

From the project root (`C:\Users\molna\Documents\fakt-app`):

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --force
npm ci
```

This project uses SQLite by default (`database/database.sqlite`) — no separate DB server needed.

## Running the app

Two processes, both from the project root, in separate terminals:

```powershell
# Terminal 1 — Laravel backend
php artisan serve
```

```powershell
# Terminal 2 — Vite (frontend hot-reload)
npm run dev
```

Then open **http://localhost:8000** (not the Vite port — Vite just injects assets into Laravel's views).

## After pulling new changes

```powershell
composer install     # if composer.lock changed
npm ci                # if package-lock.json changed
php artisan migrate --force   # if new migrations were added
```

## Demo login

Seeded accounts (see `database/seeders/DatabaseSeeder.php`) all share the password `Fakt2027!`. Example — President account:

- Email: `elnok@fakt.local`
- Password: `Fakt2027!`

## Troubleshooting

- **`php`/`composer` not recognized**: PATH wasn't updated in the current terminal — open a new one, or re-check the install steps above.
- **Port 8000 already in use**: another `php artisan serve` is likely already running; check with `netstat -ano | findstr :8000` or just reuse it.
- **Blank/500 page**: check `storage/logs/laravel.log`.
