# Fluxmon

Open-source MikroTik hotspot and voucher manager, built with Laravel.

Fluxmon is a ground-up rewrite inspired by [Mikhmon](https://github.com/laksa19/mikhmonv3) by Laksamadi Guko.

## Features

- **Multi-router**: manage any number of MikroTik routers; credentials are stored encrypted.
- **Live dashboard**: CPU, memory, online users, real-time traffic chart per interface, revenue trend.
- **Hotspot users**: search/filter, add with time and data limits, enable/disable/reset/delete in bulk.
- **Profiles**: rate limit, shared users, price, validity and expiry action (remove or disable).
- **Vouchers**: batch generation (code-only or user+password, prefix, character set), printable sheets with QR codes, CSV export.
- **Reports**: revenue per day, by profile, by router; date ranges; CSV export. A voucher counts as sold at its first login.
- **Active sessions**: live list with one-click disconnect.
- **Telegram**: daily sales report and router offline/online alerts.
- **Admin accounts**, audit log, dark mode, English and Bahasa Indonesia.

Everything Fluxmon owns (accounts, settings, sales history) lives in one SQLite file: `database/database.sqlite`.

## Requirements

- PHP 8.2+ with `sockets`, `openssl`, `mbstring`, `pdo_sqlite`
- Composer, Node.js 20+
- MikroTik RouterOS with the API enabled: `/ip service enable api` (port 8728)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build
php artisan serve
```

Open http://127.0.0.1:8000. The first visit creates the admin account; after that registration closes and admins are added from **Settings**.

## Background jobs

Fluxmon detects used and expired vouchers by syncing with each router every minute. Run the scheduler alongside the web server:

```bash
php artisan schedule:work
```

On a server, use cron instead: `* * * * * php /path/to/artisan schedule:run`.
Without it, sales and expiry update only when you press **Sync**.

Manual commands: `php artisan fluxmon:sync`, `php artisan fluxmon:daily-report --force`.

## How vouchers and sales work

Voucher codes are created on the router as hotspot users, and Fluxmon records them locally with the price frozen at generation time. On the first login (detected by sync) the voucher is marked used, counted as revenue, and its validity clock starts. When validity ends, Fluxmon disconnects the user and removes or disables it. No on-login scripts are installed on the router.

## Development

```bash
php artisan test        # uses an in-memory fake router, no MikroTik needed
npm run dev             # Vite with hot reload
```

All router access goes through `App\Services\Mikrotik\Gateway`; tests swap in `Tests\Support\FakeGateway`.

## License

GPL-2.0-or-later. This project derives from Mikhmon (Copyright (C) 2018 Laksamadi Guko, GPL v2); see [LICENSE](LICENSE).
