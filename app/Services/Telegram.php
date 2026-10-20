<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Throwable;

class Telegram
{
    public function configured(): bool
    {
        return (bool) (Setting::readSecret('telegram_token') && Setting::read('telegram_chat_id'));
    }

    /** Whether a notification type (daily_report, offline_alert) is switched on. */
    public function enabled(string $type): bool
    {
        return $this->configured() && Setting::read("telegram_{$type}") === '1';
    }

    /** @return string|null error message, null on success */
    public function send(string $text): ?string
    {
        if (! $this->configured()) {
            return 'Telegram is not configured.';
        }

        try {
            $response = Http::timeout(10)->post(
                'https://api.telegram.org/bot'.Setting::readSecret('telegram_token').'/sendMessage',
                ['chat_id' => Setting::read('telegram_chat_id'), 'text' => $text],
            );

            return $response->successful() ? null : ($response->json('description') ?? 'Telegram rejected the message.');
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }
}
