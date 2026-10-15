<?php

namespace Tests\Feature;

use App\Models\MaintenanceCut;
use App\Models\MaintenanceProvider;
use App\Models\MaintenanceTicket;
use App\Models\MaintenanceTicketAssignment;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaintenanceTicketReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_export_tickets_for_their_currently_assigned_technician_as_pdf_and_excel(): void
    {
        $administrator = $this->administrator();
        $property = $this->createProperty($administrator);
        $departingTechnician = MaintenanceProvider::create([
            'type' => 'tecnico_interno',
            'name' => 'Alejandro Técnico',
            'is_active' => false,
        ]);
        $replacementTechnician = MaintenanceProvider::create([
            'type' => 'tecnico_interno',
            'name' => 'Allison Técnica',
            'is_active' => true,
        ]);

        $matchingTicket = $this->createTicket($administrator, $property, $departingTechnician, 'Trabajo actual de Alejandro', 'completado');
        $cut = MaintenanceCut::create([
            'paid_by_user_id' => $administrator->id,
            'ticket_count' => 1,
            'labor_total' => 600,
            'material_total' => 300,
            'grand_total' => 900,
            'paid_at' => now()->subDay(),
        ]);
        $cut->items()->create([
            'ticket_id' => $matchingTicket->id,
            'labor_total' => 600,
            'material_total' => 300,
            'grand_total' => 900,
        ]);
        $historicalTicket = $this->createTicket($administrator, $property, $replacementTechnician, 'Trabajo ya reasignado a Allison', 'completado');
        MaintenanceTicketAssignment::create([
            'ticket_id' => $historicalTicket->id,
            'provider_id' => $departingTechnician->id,
            'assigned_by_user_id' => $administrator->id,
            'assigned_at' => now()->subDays(3),
            'unassigned_at' => now()->subDays(2),
            'is_current' => false,
        ]);

        $query = [
            'technician_ids' => [$departingTechnician->id],
            'statuses' => ['completado'],
            'sort_completed' => 'completed_desc',
            'date_from' => now()->subMonth()->toDateString(),
            'date_to' => now()->toDateString(),
        ];

        $pdf = $this->actingAs($administrator)
            ->get(route('maintenance.export.pdf', $query));

        $pdf->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $excel = $this->actingAs($administrator)
            ->get(route('maintenance.export.excel', $query));

        $excel->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $excelContent = $excel->streamedContent();
        $this->assertStringStartsWith('PK', $excelContent);

        $temporaryFile = tempnam(sys_get_temp_dir(), 'maintenance-report-');
        file_put_contents($temporaryFile, $excelContent);
        try {
            $sheet = (new Xlsx)->load($temporaryFile)->getActiveSheet();
            $this->assertSame('Total de tickets pagados', $sheet->getCell('A4')->getValue());
            $this->assertSame('1', (string) $sheet->getCell('A5')->getValue());
            $this->assertSame('Mano de obra pagada', $sheet->getCell('C4')->getValue());
            $this->assertSame('$600.00', $sheet->getCell('C5')->getValue());
            $this->assertSame('Materiales pagados', $sheet->getCell('E4')->getValue());
            $this->assertSame('$300.00', $sheet->getCell('E5')->getValue());
            $this->assertSame('Total pagado', $sheet->getCell('G4')->getValue());
            $this->assertSame('$900.00', $sheet->getCell('G5')->getValue());
            $this->assertSame('Alejandro Técnico', $sheet->getCell('D8')->getValue());
            $this->assertSame('Trabajo actual de Alejandro', $sheet->getCell('C8')->getValue());
            $this->assertSame('Completado - Pagado', $sheet->getCell('E8')->getValue());
            $this->assertStringContainsString($cut->display_reference, (string) $sheet->getCell('H8')->getValue());
            $this->assertSame(8, $sheet->getHighestRow());
        } finally {
            @unlink($temporaryFile);
        }
    }

    public function test_only_administrators_see_and_can_use_maintenance_report_exports(): void
    {
        $administrator = $this->administrator();
        $technician = User::factory()->create(['is_active' => true]);
        $technician->assignRole(Role::findOrCreate('tecnico', 'web'));

        $this->actingAs($administrator)
            ->get(route('maintenance.index'))
            ->assertOk()
            ->assertSee('Exportar reporte')
            ->assertSee('maintenance-report-technicians');

        $this->actingAs($technician)
            ->get(route('maintenance.index'))
            ->assertOk()
            ->assertDontSee('Exportar reporte')
            ->assertDontSee('maintenance-report-technicians');

        $this->actingAs($technician)
            ->get(route('maintenance.export.pdf'))
            ->assertForbidden();
    }

    public function test_administrator_can_order_reports_by_completion_date(): void
    {
        $administrator = $this->administrator();
        $property = $this->createProperty($administrator);
        $technician = MaintenanceProvider::create([
            'type' => 'tecnico_interno',
            'name' => 'Técnico de orden',
            'is_active' => true,
        ]);
        $oldest = $this->createTicket($administrator, $property, $technician, 'Ticket completado primero', 'completado');
        $oldest->update(['completed_at' => now()->subDays(5)]);
        $mostRecent = $this->createTicket($administrator, $property, $technician, 'Ticket completado recientemente', 'completado');
        $mostRecent->update(['completed_at' => now()->subDay()]);

        $baseQuery = [
            'technician_ids' => [$technician->id],
            'statuses' => ['completado'],
        ];

        $this->assertSame('Ticket completado recientemente', $this->firstExcelTicketTitle($administrator, $baseQuery + [
            'sort_completed' => 'completed_desc',
        ]));
        $this->assertSame('Ticket completado primero', $this->firstExcelTicketTitle($administrator, $baseQuery + [
            'sort_completed' => 'completed_asc',
        ]));
    }

    public function test_pending_ticket_report_does_not_show_payment_indicators(): void
    {
        $administrator = $this->administrator();
        $property = $this->createProperty($administrator);
        $technician = MaintenanceProvider::create([
            'type' => 'tecnico_interno',
            'name' => 'Técnico pendiente',
            'is_active' => true,
        ]);
        $this->createTicket($administrator, $property, $technician, 'Ticket pendiente', 'pendiente');

        $response = $this->actingAs($administrator)->get(route('maintenance.export.excel', [
            'technician_ids' => [$technician->id],
            'statuses' => ['pendiente'],
        ]));
        $response->assertOk();

        $temporaryFile = tempnam(sys_get_temp_dir(), 'maintenance-report-');
        file_put_contents($temporaryFile, $response->streamedContent());
        try {
            $sheet = (new Xlsx)->load($temporaryFile)->getActiveSheet();
            $this->assertSame('Folio', $sheet->getCell('A4')->getValue());
            $this->assertSame('Ticket pendiente', $sheet->getCell('C5')->getValue());
        } finally {
            @unlink($temporaryFile);
        }
    }

    private function administrator(): User
    {
        $administrator = User::factory()->create(['is_active' => true]);
        $administrator->assignRole(Role::findOrCreate('administrador', 'web'));

        return $administrator;
    }

    private function createProperty(User $user): Property
    {
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);

        return Property::create([
            'internal_name' => 'Casa del reporte',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Principal 10',
            'status' => Property::STATUS_OCCUPIED,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);
    }

    private function createTicket(User $user, Property $property, MaintenanceProvider $provider, string $title, string $status): MaintenanceTicket
    {
        return MaintenanceTicket::create([
            'property_id' => $property->id,
            'reported_by_user_id' => $user->id,
            'current_provider_id' => $provider->id,
            'reported_by_role' => 'administrador',
            'reported_by_name' => $user->name,
            'category' => 'plomeria',
            'priority' => 'alta',
            'status' => $status,
            'title' => $title,
            'exact_location' => 'Cocina',
            'description' => 'Descripción de prueba',
            'reported_at' => now()->subDays(5),
            'completed_at' => $status === 'completado' ? now()->subDay() : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function firstExcelTicketTitle(User $administrator, array $query): string
    {
        $response = $this->actingAs($administrator)->get(route('maintenance.export.excel', $query));
        $response->assertOk();

        $temporaryFile = tempnam(sys_get_temp_dir(), 'maintenance-report-');
        file_put_contents($temporaryFile, $response->streamedContent());
        try {
            return (string) (new Xlsx)->load($temporaryFile)->getActiveSheet()->getCell('C5')->getValue();
        } finally {
            @unlink($temporaryFile);
        }
    }
}
