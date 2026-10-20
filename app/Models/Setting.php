<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Key/value application settings (Telegram, locale, ...).
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function read(string $key, ?string $default = null): ?string
    {
        return static::find($key)?->value ?? $default;
    }

    public static function write(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function readSecret(string $key): ?string
    {
        $raw = static::read($key);

        return $raw ? Crypt::decryptString($raw) : null;
    }

    public static function writeSecret(string $key, ?string $value): void
    {
        static::write($key, $value ? Crypt::encryptString($value) : null);
    }
}
