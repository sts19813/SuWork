<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('expenses.index'));

        $response->assertOk();
        $response->assertSee('Gastos');
    }

    public function test_expense_can_be_created_with_attachments(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);

        $response = $this
            ->actingAs($user)
            ->from(route('expenses.index'))
            ->post(route('expenses.store'), [
                'property_id' => $property->id,
                'concept' => 'Mantenimiento elevador',
                'amount' => 2500,
                'due_date' => now()->addDays(3)->toDateString(),
                'description' => 'Servicio mensual',
                'files' => [
                    UploadedFile::fake()->image('elevador.jpg'),
                    UploadedFile::fake()->create('factura.pdf', 120, 'application/pdf'),
                ],
            ]);

        $response->assertRedirect(route('expenses.index'));
        $this->assertDatabaseHas('expenses', [
            'property_id' => $property->id,
            'concept' => 'Mantenimiento elevador',
            'description' => 'Servicio mensual',
        ]);
        $this->assertDatabaseCount('expense_files', 2);
    }

    public function test_expense_can_be_marked_as_paid(): void
    {
        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);

        $expense = Expense::create([
            'property_id' => $property->id,
            'concept' => 'Internet',
            'amount' => 800,
            'due_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('expenses.index'))
            ->post(route('expenses.mark-paid', $expense));

        $response->assertRedirect(route('expenses.index'));
        $this->assertNotNull($expense->fresh()->paid_at);
    }

    public function test_property_expense_notification_setup_can_be_customized(): void
    {
        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);

        $response = $this
            ->actingAs($user)
            ->put(route('expenses.properties.setup', $property), [
                'use_global_setup' => 0,
                'days_before' => 5,
                'emails' => 'admin@example.com, pagos@example.com',
                'phones' => '9991234567',
            ]);

        $response->assertRedirect(route('properties.show', $property) . '#tab-expenses');
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'use_global_expense_notifications' => false,
            'expense_notification_days_before' => 5,
        ]);

        $property->refresh();
        $this->assertSame(['admin@example.com', 'pagos@example.com'], $property->expense_notification_emails);
        $this->assertSame(['9991234567'], $property->expense_notification_phones);
    }

    private function createPropertyFixture(User $user): Property
    {
        $type = PropertyType::create(['name' => 'Departamento', 'slug' => 'departamento', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Montebello', 'slug' => 'montebello', 'is_active' => true]);

        return Property::create([
            'internal_name' => 'Depto 301',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 1 #301',
            'status' => Property::STATUS_OCCUPIED,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);
    }
}
