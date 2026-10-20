<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\ReportService;
use App\Services\Telegram;
use App\Support\DefaultLocale;
use App\Support\Fmt;
use Illuminate\Console\Command;

class DailyReportCommand extends Command
{
    protected $signature = 'fluxmon:daily-report {--force : Send even if the daily report switch is off}';

    protected $description = "Send today's sales summary to Telegram";

    public function handle(ReportService $reports, Telegram $telegram): int
    {
        DefaultLocale::apply();

        if (! $this->option('force') && ! $telegram->enabled('daily_report')) {
            $this->line('Daily report is off or Telegram is not configured.');

            return self::SUCCESS;
        }

        $currency = Router::value('currency') ?? 'Rp';
        $summary = $reports->summary(now()->startOfDay(), now()->endOfDay());

        $lines = [__('📊 Fluxmon daily report — :date', ['date' => now()->translatedFormat('d M Y')]), ''];
        $lines[] = __('Revenue').': '.Fmt::money($summary['revenue'], $currency);
        $lines[] = __('Vouchers sold').': '.$summary['count'];
        foreach ($summary['by_router'] as $row) {
            $lines[] = "• {$row->name}: ".Fmt::money($row->revenue, $currency)." ({$row->n})";
        }

        $error = $telegram->send(implode("\n", $lines));
        $error ? $this->error($error) : $this->info('Report sent.');

        return $error ? self::FAILURE : self::SUCCESS;
    }
}
