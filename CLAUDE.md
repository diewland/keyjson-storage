# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

keyjson-storage is a minimal PHP + MySQL key-value store: each `name` holds one JSON document, read and written over HTTP. Plain PHP with PDO — no framework, no Composer, no build step, no tests.

## Layout

- `db/` — DB scripts (phpMyAdmin dump creating database `keyjson`, table `storage`).
- `docs/` — wwwroot (deployed as the web root).
  - `index.php` — the entire API (single endpoint).
  - `config.php` — `$config` array of allowed names and their write secrets.
  - `db.php` — PDO connection (`$conn`); credentials are hardcoded placeholders.
  - `docs/index.html` — static API documentation, served at `/docs/`.

## Running locally

```sh
mysql -u root -p < db/20250804_init.sql   # creates table; create the `keyjson` database first if needed
php -S localhost:8000 -t docs             # API at http://localhost:8000/, docs at /docs/
```

Update credentials in `docs/db.php` to match the local MySQL.

## Architecture

`index.php` includes `config.php` and `db.php`, then branches on `REQUEST_METHOD`:

- **GET `?name=`** — returns `data` (decoded JSON). No secret required: reads are public.
- **POST JSON `{name, secret, value}`** — validates secret against `$config[name]['secret']`, then INSERTs or fully replaces `value` (sets `updated_at`).

Key points that span files:

- A name must exist in `docs/config.php` to be readable or writable; the DB row is created on first POST. A registered name with no row yields `data not found`.
- All responses are HTTP 200 with envelope `{success, message, data?}`; errors are signalled only via `message` (`name not found`, `data not found`, `invalid input`, `invalid secret`).
- CORS is `*` for GET/POST/OPTIONS.
- Timezone is set to `Asia/Bangkok` in `db.php`.

When changing API behavior or error messages, keep `README.md` (jQuery samples) and `docs/docs/index.html` in sync.
