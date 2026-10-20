<?php

namespace App\Support;

/**
 * The in-app user guide. Pages live in resources/views/docs/{locale}/{slug}.blade.php;
 * a page missing in the current language falls back to English.
 */
class Docs
{
    /** @return array<string,string> slug => English title (translated with __() when shown) */
    public static function pages(): array
    {
        return [
            'getting-started' => 'Getting started',
            'connect-router' => 'Connect a router',
            'dashboard' => 'Dashboard and overview',
            'monitoring' => 'Monitoring and checks',
            'alerts' => 'Alerts and notifications',
            'hotspot' => 'Hotspot users and profiles',
            'vouchers' => 'Selling vouchers',
            'reports' => 'Reports',
            'backups' => 'Configuration backups',
            'accounts' => 'Accounts and roles',
            'settings' => 'Settings',
            'scheduler' => 'Scheduler (background jobs)',
            'troubleshooting' => 'Troubleshooting',
        ];
    }

    public static function exists(string $slug): bool
    {
        return array_key_exists($slug, self::pages());
    }

    /** View name for a page in the given locale, falling back to English. */
    public static function view(string $slug, string $locale): string
    {
        return view()->exists("docs.{$locale}.{$slug}") ? "docs.{$locale}.{$slug}" : "docs.en.{$slug}";
    }

    /** @return array{prev: ?string, next: ?string} neighbouring slugs */
    public static function neighbours(string $slug): array
    {
        $slugs = array_keys(self::pages());
        $i = array_search($slug, $slugs, true);

        return ['prev' => $slugs[$i - 1] ?? null, 'next' => $slugs[$i + 1] ?? null];
    }
}
