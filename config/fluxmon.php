<?php

return [
    /*
    | Who develops Fluxmon. Shown in the footer, on the sign-in page and in Settings.
    */
    'developer' => [
        'name' => 'PT Fluxa Tritama Indonesia',
        'short' => 'Fluxa',
        'url' => 'https://fluxa.co.id',
        'email' => 'official@fluxa.co.id',
        'tagline' => 'Every Flow Builds The Future',
    ],

    /*
    | Permissions granted to each role. '*' means everything.
    | Routes are guarded with the `permission:<name>` middleware.
    */
    'roles' => [
        'admin' => ['*'],
        'technician' => [
            'monitor.view', 'hotspot.view', 'hotspot.manage', 'profiles.manage', 'vouchers.manage',
            'reports.view', 'backups.manage', 'alerts.manage', 'audit.view',
        ],
        'cashier' => ['monitor.view', 'hotspot.view', 'hotspot.manage', 'vouchers.manage', 'reports.view'],
        'viewer' => ['monitor.view', 'hotspot.view', 'reports.view'],
    ],

    /*
    | Monitoring data retention.
    */
    'metrics' => [
        'raw_days' => (int) env('FLUXMON_METRICS_RAW_DAYS', 7),       // 1-minute samples
        'rollup_days' => (int) env('FLUXMON_METRICS_ROLLUP_DAYS', 180), // hourly averages
    ],

    'backups' => [
        'keep' => (int) env('FLUXMON_BACKUPS_KEEP', 30), // per router
    ],
];
