<?php

namespace App\Services\Alerting;

use App\Models\AlertEvent;
use App\Models\AlertRule;
use App\Models\Check;
use App\Models\Metric;
use App\Models\Router;

class AlertService
{
    public function __construct(private readonly Notifier $notifier)
    {
    }

    /** Router reachable or not, as found by the latest sync. */
    public function router(Router $router, bool $ok, ?string $error = null): void
    {
        $ok
            ? $this->resolve($router, 'router', "✅ {$router->name} is back online")
            : $this->open($router, 'router_offline', 'router', "🔴 {$router->name} is unreachable", null, null, $error ?? '');
    }

    public function check(Router $router, Check $check, bool $up): void
    {
        $subject = 'check:'.$check->id;
        $up
            ? $this->resolve($router, $subject, "✅ {$router->name}: {$check->name} is reachable again")
            : $this->open($router, 'check_down', $subject, "🔴 {$router->name}: {$check->name} ({$check->target}) is not reachable", null, null);
    }

    /**
     * Evaluate threshold rules from the stored samples. A rule fires when every sample of the
     * last `duration_minutes` is on the wrong side of the threshold, and clears on the next good one.
     */
    public function evaluateRules(Router $router, ?int $now = null): void
    {
        $now ??= time();

        $rules = AlertRule::where('enabled', true)
            ->where(fn ($q) => $q->whereNull('router_id')->orWhere('router_id', $router->id))
            ->get();

        foreach ($rules as $rule) {
            $samples = Metric::where('router_id', $router->id)->where('metric', $rule->metric)->where('ref', '')
                ->where('resolution', Metric::RAW)
                ->where('recorded_at', '>=', $now - $rule->duration_minutes * 60 - 30)
                ->orderBy('recorded_at')->pluck('value');

            if ($samples->isEmpty()) {
                continue;
            }

            $latest = (float) $samples->last();
            $subject = 'rule:'.$rule->id;

            // Need most of the window to be sampled so one reading cannot trip a 10-minute rule.
            $enough = $samples->count() >= max(1, (int) floor($rule->duration_minutes * 0.6));
            $sustained = $enough && $samples->every(fn ($v) => $rule->breaches((float) $v));

            if ($sustained) {
                $this->open(
                    $router, 'rule', $subject,
                    "⚠️ {$router->name}: {$rule->name} ({$rule->metric} {$rule->operator} {$rule->threshold} for {$rule->duration_minutes} min)",
                    $latest, $rule->id,
                );
            } elseif (! $rule->breaches($latest)) {
                $this->resolve($router, $subject, "✅ {$router->name}: {$rule->name} is back to normal");
            }
        }
    }

    /** Start an event unless one is already firing for the same subject. */
    private function open(Router $router, string $kind, string $subject, string $title, ?float $value, ?int $ruleId = null, string $detail = ''): void
    {
        if (AlertEvent::firing()->where('router_id', $router->id)->where('subject', $subject)->exists()) {
            return;
        }

        AlertEvent::create([
            'alert_rule_id' => $ruleId, 'router_id' => $router->id, 'kind' => $kind, 'subject' => $subject,
            'title' => $title, 'value' => $value, 'state' => 'firing', 'started_at' => now(),
        ]);

        $this->notifier->send($title, trim(($value !== null ? "Value: {$value}\n" : '').$detail), ['router' => $router->name, 'kind' => $kind, 'state' => 'firing']);
    }

    private function resolve(Router $router, string $subject, string $message): void
    {
        $events = AlertEvent::firing()->where('router_id', $router->id)->where('subject', $subject)->get();
        if ($events->isEmpty()) {
            return;
        }

        foreach ($events as $event) {
            $event->update(['state' => 'resolved', 'resolved_at' => now()]);
        }

        $this->notifier->send($message, '', ['router' => $router->name, 'state' => 'resolved']);
    }
}
