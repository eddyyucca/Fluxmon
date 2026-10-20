<?php

namespace App\Models;

use App\Services\DeviceAccess;
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
        // Staff act through a device account; name it so "who did this?" has a full answer.
        if ($router && ! str_starts_with($action, 'device.') && ($account = DeviceAccess::accountName($router))) {
            $details = trim(($details ?? '')." [device account: {$account}]");
        }

        static::create([
            'user_id' => auth()->id(),
            'router_id' => $router?->id,
            'action' => $action,
            'details' => $details,
            'ip' => request()?->ip(),
        ]);
    }
}
