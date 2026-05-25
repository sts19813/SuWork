<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'propiedades.ver',
            'propietarios.ver',
            'inquilinos.ver',
            'expedientes.ver',
            'cobranza.ver',
            'gastos.ver',
            'mantenimiento.ver',
            'almacen.ver',
            'usuarios.gestionar',
            'expedientes.eliminar_archivos',
            'expedientes.ver_bitacora_eliminados',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::query()->whereIn('name', ['administrador', 'admin'])->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        $tenantPermissions = ['cobranza.ver', 'mantenimiento.ver'];
        Role::query()
            ->whereIn('name', ['inquilino', 'tenant'])
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($tenantPermissions));

        $technicianPermissions = ['mantenimiento.ver', 'almacen.ver'];
        Role::query()
            ->whereIn('name', ['tecnico', 'technician'])
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($technicianPermissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        //
    }
};

