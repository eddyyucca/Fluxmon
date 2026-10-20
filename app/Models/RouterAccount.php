<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A management account of one device. What it may do on that device comes from its role
 * (see `device_roles` in config/fluxmon.php).
 */
class RouterAccount extends Model
{
    public const ROLES = ['technician', 'cashier', 'viewer'];

    protected $fillable = ['router_id', 'name', 'username', 'password', 'role', 'active'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }
}
