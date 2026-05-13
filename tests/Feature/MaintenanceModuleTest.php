<?php

namespace Tests\Feature;

use App\Models\MaintenanceProvider;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaintenanceModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_index_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('maintenance.index'));

        $response->assertOk();
        $response->assertSee('Mantenimiento');
    }

    public function test_ticket_can_be_created_with_multiple_files(): void
    {
        Storage::fake('public');
        Mail::fake();

        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);

        $response = $this
            ->actingAs($user)
            ->post(route('maintenance.store'), [
                'property_id' => $property->id,
                'category' => 'plomeria',
                'priority' => 'alta',
                'title' => 'Fuga en baño principal',
                'reference' => 'REP-001',
                'exact_location' => 'Baño principal',
                'description' => 'Se detecta fuga constante en lavabo',
                'additional_notes' => 'Urgente por filtración',
                'reported_at' => now()->format('Y-m-d H:i:s'),
                'files' => [
                    UploadedFile::fake()->image('foto1.jpg'),
                    UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4'),
                ],
            ]);

        $ticket = MaintenanceTicket::query()->where('title', 'Fuga en baño principal')->first();
        $this->assertNotNull($ticket);
        $response->assertRedirect(route('maintenance.show', $ticket));
        $this->assertDatabaseHas('maintenance_tickets', [
            'id' => $ticket->id,
            'property_id' => $property->id,
            'status' => 'pendiente',
        ]);
        $this->assertDatabaseCount('maintenance_ticket_files', 2);
        $this->assertDatabaseHas('maintenance_ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'to_status' => 'pendiente',
        ]);
    }

    public function test_tenant_can_create_ticket_with_minimal_fields(): void
    {
        Storage::fake('public');
        Mail::fake();

        Role::query()->firstOrCreate(['name' => 'inquilino', 'guard_name' => 'web']);
        $tenantUser = User::factory()->create(['email' => 'inquilino@example.com']);
        $tenantUser->assignRole('inquilino');
        $tenant = Tenant::create([
            'full_name' => 'Inquilino Prueba',
            'phone_primary' => '9991112233',
            'email' => 'inquilino@example.com',
            'dossier_status' => Tenant::DOSSIER_INCOMPLETE,
            'is_active' => true,
        ]);
        $property = $this->createPropertyFixture($tenantUser);
        $property->update([
            'tenant_id' => $tenant->id,
            'current_tenant_name' => $tenant->full_name,
        ]);

        $response = $this
            ->actingAs($tenantUser)
            ->post(route('maintenance.store'), [
                'property_id' => $property->id,
                'title' => 'No hay agua caliente',
                'files' => [
                    UploadedFile::fake()->image('evidencia.jpg'),
                ],
            ]);

        $ticket = MaintenanceTicket::query()->where('title', 'No hay agua caliente')->first();
        $this->assertNotNull($ticket);
        $response->assertRedirect(route('maintenance.show', $ticket));
        $this->assertSame('media', $ticket->priority);
        $this->assertSame('pendiente', $ticket->status);
        $this->assertDatabaseCount('maintenance_ticket_files', 1);
    }

    public function test_ticket_can_be_assigned_and_completed(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);
        $ticket = MaintenanceTicket::create([
            'property_id' => $property->id,
            'reported_by_user_id' => $user->id,
            'reported_by_role' => 'administrador',
            'reported_by_name' => $user->name,
            'category' => 'electricidad',
            'priority' => 'media',
            'status' => 'pendiente',
            'title' => 'Luminaria sin energía',
            'exact_location' => 'Sala',
            'description' => 'No enciende foco principal',
            'reported_at' => now(),
        ]);

        $provider = MaintenanceProvider::create([
            'type' => 'tecnico_interno',
            'name' => 'Técnico 01',
            'email' => 'tecnico01@example.com',
            'specialty' => 'Electricidad',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $assignResponse = $this
            ->actingAs($user)
            ->post(route('maintenance.assign', $ticket), [
                'provider_id' => $provider->id,
                'notes' => 'Asignación inicial',
            ]);

        $assignResponse->assertRedirect();
        $this->assertDatabaseHas('maintenance_ticket_assignments', [
            'ticket_id' => $ticket->id,
            'provider_id' => $provider->id,
            'is_current' => true,
        ]);

        $statusResponse = $this
            ->actingAs($user)
            ->patch(route('maintenance.status', $ticket), [
                'status' => 'completado',
                'notes' => 'Trabajo finalizado',
            ]);

        $statusResponse->assertRedirect();
        $ticket->refresh();
        $this->assertSame('completado', $ticket->status);
        $this->assertNotNull($ticket->completed_at);
        $this->assertDatabaseHas('maintenance_ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'to_status' => 'completado',
        ]);
    }

    private function createPropertyFixture(User $user): Property
    {
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);

        return Property::create([
            'internal_name' => 'Casa 10',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Principal 10',
            'status' => Property::STATUS_OCCUPIED,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);
    }
}
