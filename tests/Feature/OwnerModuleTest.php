<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owners_index_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('owners.index'));

        $response->assertOk();
        $response->assertSee('Propietarios');
    }

    public function test_owner_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('owners.store'), [
                'name' => 'Carlos Mendoza',
                'phone' => '9991234567',
                'email' => 'carlos@example.com',
                'owner_type' => Owner::OWNER_INDIVIDUAL,
                'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
                'bank_name' => 'BBVA',
                'clabe' => '012180015312345678',
            ]);

        $response->assertRedirect(route('owners.index'));
        $this->assertDatabaseHas('owners', ['email' => 'carlos@example.com']);
    }

    public function test_owner_can_be_updated(): void
    {
        $user = User::factory()->create();
        $owner = Owner::create([
            'name' => 'Sofia Herrera',
            'phone' => '9997654321',
            'email' => 'sofia@example.com',
            'owner_type' => Owner::OWNER_INDIVIDUAL,
            'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('owners.update', $owner), [
                'name' => 'Sofia Herrera del Valle',
                'phone' => '9997654321',
                'email' => 'sofia@example.com',
                'owner_type' => Owner::OWNER_INDIVIDUAL,
                'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
            ]);

        $response->assertRedirect(route('owners.index'));
        $this->assertDatabaseHas('owners', ['id' => $owner->id, 'name' => 'Sofia Herrera del Valle']);
    }
}

