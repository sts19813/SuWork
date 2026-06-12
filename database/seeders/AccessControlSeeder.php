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
            'usuarios.gestionar',
            'expedientes.eliminar_archivos',
            'expedientes.ver_bitacora_eliminados',
            'propiedades.control_ver',
        ];

        $permissions = collect($permissionNames)
            ->map(fn (string $name) => Permission::findOrCreate($name, 'web'));

        $adminRole = Role::query()->firstOrCreate([
            'name' => 'administrador',
            'guard_name' => 'web',
        ]);
        $adminRole->syncPermissions($permissions);

        Role::query()->firstOrCreate([
            'name' => 'propietario',
            'guard_name' => 'web',
        ]);
        Role::query()->firstOrCreate([
            'name' => 'inquilino',
            'guard_name' => 'web',
        ]);
        Role::query()->firstOrCreate([
            'name' => 'tecnico',
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
