<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\PropertyType;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_property_can_be_created_with_new_owner(): void
    {
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
}

