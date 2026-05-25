<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    public const MODULE_PERMISSION_ROUTE_MAP = [
        'propiedades.ver' => 'properties.index',
        'propietarios.ver' => 'owners.index',
        'inquilinos.ver' => 'tenants.index',
        'expedientes.ver' => 'documents.index',
        'cobranza.ver' => 'charges.index',
        'gastos.ver' => 'expenses.index',
        'mantenimiento.ver' => 'maintenance.index',
        'almacen.ver' => 'storage_items.index',
        'usuarios.gestionar' => 'access.index',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'profile_photo',
        'google_id',
        'google_access_token',
        'google_refresh_token',
        'google_token_expires_at',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'google_token_expires_at' => 'datetime',
        ];
    }

    public function createdProperties(): HasMany
    {
        return $this->hasMany(Property::class, 'created_by');
    }

    public function createdCharges(): HasMany
    {
        return $this->hasMany(Charge::class, 'created_by');
    }

    public function maintenanceTicketsReported(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class, 'reported_by_user_id');
    }

    /**
     * @return array<int, string>
     */
    public static function modulePermissions(): array
    {
        return array_keys(self::MODULE_PERMISSION_ROUTE_MAP);
    }

    public function hasAnyModulePermission(): bool
    {
        return $this->hasAnyPermission(self::modulePermissions());
    }

    public function firstAccessibleRouteName(): ?string
    {
        $permissions = $this->getAllPermissions()->pluck('name');

        foreach (self::MODULE_PERMISSION_ROUTE_MAP as $permission => $routeName) {
            if ($permissions->contains($permission)) {
                return $routeName;
            }
        }

        return null;
    }
}
