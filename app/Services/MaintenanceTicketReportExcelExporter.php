<?php

namespace App\Services;

use Carbon\CarbonInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MaintenanceTicketReportExcelExporter
{
    /**
     * @param  array<int, array<string, string>>  $rows
     */
    public function generate(array $rows, string $filterSummary, CarbonInterface $generatedAt): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tickets de mantenimiento');

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'Reporte de tickets de mantenimiento');
        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'Generado el '.$generatedAt->format('d/m/Y H:i').' · '.$filterSummary);
        $sheet->fromArray(['Folio', 'Propiedad', 'Solicitud', 'Técnico(s)', 'Estatus', 'Creación', 'Completado'], null, 'A4');

        foreach ($rows as $index => $row) {
            $sheet->fromArray([
                $row['folio'],
                $row['property'],
                $row['title'],
                $row['technicians'],
                $row['status'],
                $row['created_at'],
                $row['completed_at'],
            ], null, 'A'.($index + 5));
        }

        $lastRow = max(5, count($rows) + 4);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('1F4E78');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('4B5563');
        $sheet->getStyle('A4:G4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A4:G4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E78');
        $sheet->getStyle('A1:G'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        $sheet->getStyle('A4:G4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A5');
        $sheet->setAutoFilter('A4:G'.$lastRow);
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(30);

        foreach (['A' => 17, 'B' => 28, 'C' => 42, 'D' => 28, 'E' => 18, 'F' => 19, 'G' => 19] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        return $spreadsheet;
    }
}
