<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = [
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

        $permissions = collect($permissionNames)
            ->map(fn (string $name) => Permission::findOrCreate($name, 'web'));

        $adminRole = Role::query()->firstOrCreate([
            'name' => 'administrador',
            'guard_name' => 'web',
        ]);
        $adminRole->syncPermissions($permissions);

        $ownerRole = Role::query()->firstOrCreate([
            'name' => 'propietario',
            'guard_name' => 'web',
        ]);
        $ownerRole->syncPermissions([
            'propiedades.ver',
            'propietarios.ver',
            'inquilinos.ver',
            'expedientes.ver',
            'cobranza.ver',
            'gastos.ver',
            'mantenimiento.ver',
            'almacen.ver',
        ]);

        $tenantRole = Role::query()->firstOrCreate([
            'name' => 'inquilino',
            'guard_name' => 'web',
        ]);
        $tenantRole->syncPermissions([
            'cobranza.ver',
            'mantenimiento.ver',
        ]);

        $technicianRole = Role::query()->firstOrCreate([
            'name' => 'tecnico',
            'guard_name' => 'web',
        ]);
        $technicianRole->syncPermissions([
            'mantenimiento.ver',
            'almacen.ver',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
