<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'router_id', 'action', 'details', 'ip'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    /** Record an action by the current user. */
    public static function record(string $action, ?string $details = null, ?Router $router = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'router_id' => $router?->id,
            'action' => $action,
            'details' => $details,
            'ip' => request()?->ip(),
        ]);
    }
}
