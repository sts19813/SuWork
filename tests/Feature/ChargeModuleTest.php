<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChargeModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_charges_index_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('charges.index'));

        $response->assertOk();
        $response->assertSee('Cobranza');
    }

    public function test_charge_can_be_created(): void
    {
        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);
        $tenant = Tenant::create([
            'full_name' => 'Ana Lucia Torres',
            'phone_primary' => '9991112233',
            'dossier_status' => Tenant::DOSSIER_COMPLETE,
            'is_active' => true,
        ]);

        $property = Property::create([
            'internal_name' => 'Casa Centro 201',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 20 #201',
            'status' => Property::STATUS_OCCUPIED,
            'tenant_id' => $tenant->id,
            'current_tenant_name' => $tenant->full_name,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('charges.store'), [
                'property_id' => $property->id,
                'tenant_id' => $tenant->id,
                'type' => Charge::TYPE_RENT,
                'due_date' => now()->addDays(7)->toDateString(),
                'amount' => 18000,
                'period_month' => 3,
                'period_year' => 2026,
                'concept' => 'Renta Marzo 2026',
                'notes' => 'Prueba modulo cobranza',
            ]);

        $response->assertRedirect(route('charges.index'));
        $this->assertDatabaseHas('charges', [
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'type' => Charge::TYPE_RENT,
            'concept' => 'Renta Marzo 2026',
            'status' => Charge::STATUS_PENDING,
        ]);
    }

    public function test_public_charge_link_is_accessible(): void
    {
        $charge = $this->createChargeFixture();

        $response = $this->get(route('charges.public.show', ['token' => $charge->payment_token]));

        $response->assertOk();
        $response->assertSee('Pagar con Stripe');
    }

    public function test_webhook_marks_charge_as_paid(): void
    {
        $charge = $this->createChargeFixture();
        config()->set('services.stripe.webhook_secret', '');

        $payload = [
            'id' => 'evt_test_charge_paid',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_charge_paid',
                    'payment_status' => 'paid',
                    'amount_total' => 1800000,
                    'currency' => 'mxn',
                    'created' => now()->timestamp,
                    'client_reference_id' => (string) $charge->id,
                    'payment_intent' => 'pi_test_123',
                    'metadata' => [
                        'charge_id' => (string) $charge->id,
                        'charge_token' => $charge->payment_token,
                    ],
                ],
            ],
        ];

        $response = $this->postJson(route('stripe.webhook'), $payload);

        $response->assertOk();
        $this->assertDatabaseHas('charges', [
            'id' => $charge->id,
            'status' => Charge::STATUS_PAID,
            'paid_amount' => 18000.00,
        ]);
        $this->assertDatabaseHas('charge_payments', [
            'charge_id' => $charge->id,
            'stripe_checkout_session_id' => 'cs_test_charge_paid',
            'status' => 'succeeded',
        ]);
    }

    private function createChargeFixture(): Charge
    {
        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Departamento', 'slug' => 'departamento', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Montebello', 'slug' => 'montebello', 'is_active' => true]);
        $tenant = Tenant::create([
            'full_name' => 'Roberto Canul',
            'phone_primary' => '9994445566',
            'email' => 'roberto@example.com',
            'dossier_status' => Tenant::DOSSIER_COMPLETE,
            'is_active' => true,
        ]);

        $property = Property::create([
            'internal_name' => 'Departamento 12',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 40 #12',
            'status' => Property::STATUS_OCCUPIED,
            'tenant_id' => $tenant->id,
            'current_tenant_name' => $tenant->full_name,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);

        return Charge::create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'type' => Charge::TYPE_RENT,
            'due_date' => now()->toDateString(),
            'amount' => 18000,
            'paid_amount' => 0,
            'period_month' => 3,
            'period_year' => 2026,
            'concept' => 'Renta Marzo 2026',
            'status' => Charge::STATUS_PENDING,
            'created_by' => $user->id,
        ]);
    }
}
