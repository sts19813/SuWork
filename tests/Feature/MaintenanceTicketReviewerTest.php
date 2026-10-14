<?php

namespace Tests\Feature;

use App\Models\MaintenanceReviewerAccess;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaintenanceTicketReviewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewer_only_sees_tickets_in_its_relative_date_range_and_can_validate_them(): void
    {
        $this->travelTo('2026-10-14 12:00:00');
        $reviewer = User::factory()->create(['is_active' => true]);
        $role = Role::findOrCreate('revisor de tickets', 'web');
        $role->syncPermissions([
            Permission::findOrCreate('mantenimiento.consultar_tickets', 'web'),
            Permission::findOrCreate('mantenimiento.validar_tickets', 'web'),
        ]);
        $reviewer->assignRole($role);
        MaintenanceReviewerAccess::create([
            'user_id' => $reviewer->id,
            'range_type' => 'relative',
            'days_before' => 30,
            'days_after' => 10,
        ]);

        $property = $this->createProperty($reviewer);
        $visibleTicket = $this->createTicket($reviewer, $property, 'Ticket visible', now()->subDays(30));
        $visibleTicket->update(['status' => 'completado', 'completed_at' => now()->subDays(1)]);
        $hiddenTicket = $this->createTicket($reviewer, $property, 'Ticket fuera de rango', now()->subDays(31));
        $activeTicket = $this->createTicket($reviewer, $property, 'Ticket activo', now()->subDays(1));
        $canceledTicket = $this->createTicket($reviewer, $property, 'Ticket cancelado', now()->subDays(1));
        $canceledTicket->update(['status' => 'cancelado', 'canceled_at' => now()->subDay()]);

        $this->actingAs($reviewer)
            ->get(route('maintenance.index', ['tab' => 'activos']))
            ->assertOk()
            ->assertSee('Ticket visible')
            ->assertDontSee('Ticket fuera de rango')
            ->assertDontSee('Ticket activo')
            ->assertDontSee('Ticket cancelado')
            ->assertDontSee('>Activos<', false)
            ->assertDontSee('>Cancelados<', false)
            ->assertSee('Marcar como validados');

        $this->actingAs($reviewer)
            ->get(route('maintenance.show', $hiddenTicket))
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->patch(route('maintenance.validate'), ['ticket_uuids' => [$visibleTicket->uuid]])
            ->assertRedirect();

        $this->assertDatabaseHas('maintenance_ticket_validations', [
            'ticket_id' => $visibleTicket->id,
            'validated_by_user_id' => $reviewer->id,
        ]);
    }

    public function test_reviewer_is_blocked_from_other_modules_and_ticket_mutations(): void
    {
        $reviewer = User::factory()->create(['is_active' => true]);
        $role = Role::findOrCreate('revisor de tickets', 'web');
        $role->givePermissionTo(Permission::findOrCreate('mantenimiento.validar_tickets', 'web'));
        $reviewer->assignRole($role);
        MaintenanceReviewerAccess::create([
            'user_id' => $reviewer->id,
            'range_type' => 'relative',
            'days_before' => 30,
            'days_after' => 0,
        ]);

        $property = $this->createProperty($reviewer);
        $ticket = $this->createTicket($reviewer, $property, 'Ticket protegido', now());

        $this->actingAs($reviewer)->get(route('properties.index'))->assertForbidden();
        $this->actingAs($reviewer)->post(route('maintenance.files', $ticket), [])->assertForbidden();
        $this->actingAs($reviewer)->patch(route('maintenance.status', $ticket), ['status' => 'completado'])->assertForbidden();
    }

    public function test_reviewer_is_sent_to_maintenance_after_login(): void
    {
        $reviewer = User::factory()->create([
            'email' => 'revisor-login@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $reviewer->assignRole(Role::findOrCreate('revisor de tickets', 'web'));

        $this->post(route('login'), [
            'email' => $reviewer->email,
            'password' => 'password',
        ])->assertRedirect(route('maintenance.index'));
    }

    private function createProperty(User $user): Property
    {
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);

        return Property::create([
            'internal_name' => 'Casa de revisión',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Principal 10',
            'status' => Property::STATUS_OCCUPIED,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);
    }

    private function createTicket(User $user, Property $property, string $title, $reportedAt): MaintenanceTicket
    {
        return MaintenanceTicket::create([
            'property_id' => $property->id,
            'reported_by_user_id' => $user->id,
            'reported_by_role' => 'administrador',
            'reported_by_name' => $user->name,
            'category' => 'plomeria',
            'priority' => 'alta',
            'status' => 'pendiente',
            'title' => $title,
            'exact_location' => 'Cocina',
            'description' => 'Descripción de prueba',
            'reported_at' => $reportedAt,
        ]);
    }
}
