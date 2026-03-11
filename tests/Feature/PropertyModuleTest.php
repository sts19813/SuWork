<?php

namespace Tests\Feature;

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

    public function test_property_can_be_created_with_owner(): void
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
                'owners' => [
                    [
                        'name' => 'Juan Pérez',
                        'phone' => '9991234567',
                        'email' => 'juan@example.com',
                        'owner_type' => 'individual',
                        'payment_method' => 'transfer',
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('properties', ['internal_name' => 'Casa Centro 201']);
        $this->assertDatabaseHas('property_owners', ['email' => 'juan@example.com']);
        $this->assertDatabaseCount('property_documents', count(PropertyDocument::REQUIRED_DOCUMENTS));
    }
}
