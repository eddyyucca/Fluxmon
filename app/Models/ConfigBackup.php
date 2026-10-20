<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigBackup extends Model
{
    public $timestamps = false;

    protected $fillable = ['router_id', 'user_id', 'reason', 'hash', 'size', 'content'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
