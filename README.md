# Fluxmon

**Open-source web management for MikroTik routers: monitoring, alerts, config backups and hotspot voucher sales, across as many routers as you run.**

Fluxmon is developed by [PT Fluxa Tritama Indonesia](https://fluxa.co.id) (*Every Flow Builds The Future*). It started as a rewrite of [Mikhmon](https://github.com/laksa19/mikhmonv3) and is growing into a general MikroTik management panel. It is built with Laravel, stores everything in a single SQLite file, and runs on a PC, a Raspberry Pi or a small VPS.

> **Status: early development.** The test suite runs against an in-memory fake router; behaviour on real RouterOS 6 and 7 devices is still being verified. Bug reports with your RouterOS version and board are very welcome. See [Contributing](#contributing).

---

## Features

**Monitoring**
- Live dashboard per router: CPU, memory, uptime, online users, real-time traffic chart per interface
- Historical charts (1 hour to 30 days): CPU, memory, temperature, online users, traffic per interface
- Reachability checks: ping run *by the router* (is the ISP gateway up?) or TCP port checks from Fluxmon
- Alerts for router offline, failed checks, and threshold rules (e.g. CPU above 90% for 5 minutes), sent to Telegram, a webhook and/or email
- Router log viewer with topic and text filters
- Automatic detection of RouterOS version, board and installed packages (WireGuard, new wifi package, LTE, ...)

**Maintenance**
- Nightly configuration backups over SSH, with change history, diff between versions and `.rsc` download
- SSH host key pinning (trust on first use) so backups cannot be silently intercepted
- Sensitive values stay on the router; RouterOS 7 omits them from exports

**Hotspot and vouchers**
- Hotspot users and profiles: rate limit, shared users, price, validity, expiry action
- Batch voucher generation (code-only or user + password, prefix, character set, time/data limits)
- Printable voucher sheets with QR codes, CSV export
- Sales reports by day, profile and router, with CSV export. A voucher counts as sold at its first login
- Active sessions with one-click disconnect
- No on-login scripts installed on your routers

**Platform**
- Built-in user guide (**Documentation** in the sidebar), in English and Bahasa Indonesia, written for the people who run the hotspot, not only for developers
- Multiple routers in one dashboard; credentials stored encrypted
- Roles (admin, technician, cashier, viewer) and per-router access limits
- Audit log of every change, with the admin who made it
- Two languages, English and Bahasa Indonesia, switchable by each person with the **EN | ID** button (also on the sign-in page): interface, user guide, dates, number formats and validation messages. Background alerts and the daily report use an admin-chosen default language
- Dark mode
- Daily sales report on Telegram

## Requirements

| | |
|---|---|
| PHP | 8.2 or newer with `sockets`, `openssl`, `mbstring`, `pdo_sqlite`, `curl`, `zip` |
| Tools | [Composer](https://getcomposer.org), Node.js 20+ (to build the front-end) |
| Router | MikroTik RouterOS 6.4x or 7.x with the **API** service enabled; the **SSH** service for backups |

## Installation

```bash
git clone https://github.com/<you>/fluxmon.git
cd fluxmon

composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

npm install
npm run build

php artisan serve
```

Open <http://127.0.0.1:8000>. The first visit asks you to create the admin account; after that, registration closes and admins add teammates under **Settings**.

On Windows with XAMPP, enable `extension=sockets` in `php.ini` first.

### Updating

```bash
git pull
composer install
php artisan migrate        # never `migrate:fresh`: that erases your data
npm install && npm run build
```

### Run the scheduler

Fluxmon collects metrics, runs checks, evaluates alerts and detects used/expired vouchers **once a minute**. Without the scheduler the app still works, but charts stay empty and sales only update when you press **Sync**.

```bash
php artisan schedule:work          # development / a terminal you keep open
```

For production, run it from cron instead:

```cron
* * * * * cd /path/to/fluxmon && php artisan schedule:run >> /dev/null 2>&1
```

On Windows use Task Scheduler to run `php artisan schedule:run` every minute.

| Command | When | What it does |
|---|---|---|
| `fluxmon:sync` | every minute | vouchers, metrics, checks, alerts, online/offline state |
| `fluxmon:rollup` | hourly | folds minute samples into hourly averages and prunes old data |
| `fluxmon:backup` | daily 03:00 | saves each router's configuration if it changed |
| `fluxmon:daily-report` | daily 23:55 | sends the day's sales to Telegram |

## Connecting a router

1. On the router, enable the services Fluxmon uses:
   ```
   /ip service enable api
   /ip service enable ssh
   ```
2. Create a dedicated account instead of using `admin`:
   ```
   /user group add name=fluxmon policy=read,write,api,ssh,test
   /user add name=fluxmon group=fluxmon password=<strong password>
   ```
   Read-only monitoring works with `read,api,test`. Adjust the policies to what you need and test them; RouterOS permission requirements vary slightly between versions.
3. In Fluxmon: **Routers → Add router**, enter the address and the account, then press **Test**.
4. Restrict the API and SSH services to your Fluxmon server's address (`/ip service set api address=...`) and, where you can, use API-SSL.

## Roles

| Role | Can do |
|---|---|
| **admin** | everything: routers, settings, accounts, audit log |
| **technician** | monitoring, hotspot, profiles, vouchers, backups, alerts, audit log |
| **cashier** | sell vouchers, manage hotspot users, view reports |
| **viewer** | read-only dashboards, users and reports |

Any non-admin account can also be limited to specific routers. Permissions are defined in [`config/fluxmon.php`](config/fluxmon.php).

## Configuration

Most settings live in the web UI. A few go in `.env`:

| Variable | Default | Meaning |
|---|---|---|
| `APP_TIMEZONE` | `Asia/Jakarta` | timezone for reports and schedules |
| `FLUXMON_METRICS_RAW_DAYS` | `7` | keep minute-level samples this long |
| `FLUXMON_METRICS_ROLLUP_DAYS` | `180` | keep hourly averages this long |
| `FLUXMON_BACKUPS_KEEP` | `30` | configuration backups kept per router |
| `MAIL_*` | log driver | needed only for email alerts |

Data lives in `database/database.sqlite`. Back that file up. SQLite is plenty for dozens of routers; for larger installs set `DB_CONNECTION=mysql` (or `pgsql`) in `.env`.

## Security notes

- Fluxmon holds credentials that can reconfigure your network. Run it behind HTTPS and do not expose it to the internet without a reverse proxy and a firewall rule limiting who can reach it.
- Set `APP_DEBUG=false` in production.
- Router passwords and the Telegram token are encrypted with `APP_KEY`. Keep `.env` private; if you lose the key, re-enter the router passwords.
- Found a vulnerability? Please open a private security advisory on GitHub instead of a public issue.

## Architecture

```
app/Services/Mikrotik/    Gateway interface + RouterOS API implementation (the only code that talks to routers)
app/Services/Monitoring/  metric collection, rollups, series queries, checks
app/Services/Alerting/    alert rules/events and notification channels
app/Services/Config/      SSH config export, backups, diff
app/Services/             vouchers, sync, reports, capability detection
```

Controllers and services depend on the `Gateway` interface, never on the RouterOS library directly. Tests substitute `Tests\Support\FakeGateway`, so the whole suite runs without a router.

## Development

```bash
php artisan test        # no MikroTik needed
npm run dev             # Vite with hot reload
```

Adding a feature that talks to the router: add the method to `Gateway`, implement it in `RouterOsGateway` and `FakeGateway`, then test against the fake.

## Roadmap

- [x] Multi-router dashboard, hotspot users/profiles, vouchers, sales reports
- [x] Historical monitoring, checks, alerts, logs
- [x] Configuration backups with diff, roles and per-router access
- [ ] Network configuration: interfaces, IP addresses, routes, DNS, DHCP
- [ ] Firewall and NAT with templates and a rule checker
- [ ] Queues and bandwidth control (simple queues, PCQ, FUP)
- [ ] PPPoE server and VPN (WireGuard, L2TP)
- [ ] Wireless and CAPsMAN
- [ ] Router groups, configuration templates and drift detection
- [ ] Optional ISP billing module
- [ ] 2FA, REST API and Docker image

Safe-apply (automatic rollback if a change cuts off access) will land together with the first modules that change network configuration.

## Contributing

Issues and pull requests are welcome.

1. Fork, create a branch, make your change.
2. Add or update tests; `php artisan test` must pass.
3. Keep user-facing strings in `__('...')` and add the Indonesian translation to `lang/id.json`. Framework messages (validation, sign-in, pagination) live in `lang/id/*.php`. A test fails if a string in the app has no Indonesian translation or loses a `:placeholder`. To add another language, add it to `SetLocale::SUPPORTED`, create `lang/<code>.json` and `lang/<code>/`, and the `resources/views/docs/<code>/` pages (English is used for any missing page).
4. The user guide lives in `resources/views/docs/{en,id}/`. When you change how a feature works, update its page in both languages (tests check that every page exists in both, and that titles are translated). In the Indonesian pages take screen labels from `__('...')` so the guide matches the interface.
5. When reporting a bug, include your RouterOS version, board, and what the page or log shows.

Testing on real hardware is the most valuable contribution right now.

## Ringkasan (Bahasa Indonesia)

Fluxmon adalah aplikasi web open source untuk mengelola banyak router MikroTik: monitoring dengan grafik historis, peringatan (Telegram, webhook, email), backup konfigurasi terjadwal beserta perbandingan versinya, serta penjualan voucher hotspot lengkap dengan laporan.

Cara cepat: `composer install`, salin `.env.example` ke `.env`, `php artisan key:generate`, `php artisan migrate`, `npm install && npm run build`, lalu `php artisan serve`. Jalankan juga `php artisan schedule:work` di terminal kedua supaya grafik, peringatan, dan sinkronisasi voucher berjalan. Aktifkan layanan API dan SSH di MikroTik, buat akun khusus untuk Fluxmon, lalu tambahkan router dari menu **Routers**. Bahasa antarmuka bisa diganti ke Indonesia di **Settings**. Setelah `git pull`, jalankan `php artisan migrate` (jangan `migrate:fresh`, itu menghapus data).

## About the developer

Fluxmon is developed and maintained by **[PT Fluxa Tritama Indonesia](https://fluxa.co.id)**, an IT company building business applications, networks and infrastructure. Questions, partnerships or support: [official@fluxa.co.id](mailto:official@fluxa.co.id).

### Brand assets

`public/brand/` holds the Fluxmon mark and the Fluxa logo (`fluxa-black.png`, `fluxa-white.png`), and the interface colours follow Fluxa's palette (navy with sky and teal accents). The Fluxa name and logo identify the developer; if you fork Fluxmon under a different name, replace the files in `public/brand/`, the icons in `public/` and the `developer` block in `config/fluxmon.php`.

## License

GPL-2.0-or-later, see [LICENSE](LICENSE).

Fluxmon derives from [Mikhmon](https://github.com/laksa19/mikhmonv3), Copyright (C) 2018 Laksamadi Guko, licensed under GPL v2. Third-party libraries keep their own licenses.
