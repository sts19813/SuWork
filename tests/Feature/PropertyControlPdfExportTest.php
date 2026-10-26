<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\PropertyType;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PropertyControlPdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_control_permission_can_export_current_property_pdfs_and_see_missing_required_pdfs(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('propiedades.control_ver', 'web');
        $user->givePermissionTo('propiedades.control_ver');
        $property = $this->createProperty($user);

        PropertyDocument::create([
            'property_id' => $property->id,
            'document_type' => 'contract',
            'label' => 'Contrato',
            'file_path' => 'properties/'.$property->id.'/documents/contrato.pdf',
            'status' => PropertyDocument::STATUS_UPLOADED,
            'uploaded_at' => now(),
        ]);
        PropertyDocument::create([
            'property_id' => $property->id,
            'document_type' => 'property_tax',
            'label' => 'Predial',
            'file_path' => 'properties/'.$property->id.'/documents/predial.jpg',
            'status' => PropertyDocument::STATUS_UPLOADED,
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('properties.control.documents-pdf.export'));

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $temporaryFile = tempnam(sys_get_temp_dir(), 'property-pdf-export-');
        file_put_contents($temporaryFile, $response->streamedContent());

        try {
            $workbook = (new Xlsx)->load($temporaryFile);
            $summary = $workbook->getSheetByName('Resumen por propiedad');
            $documents = $workbook->getSheetByName('PDFs cargados');

            $this->assertNotNull($summary);
            $this->assertNotNull($documents);
            $this->assertSame('Casa PDF', $summary->getCell('A8')->getValue());
            $this->assertSame(1, (int) $summary->getCell('E8')->getValue());
            $this->assertSame('Contrato', $summary->getCell('F8')->getValue());
            $this->assertSame('1 de 2', $summary->getCell('G8')->getValue());
            $this->assertSame('Pendiente', $summary->getCell('H8')->getValue());
            $this->assertSame('Predial', $summary->getCell('I8')->getValue());
            $this->assertStringContainsString('/propiedades/'.$property->uuid.'/expediente', $summary->getCell('J8')->getHyperlink()->getUrl());

            $this->assertSame('Casa PDF', $documents->getCell('A5')->getValue());
            $this->assertSame('Contrato', $documents->getCell('C5')->getValue());
            $this->assertSame('Requerido', $documents->getCell('D5')->getValue());
            $this->assertSame('contrato.pdf', $documents->getCell('F5')->getValue());
            $this->assertSame(5, $documents->getHighestRow());
        } finally {
            @unlink($temporaryFile);
        }
    }

    public function test_user_without_control_permission_cannot_export_property_pdfs(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('properties.control.documents-pdf.export'))
            ->assertForbidden();
    }

    private function createProperty(User $user): Property
    {
        $type = PropertyType::query()->create([
            'name' => 'Casa PDF',
            'slug' => 'casa-pdf',
            'is_active' => true,
        ]);
        $zone = Zone::query()->create([
            'name' => 'Zona PDF',
            'slug' => 'zona-pdf',
            'is_active' => true,
        ]);

        return Property::query()->create([
            'internal_name' => 'Casa PDF',
            'internal_reference' => 'PDF-01',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle PDF 1',
            'status' => Property::STATUS_AVAILABLE,
            'monthly_rent_price' => 10000,
            'created_by' => $user->id,
        ]);
    }
}
