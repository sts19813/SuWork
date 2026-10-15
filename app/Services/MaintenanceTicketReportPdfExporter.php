<?php

namespace App\Services;

use Carbon\CarbonInterface;
use TCPDF;

class MaintenanceTicketReportPdfExporter
{
    /**
     * @param  array<int, array<string, string>>  $rows
     */
    public function generate(array $rows, string $filterSummary, CarbonInterface $generatedAt): string
    {
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(config('app.name'));
        $pdf->SetAuthor(config('app.name'));
        $pdf->SetTitle('Reporte de tickets de mantenimiento');
        $pdf->SetMargins(10, 12, 10);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->AddPage();

        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 8, 'Reporte de tickets de mantenimiento', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->MultiCell(0, 5, 'Generado el '.$generatedAt->format('d/m/Y H:i').' · '.$filterSummary, 0, 'L', false, 1);
        $pdf->Ln(2);

        $html = '<table border="1" cellpadding="4" cellspacing="0" style="font-size:7.5pt;">'
            .'<thead><tr style="background-color:#1f4e78;color:#ffffff;font-weight:bold;">'
            .'<th width="9%">Folio</th><th width="17%">Propiedad</th><th width="27%">Solicitud</th>'
            .'<th width="16%">Técnico asignado</th><th width="10%">Estatus</th><th width="11%">Creación</th><th width="10%">Completado</th>'
            .'</tr></thead><tbody>';

        if ($rows === []) {
            $html .= '<tr><td colspan="7" align="center">No se encontraron tickets con los filtros seleccionados.</td></tr>';
        } else {
            foreach ($rows as $row) {
                $html .= '<tr>'
                    .'<td>'.$this->escape($row['folio']).'</td>'
                    .'<td>'.$this->escape($row['property']).'</td>'
                    .'<td>'.$this->escape($row['title']).'</td>'
                    .'<td>'.$this->escape($row['technician']).'</td>'
                    .'<td>'.$this->escape($row['status']).'</td>'
                    .'<td>'.$this->escape($row['created_at']).'</td>'
                    .'<td>'.$this->escape($row['completed_at']).'</td>'
                    .'</tr>';
            }
        }

        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('', 'S');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
