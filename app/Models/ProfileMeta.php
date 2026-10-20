<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileMeta extends Model
{
    protected $fillable = ['router_id', 'name', 'price', 'validity_minutes', 'expire_action'];

    protected function casts(): array
    {
        return ['price' => 'float'];
    }
}
