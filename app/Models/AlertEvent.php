<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['alert_rule_id', 'router_id', 'kind', 'subject', 'title', 'value', 'state', 'started_at', 'resolved_at'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'resolved_at' => 'datetime', 'value' => 'float'];
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function scopeFiring($query)
    {
        return $query->where('state', 'firing');
    }
}
