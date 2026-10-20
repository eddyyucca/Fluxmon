<?php

use Illuminate\Support\Facades\Schedule;

// Run `php artisan schedule:work` (or a cron entry for `schedule:run`) to enable these.
Schedule::command('fluxmon:sync')->everyMinute()->withoutOverlapping();
Schedule::command('fluxmon:daily-report')->dailyAt('23:55');
