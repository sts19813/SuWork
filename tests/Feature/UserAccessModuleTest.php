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
        Permission::findOrCreate('usuarios.gestionar', 'web');
        $role = Role::query()->create(['name' => 'administrador', 'guard_name' => 'web']);
        $role->givePermissionTo('usuarios.gestionar');
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
        Permission::findOrCreate('cobranza.ver', 'web');
        $user->givePermissionTo('cobranza.ver');

        $this->actingAs($user)
            ->get(route('access.index'))
            ->assertForbidden();
    }

    public function test_user_without_module_permissions_is_redirected_to_pending_access(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('access.pending'));

        $this->actingAs($user)
            ->get(route('charges.index'))
            ->assertRedirect(route('access.pending'));
    }

    public function test_user_with_module_permission_can_access_assigned_module(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('cobranza.ver', 'web');
        $user->givePermissionTo('cobranza.ver');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('charges.index'));

        $this->actingAs($user)
            ->get(route('charges.index'))
            ->assertOk();
    }
}
