<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertRule extends Model
{
    public const METRICS = ['cpu' => 'CPU load (%)', 'memory' => 'Memory used (%)', 'temperature' => 'Temperature (°C)'];

    protected $fillable = ['router_id', 'name', 'metric', 'operator', 'threshold', 'duration_minutes', 'enabled'];

    protected function casts(): array
    {
        return ['threshold' => 'float', 'enabled' => 'boolean'];
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    /** Whether a sampled value is on the bad side of the threshold. */
    public function breaches(float $value): bool
    {
        return $this->operator === '<' ? $value < $this->threshold : $value > $this->threshold;
    }
}
