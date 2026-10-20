<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metric extends Model
{
    public $timestamps = false;

    public const RAW = 60;
    public const HOURLY = 3600;

    protected $fillable = ['router_id', 'metric', 'ref', 'value', 'resolution', 'recorded_at'];

    protected function casts(): array
    {
        return ['value' => 'float'];
    }
}
