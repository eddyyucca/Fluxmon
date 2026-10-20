<?php

namespace App\Services\Alerting;

use App\Mail\AlertMail;
use App\Models\Setting;
use App\Services\Telegram;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends an alert to every channel that is configured. A broken channel never blocks the others.
 */
class Notifier
{
    public function __construct(private readonly Telegram $telegram)
    {
    }

    /** @param array<string,mixed> $payload extra fields for the webhook body */
    public function send(string $title, string $body, array $payload = []): void
    {
        $text = trim($title."\n".$body);

        if ($this->telegram->enabled('offline_alert')) {
            $this->guarded('telegram', fn () => $this->telegram->send($text));
        }

        if ($url = Setting::read('webhook_url')) {
            $this->guarded('webhook', fn () => Http::timeout(8)->post($url, ['title' => $title, 'message' => $body, 'source' => 'fluxmon'] + $payload));
        }

        $recipients = array_filter(array_map('trim', explode(',', (string) Setting::read('alert_email'))));
        if ($recipients) {
            $this->guarded('email', fn () => Mail::to($recipients)->send(new AlertMail($title, $text)));
        }
    }

    private function guarded(string $channel, \Closure $send): void
    {
        try {
            $send();
        } catch (Throwable $e) {
            Log::warning("Fluxmon alert via {$channel} failed: ".$e->getMessage());
        }
    }
}
