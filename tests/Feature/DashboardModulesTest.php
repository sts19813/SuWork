<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();
        $type = PropertyType::query()->create([
            'name' => 'Casa',
            'slug' => 'casa',
            'is_active' => true,
        ]);
        $zone = Zone::query()->create([
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);

        Property::query()->create([
            'internal_name' => 'Casa Centro',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 1',
            'status' => Property::STATUS_AVAILABLE,
            'monthly_rent_price' => 12000,
            'facade_photo_path' => 'properties/test.jpg',
            'created_by' => $user->id,
            'advisor_user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Panel ejecutivo')
            ->assertSee('Resumen de cobranza');
    }

    public function test_advisor_dashboard_defaults_to_assigned_properties_and_can_view_all(): void
    {
        $advisorRole = Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']);
        $advisor = User::factory()->create();
        $advisor->assignRole($advisorRole);
        $creator = User::factory()->create();
        $type = PropertyType::query()->create([
            'name' => 'Casa',
            'slug' => 'casa',
            'is_active' => true,
        ]);
        $zone = Zone::query()->create([
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);

        $assignedProperty = Property::query()->create([
            'internal_name' => 'Casa Dashboard Asignada',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 1',
            'status' => Property::STATUS_OCCUPIED,
            'monthly_rent_price' => 12000,
            'created_by' => $creator->id,
        ]);
        $assignedProperty->advisors()->attach($advisor->id);

        Property::query()->create([
            'internal_name' => 'Casa Dashboard General',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 2',
            'status' => Property::STATUS_OCCUPIED,
            'monthly_rent_price' => 15000,
            'created_by' => $creator->id,
        ]);

        $this->actingAs($advisor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Casa Dashboard Asignada')
            ->assertDontSee('Casa Dashboard General');

        $this->actingAs($advisor)
            ->get(route('dashboard', ['property_scope' => 'all']))
            ->assertOk()
            ->assertSee('Casa Dashboard Asignada')
            ->assertSee('Casa Dashboard General');
    }

    public function test_property_control_requires_explicit_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('properties.control'))
            ->assertForbidden();
    }

    public function test_user_with_property_control_permission_can_view_module(): void
    {
        $user = User::factory()->create();

        Permission::findOrCreate('propiedades.control_ver', 'web');
        $user->givePermissionTo('propiedades.control_ver');

        $this->actingAs($user)
            ->get(route('properties.control'))
            ->assertOk()
            ->assertSee('Control de Alta de Propiedades');
    }
}
