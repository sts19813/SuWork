<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

