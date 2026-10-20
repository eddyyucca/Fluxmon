<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
            'last_connected_at' => 'datetime',
        ];
    }
}
