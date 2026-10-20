<?php

namespace App\Models;

use App\Services\DeviceAccess;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const SUPERADMIN = 'superadmin';
    public const ADMINISTRATOR = 'administrator';
    public const STAFF = 'staff';

    public const ROLES = [self::SUPERADMIN, self::ADMINISTRATOR, self::STAFF];

    /** `role` and `all_routers` are deliberately not mass assignable. */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'all_routers' => 'boolean',
        ];
    }

    /** Devices a staff account may try to sign in to (when not "all"). */
    public function routers(): BelongsToMany
    {
        return $this->belongsToMany(Router::class);
    }

    public function isSuperadmin(): bool
    {
        return $this->role === self::SUPERADMIN;
    }

    /** Superadmins and administrators work on every device without the second login. */
    public function seesAllDevices(): bool
    {
        return in_array($this->role, [self::SUPERADMIN, self::ADMINISTRATOR], true);
    }

    /**
     * Whether this account may do something, optionally on one device.
     *
     * Superadmins and administrators have their rights everywhere. Staff have none of their own:
     * on a device they hold whatever the device account they signed in with allows.
     */
    public function hasPermission(string $permission, ?Router $router = null): bool
    {
        $granted = config("fluxmon.roles.{$this->role}", []);

        if (in_array('*', $granted, true) || in_array($permission, $granted, true)) {
            return true;
        }

        if ($this->role !== self::STAFF || ! $router || ! $this->canAccessRouter($router)) {
            return false;
        }

        $deviceRole = DeviceAccess::role($router);

        return $deviceRole !== null && in_array($permission, config("fluxmon.device_roles.{$deviceRole}", []), true);
    }

    /**
     * Devices on which this account holds a permission right now: null means all of them,
     * an array is the list (empty = none).
     *
     * @return list<int>|null
     */
    public function permittedRouterIds(string $permission): ?array
    {
        if ($this->seesAllDevices()) {
            return $this->hasPermission($permission) ? null : [];
        }

        return collect(DeviceAccess::unlockedIds())
            ->filter(fn (int $id) => ($router = Router::find($id)) && $this->hasPermission($permission, $router))
            ->values()->all();
    }

    /** Whether the permission applies on at least one device (decides which global menus show). */
    public function canAnywhere(string $permission): bool
    {
        $ids = $this->permittedRouterIds($permission);

        return $ids === null || $ids !== [];
    }

    /** Whether the device is among those this account may see at all (not the same as being signed in to it). */
    public function canAccessRouter(Router $router): bool
    {
        return $this->seesAllDevices() || $this->all_routers || $this->routers()->whereKey($router->id)->exists();
    }
}
