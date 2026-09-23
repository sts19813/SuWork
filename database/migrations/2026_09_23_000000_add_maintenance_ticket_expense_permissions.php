<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['editar gastos tickets', 'eliminar gastos tickets'] as $permissionName) {
            $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');

            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $adminRoleIds = DB::table('roles')
                ->whereIn('name', ['administrador', 'admin'])
                ->pluck('id');

            foreach ($adminRoleIds as $roleId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['editar gastos tickets', 'eliminar gastos tickets'])
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
