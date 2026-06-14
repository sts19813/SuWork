<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\PropertyType;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PropertyModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_properties_index_is_displayed_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Montebello', 'slug' => 'montebello', 'is_active' => true]);

        Property::create([
            'internal_name' => 'Casa Montebello 101',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 1 #101',
            'status' => Property::STATUS_AVAILABLE,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('properties.index'));

        $response->assertOk();
        $response->assertSee('Casa Montebello 101');
    }

    public function test_property_create_page_is_displayed(): void
    {
        $user = User::factory()->create();
        PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);

        $response = $this
            ->actingAs($user)
            ->get(route('properties.create'));

        $response->assertOk();
        $response->assertSee('Nueva Propiedad');
    }

    public function test_advisor_role_sees_assigned_properties_by_default_and_can_view_all(): void
    {
        $advisorRole = Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']);
        $advisor = User::factory()->create();
        $advisor->assignRole($advisorRole);
        $otherUser = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);

        $assignedProperty = Property::create([
            'internal_name' => 'Casa Asignada',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Asignada',
            'status' => Property::STATUS_AVAILABLE,
            'created_by' => $otherUser->id,
        ]);
        $assignedProperty->advisors()->attach($advisor->id);

        Property::create([
            'internal_name' => 'Casa General',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle General',
            'status' => Property::STATUS_AVAILABLE,
            'created_by' => $otherUser->id,
        ]);

        $this->actingAs($advisor)
            ->get(route('properties.index'))
            ->assertOk()
            ->assertSee('Casa Asignada')
            ->assertDontSee('Casa General');

        $this->actingAs($advisor)
            ->get(route('properties.index', ['property_scope' => 'all']))
            ->assertOk()
            ->assertSee('Casa Asignada')
            ->assertSee('Casa General');
    }

    public function test_admin_can_assign_responsible_advisors_from_properties_index(): void
    {
        $adminRole = Role::query()->create(['name' => 'administrador', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);
        $advisor = User::factory()->create(['name' => 'Asesor Demo']);
        $advisor->assignRole(Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']));
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);
        $property = Property::create([
            'internal_name' => 'Casa con Asesor',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Asesor',
            'status' => Property::STATUS_AVAILABLE,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->putJson(route('properties.update.advisors', $property), [
                'advisor_user_ids' => [$advisor->id],
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('property_advisor', [
            'property_id' => $property->id,
            'user_id' => $advisor->id,
        ]);
        $this->assertSame($advisor->id, $property->fresh()->advisor_user_id);
    }

    public function test_property_advisor_assignment_requires_specific_permission(): void
    {
        $manager = User::factory()->create();
        Permission::findOrCreate('usuarios.gestionar', 'web');
        Permission::findOrCreate('propiedades.asignar_asesores', 'web');
        $manager->givePermissionTo('usuarios.gestionar');
        $advisor = User::factory()->create(['name' => 'Asesor Demo']);
        $advisor->assignRole(Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']));
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);
        $property = Property::create([
            'internal_name' => 'Casa Permiso Asesor',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Permiso',
            'status' => Property::STATUS_AVAILABLE,
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->putJson(route('properties.update.advisors', $property), [
                'advisor_user_ids' => [$advisor->id],
            ])
            ->assertForbidden();

        $manager->givePermissionTo('propiedades.asignar_asesores');

        $this->actingAs($manager)
            ->putJson(route('properties.update.advisors', $property), [
                'advisor_user_ids' => [$advisor->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_property_can_be_created_with_new_owner(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);

        $response = $this
            ->actingAs($user)
            ->post(route('properties.store'), [
                'internal_name' => 'Casa Centro 201',
                'internal_reference' => 'CC-201',
                'property_type_id' => $type->id,
                'zone_id' => $zone->id,
                'full_address' => 'Calle 20 #201',
                'status' => Property::STATUS_AVAILABLE,
                'facade_photo' => UploadedFile::fake()->image('facade.jpg'),
                'new_owners' => [
                    [
                        'name' => 'Juan Perez',
                        'phone' => '9991234567',
                        'email' => 'juan@example.com',
                        'owner_type' => Owner::OWNER_INDIVIDUAL,
                        'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('properties', ['internal_name' => 'Casa Centro 201']);
        $this->assertDatabaseHas('owners', ['email' => 'juan@example.com']);
        $this->assertDatabaseCount('property_documents', count(PropertyDocument::REQUIRED_DOCUMENTS));
        $this->assertDatabaseCount('owner_property', 1);
    }

    public function test_property_route_uses_uuid(): void
    {
        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Playa', 'slug' => 'playa', 'is_active' => true]);

        $property = Property::create([
            'internal_name' => 'Casa Playa 9',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 9',
            'status' => Property::STATUS_AVAILABLE,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('properties.show', $property));

        $response->assertOk();
        $this->assertNotNull($property->uuid);
        $this->assertStringContainsString('/propiedades/' . $property->uuid, route('properties.show', $property));
    }

    public function test_property_can_be_updated_from_edit_flow(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $type2 = PropertyType::create(['name' => 'Local', 'slug' => 'local', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Playa', 'slug' => 'playa', 'is_active' => true]);
        $zone2 = Zone::create(['name' => 'Montebello', 'slug' => 'montebello', 'is_active' => true]);
        $owner = Owner::create([
            'name' => 'Laura Gomez',
            'phone' => '9993332211',
            'email' => 'laura@example.com',
            'owner_type' => Owner::OWNER_INDIVIDUAL,
            'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
            'is_active' => true,
        ]);

        $property = Property::create([
            'internal_name' => 'Casa Playa 9',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 9',
            'status' => Property::STATUS_AVAILABLE,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('properties.update', $property), [
                'internal_name' => 'Local Montebello 20',
                'internal_reference' => 'LM-20',
                'property_type_id' => $type2->id,
                'zone_id' => $zone2->id,
                'full_address' => 'Calle 20',
                'status' => Property::STATUS_BLOCKED,
                'facade_photo' => UploadedFile::fake()->image('facade.jpg'),
                'owner_ids' => [$owner->id],
            ]);

        $response->assertRedirect(route('properties.show', $property));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'internal_name' => 'Local Montebello 20',
            'status' => Property::STATUS_BLOCKED,
        ]);
        $this->assertDatabaseHas('owner_property', [
            'property_id' => $property->id,
            'owner_id' => $owner->id,
        ]);
    }

    public function test_inventory_area_and_item_ids_are_preserved_in_property_update(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Playa', 'slug' => 'playa', 'is_active' => true]);

        $property = Property::create([
            'internal_name' => 'Casa Bug 23',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 23',
            'status' => Property::STATUS_AVAILABLE,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);

        $owner = Owner::create([
            'name' => 'Laura Gomez',
            'phone' => '9993332211',
            'email' => 'laura@example.com',
            'owner_type' => Owner::OWNER_INDIVIDUAL,
            'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
            'is_active' => true,
        ]);

        $area = $property->inventoryAreas()->create([
            'name' => 'Sala',
            'notes' => 'Principal',
        ]);

        $item = $area->items()->create([
            'name' => 'Sillon',
            'condition' => 'bueno',
            'notes' => 'Ninguna',
            'entry_checklist' => 'OK',
            'exit_checklist' => 'OK',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('properties.update', $property), [
                'internal_name' => 'Casa Bug 23 Editada',
                'property_type_id' => $type->id,
                'zone_id' => $zone->id,
                'full_address' => 'Calle 23',
                'status' => Property::STATUS_AVAILABLE,
                'facade_photo' => UploadedFile::fake()->image('facade.jpg'),
                'owner_ids' => [$owner->id],
                'inventory_areas' => [
                    [
                        'id' => $area->id,
                        'name' => 'Sala',
                        'notes' => 'Principal actualizado',
                        'items' => [
                            [
                                'id' => $item->id,
                                'name' => 'Sillon',
                                'condition' => 'bueno',
                                'notes' => 'Ninguna',
                                'entry_checklist' => 'OK',
                                'exit_checklist' => 'OK',
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertRedirect(route('properties.show', $property));

        $this->assertDatabaseHas('property_inventory_areas', [
            'id' => $area->id,
            'name' => 'Sala',
            'notes' => 'Principal actualizado',
        ]);

        $this->assertDatabaseHas('property_inventory_items', [
            'id' => $item->id,
            'name' => 'Sillon',
            'entry_checklist' => 'OK',
            'exit_checklist' => 'OK',
        ]);

        $this->assertEquals(1, $property->fresh()->inventoryAreas()->count());
        $this->assertEquals(1, $property->fresh()->inventoryAreas->first()->items->count());
    }

    public function test_occupied_status_requires_tenant_selection(): void
    {
        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Playa', 'slug' => 'playa', 'is_active' => true]);

        $response = $this
            ->actingAs($user)
            ->from(route('properties.create'))
            ->post(route('properties.store'), [
                'internal_name' => 'Casa Ocupada',
                'property_type_id' => $type->id,
                'zone_id' => $zone->id,
                'full_address' => 'Calle 10',
                'status' => Property::STATUS_OCCUPIED,
                'new_owners' => [
                    [
                        'name' => 'Owner Uno',
                        'phone' => '9990001111',
                    ],
                ],
            ]);

        $response->assertRedirect(route('properties.create'));
        $response->assertSessionHasErrors('tenant_id');
    }

    public function test_property_can_be_saved_as_occupied_with_tenant(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Playa', 'slug' => 'playa', 'is_active' => true]);
        $tenant = Tenant::create([
            'full_name' => 'Ana Lucia Torres',
            'phone_primary' => '9991112233',
            'dossier_status' => Tenant::DOSSIER_COMPLETE,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('properties.store'), [
                'internal_name' => 'Casa Ocupada 2',
                'property_type_id' => $type->id,
                'zone_id' => $zone->id,
                'full_address' => 'Calle 20',
                'status' => Property::STATUS_OCCUPIED,
                'facade_photo' => UploadedFile::fake()->image('facade.jpg'),
                'tenant_id' => $tenant->id,
                'new_owners' => [
                    [
                        'name' => 'Owner Dos',
                        'phone' => '9991112222',
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('properties', [
            'internal_name' => 'Casa Ocupada 2',
            'status' => Property::STATUS_OCCUPIED,
            'tenant_id' => $tenant->id,
        ]);
    }
}
