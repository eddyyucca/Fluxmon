<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Router extends Model
{
    protected $fillable = [
        'name', 'host', 'port', 'use_ssl', 'username', 'password',
        'hotspot_name', 'dns_name', 'currency', 'traffic_interface', 'timeout',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'use_ssl' => 'boolean',
            'is_online' => 'boolean',
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

    /** Address users reach the hotspot login page at. */
    public function loginHost(): string
    {
        return $this->dns_name ?: $this->host;
    }
}
