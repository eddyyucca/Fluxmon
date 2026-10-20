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
    | Fluxmon accounts.
    |
    |  - superadmin:    everything, every device without a second login, and the one combined report.
    |  - administrator: every device without a second login, one device at a time; manages device accounts.
    |  - staff:         no rights of their own. They sign in to each device with that device's own account,
    |                   and the account's role (see "device_roles") decides what they can do there.
    |
    | '*' means everything. Routes are guarded with the `permission:<name>` middleware.
    */
    'roles' => [
        'superadmin' => ['*'],
        'administrator' => [
            'monitor.view', 'hotspot.view', 'hotspot.manage', 'profiles.manage', 'vouchers.manage',
            'reports.view', 'backups.manage', 'alerts.manage', 'audit.view', 'accounts.manage',
        ],
        'staff' => [],
    ],

    /*
    | What a device account may do on its own device.
    */
    'device_roles' => [
        'technician' => [
            'monitor.view', 'hotspot.view', 'hotspot.manage', 'profiles.manage', 'vouchers.manage',
            'reports.view', 'backups.manage', 'alerts.manage',
        ],
        'cashier' => ['monitor.view', 'hotspot.view', 'hotspot.manage', 'vouchers.manage', 'reports.view'],
        'viewer' => ['monitor.view', 'hotspot.view', 'reports.view'],
    ],

    // A device sign-in ends after this many idle minutes (default 8 hours).
    'device_session_minutes' => (int) env('FLUXMON_DEVICE_SESSION_MINUTES', 480),

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
