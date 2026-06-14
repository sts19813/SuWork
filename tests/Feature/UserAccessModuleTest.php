<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAccessModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_role_can_access_user_access_module(): void
    {
        $role = Role::query()->create(['name' => 'administrador', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('access.index'))
            ->assertOk()
            ->assertSee('Usuarios, roles y permisos');
    }

    public function test_non_admin_user_cannot_access_user_access_module(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('access.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_user_with_ajax_response(): void
    {
        $admin = $this->adminUser();
        $role = Role::query()->create(['name' => 'asesor', 'guard_name' => 'web']);
        $permission = Permission::query()->create(['name' => 'propiedades.ver', 'guard_name' => 'web']);

        $this->actingAs($admin)
            ->postJson(route('access.users.store'), [
                'name' => 'Usuario Ajax',
                'email' => 'ajax@example.com',
                'password' => 'password123',
                'is_active' => '1',
                'role_names' => [$role->name],
                'permission_names' => [$permission->name],
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'tab' => 'users',
            ]);

        $created = User::query()->where('email', 'ajax@example.com')->firstOrFail();
        $this->assertTrue($created->hasRole('asesor'));
        $this->assertTrue($created->hasDirectPermission('propiedades.ver'));
    }

    public function test_ajax_index_returns_access_partial(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('access.index', ['tab' => 'roles']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('id="access-module"', false)
            ->assertDontSee('<!DOCTYPE html>', false);
    }

    private function adminUser(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
