<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = ['admin', 'technician', 'cashier', 'viewer'];

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

    public function routers(): BelongsToMany
    {
        return $this->belongsToMany(Router::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasPermission(string $permission): bool
    {
        $granted = config("fluxmon.roles.{$this->role}", []);

        return in_array('*', $granted, true) || in_array($permission, $granted, true);
    }

    /** Router ids this account is limited to, or null when it may use every router. */
    public function limitedRouterIds(): ?array
    {
        return ($this->isAdmin() || $this->all_routers) ? null : $this->routers()->pluck('routers.id')->all();
    }

    public function canAccessRouter(Router $router): bool
    {
        return $this->isAdmin() || $this->all_routers || $this->routers()->whereKey($router->id)->exists();
    }
}
