<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Check extends Model
{
    public const TYPES = ['router_ping' => 'Ping from router', 'tcp' => 'TCP port from Fluxmon'];

    protected $fillable = ['router_id', 'name', 'type', 'target', 'port', 'enabled'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'last_status' => 'boolean',
            'last_rtt' => 'float',
            'last_checked_at' => 'datetime',
            'last_change_at' => 'datetime',
        ];
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }
}
