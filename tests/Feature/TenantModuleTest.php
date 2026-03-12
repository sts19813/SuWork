<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenants_index_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('tenants.index'));

        $response->assertOk();
        $response->assertSee('Inquilinos');
    }

    public function test_tenant_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('tenants.store'), [
                'full_name' => 'Ana Lucia Torres',
                'phone_primary' => '9991112233',
                'email' => 'ana@example.com',
                'monthly_income' => 28000,
                'dossier_status' => Tenant::DOSSIER_COMPLETE,
            ]);

        $response->assertRedirect(route('tenants.index'));
        $this->assertDatabaseHas('tenants', ['full_name' => 'Ana Lucia Torres']);
    }

    public function test_tenant_can_be_updated(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'full_name' => 'Roberto Canul',
            'phone_primary' => '9994445566',
            'email' => 'roberto@example.com',
            'dossier_status' => Tenant::DOSSIER_IN_REVIEW,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('tenants.update', $tenant), [
                'full_name' => 'Roberto Canul Dzib',
                'phone_primary' => '9994445566',
                'email' => 'roberto@example.com',
                'dossier_status' => Tenant::DOSSIER_IN_REVIEW,
            ]);

        $response->assertRedirect(route('tenants.index'));
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'full_name' => 'Roberto Canul Dzib',
        ]);
    }
}

