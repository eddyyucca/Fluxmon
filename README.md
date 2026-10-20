# Fluxmon

Open-source MikroTik hotspot and voucher manager, built with Laravel.

Fluxmon is a ground-up rewrite inspired by [Mikhmon](https://github.com/laksa19/mikhmonv3) by Laksamadi Guko.

## Status

Early development. Implemented so far:

- Admin login (Laravel Breeze)
- Multi-router management with encrypted credentials and connection test
- Live dashboard per router (CPU, memory, hotspot active/users)

Planned: hotspot users and profiles, voucher generation and printing, sales reports, resellers, Telegram notifications.

## Requirements

- PHP 8.2+ with `sockets`, `openssl`, `mbstring`, `pdo_sqlite`
- Composer, Node.js 20+
- MikroTik RouterOS with API enabled (`/ip service enable api`, port 8728)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build
php artisan serve
```

Register the first admin at `/register`, then add a router under **Routers**.

## Tests

```bash
php artisan test
```

## License

GPL-2.0-or-later. This project derives from Mikhmon (Copyright (C) 2018 Laksamadi Guko, GPL v2); see [LICENSE](LICENSE).
