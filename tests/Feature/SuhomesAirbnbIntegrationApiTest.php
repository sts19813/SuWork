<?php

namespace Tests\Feature;

use App\Models\AirbnbFinancialEvent;
use App\Models\Charge;
use App\Models\IntegrationClient;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Tenant;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuhomesAirbnbIntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_airbnb_integration_requires_a_sanctum_token_with_airbnb_ability(): void
    {
        $this->getJson('/api/internal/airbnb/properties')->assertUnauthorized();

        $client = IntegrationClient::query()->create([
            'name' => 'Otro cliente',
            'key' => 'other',
        ]);
        $token = $client->createToken('other', ['other'])->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/internal/airbnb/properties')
            ->assertForbidden();
    }

    public function test_airbnb_client_can_list_claim_and_release_available_properties(): void
    {
        $property = $this->createProperty('Casa Disponible');
        $occupied = $this->createProperty('Casa Ocupada');
        $tenant = Tenant::query()->create([
            'full_name' => 'Inquilino Uno',
            'phone_primary' => '9991112233',
            'is_active' => true,
        ]);
        $occupied->update(['tenant_id' => $tenant->id]);

        $token = $this->airbnbToken();

        $this->withToken($token)
            ->getJson('/api/internal/airbnb/properties')
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $property->uuid)
            ->assertJsonMissing(['uuid' => $occupied->uuid]);

        $this->withToken($token)
            ->postJson("/api/internal/airbnb/properties/{$property->uuid}/claim", [
                'external_property_id' => 'airbnb-property-1',
                'external_property_code' => 'AIR-001',
                'external_property_name' => 'Casa Disponible Airbnb',
            ])
            ->assertOk()
            ->assertJsonPath('data.airbnb_external_property_id', 'airbnb-property-1')
            ->assertJsonPath('data.status', Property::STATUS_BLOCKED);

        $property->refresh();
        $this->assertNotNull($property->airbnb_managed_at);
        $this->assertSame('airbnb-property-1', $property->airbnb_external_property_id);

        $this->withToken($token)
            ->postJson("/api/internal/airbnb/properties/{$property->uuid}/release", [
                'external_property_id' => 'airbnb-property-1',
            ])
            ->assertOk()
            ->assertJsonPath('data.airbnb_external_property_id', null)
            ->assertJsonPath('data.status', Property::STATUS_AVAILABLE);
    }

    public function test_claim_rejects_properties_with_open_charges(): void
    {
        $property = $this->createProperty('Casa con Cobranza');
        $tenant = Tenant::query()->create([
            'full_name' => 'Inquilino Cargo',
            'phone_primary' => '9991112233',
            'is_active' => true,
        ]);
        Charge::query()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'type' => Charge::TYPE_RENT,
            'due_date' => now()->toDateString(),
            'amount' => 1000,
            'paid_amount' => 0,
            'period_month' => (int) now()->month,
            'period_year' => (int) now()->year,
            'concept' => 'Renta',
            'status' => Charge::STATUS_PENDING,
        ]);

        $this->withToken($this->airbnbToken())
            ->postJson("/api/internal/airbnb/properties/{$property->uuid}/claim", [
                'external_property_id' => 'airbnb-property-open-charge',
            ])
            ->assertUnprocessable();
    }

    public function test_financial_events_are_idempotent_by_external_event_id(): void
    {
        $property = $this->createProperty('Casa Financiera');
        $token = $this->airbnbToken();

        $payload = [
            'property_uuid' => $property->uuid,
            'external_event_id' => 'reservation-payment-1',
            'event_type' => 'reservation_payment',
            'external_reservation_id' => 'RSV-001',
            'amount' => 1250.50,
            'currency' => 'mxn',
            'occurred_on' => now()->toDateString(),
            'payload' => ['source' => 'airbnbsu'],
        ];

        $this->withToken($token)
            ->postJson('/api/internal/airbnb/financial-events', $payload)
            ->assertCreated()
            ->assertJsonPath('data.created', true);

        $this->withToken($token)
            ->postJson('/api/internal/airbnb/financial-events', array_merge($payload, ['amount' => 1300]))
            ->assertOk()
            ->assertJsonPath('data.created', false);

        $this->assertSame(1, AirbnbFinancialEvent::query()->count());
        $this->assertSame('1300.00', (string) AirbnbFinancialEvent::query()->firstOrFail()->amount);
    }

    private function airbnbToken(): string
    {
        $client = IntegrationClient::query()->create([
            'name' => 'Airbnb SU',
            'key' => 'airbnbsu',
        ]);

        return $client->createToken('airbnb-su', ['airbnb'])->plainTextToken;
    }

    private function createProperty(string $name): Property
    {
        $type = PropertyType::query()->create([
            'name' => 'Casa',
            'slug' => 'casa-'.str()->random(6),
            'is_active' => true,
        ]);
        $zone = Zone::query()->create([
            'name' => 'Centro',
            'slug' => 'centro-'.str()->random(6),
            'is_active' => true,
        ]);

        return Property::query()->create([
            'internal_name' => $name,
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => $name.' 123',
            'status' => Property::STATUS_AVAILABLE,
            'onboarding_step' => 5,
        ]);
    }
}
