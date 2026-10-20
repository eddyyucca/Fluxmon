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
            ? $this->resolve($router, 'router', __('✅ :router is back online', ['router' => $router->name]))
            : $this->open($router, 'router_offline', 'router', __('🔴 :router is unreachable', ['router' => $router->name]), null, null, $error ?? '');
    }

    public function check(Router $router, Check $check, bool $up): void
    {
        $subject = 'check:'.$check->id;
        $up
            ? $this->resolve($router, $subject, __('✅ :router: :check is reachable again', ['router' => $router->name, 'check' => $check->name]))
            : $this->open($router, 'check_down', $subject, __('🔴 :router: :check (:target) is not reachable', ['router' => $router->name, 'check' => $check->name, 'target' => $check->target]), null, null);
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
                    __('⚠️ :router: :rule (:metric :operator :threshold for :minutes min)', [
                        'router' => $router->name, 'rule' => $rule->name, 'metric' => $rule->metric,
                        'operator' => $rule->operator, 'threshold' => $rule->threshold, 'minutes' => $rule->duration_minutes,
                    ]),
                    $latest, $rule->id,
                );
            } elseif (! $rule->breaches($latest)) {
                $this->resolve($router, $subject, __('✅ :router: :rule is back to normal', ['router' => $router->name, 'rule' => $rule->name]));
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

        $this->notifier->send($title, trim(($value !== null ? __('Value: :value', ['value' => $value])."\n" : '').$detail), ['router' => $router->name, 'kind' => $kind, 'state' => 'firing']);
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
