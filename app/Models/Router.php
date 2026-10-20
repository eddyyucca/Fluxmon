<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Router extends Model
{
    protected $fillable = [
        'name', 'host', 'port', 'ssh_port', 'use_ssl', 'username', 'password',
        'hotspot_name', 'dns_name', 'currency', 'traffic_interface', 'timeout',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'use_ssl' => 'boolean',
            'is_online' => 'boolean',
            'capabilities' => 'array',
            'last_connected_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function voucherBatches(): HasMany
    {
        return $this->hasMany(VoucherBatch::class);
    }

    public function profileMetas(): HasMany
    {
        return $this->hasMany(ProfileMeta::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(ConfigBackup::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(Check::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /** Routers a user may see: everything for admins and "all routers" accounts, else the assigned ones. */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return ($user->isAdmin() || $user->all_routers)
            ? $query
            : $query->whereIn('routers.id', $user->routers()->select('routers.id'));
    }

    /** Whether detection found this feature on the router (see CapabilityDetector). */
    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities[$capability] ?? false);
    }

    /** Address users reach the hotspot login page at. */
    public function loginHost(): string
    {
        return $this->dns_name ?: $this->host;
    }
}
