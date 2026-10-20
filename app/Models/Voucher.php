<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    public const UNUSED = 'unused';
    public const USED = 'used';
    public const EXPIRED = 'expired';

    protected $fillable = [
        'router_id', 'voucher_batch_id', 'username', 'password', 'profile',
        'price', 'status', 'first_used_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'first_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(VoucherBatch::class, 'voucher_batch_id');
    }

    /** Vouchers that have been redeemed at least once (expired ones were used too). */
    public function scopeSold($query)
    {
        return $query->whereNotNull('first_used_at');
    }
}
