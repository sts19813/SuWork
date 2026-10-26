<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 18px 20px; }
        * { box-sizing: border-box; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 8px; }
        h1 { color: #1f4e78; font-size: 17px; margin: 0 0 4px; }
        .subtitle { color: #64748b; margin: 0 0 16px; }
        .metrics { margin: 0 0 18px; width: 100%; }
        .metrics td { background: #eff6ff; border: 1px solid #d6dee7; color: #1f4e78; font-weight: bold; padding: 7px 9px; text-align: center; }
        h2 { color: #1f4e78; font-size: 11px; margin: 18px 0 7px; }
        table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        th { background: #1f4e78; border: 1px solid #1f4e78; color: #fff; font-size: 7px; padding: 6px 4px; text-align: left; }
        td { border: 1px solid #d6dee7; padding: 5px 4px; vertical-align: top; word-wrap: break-word; }
        .status-complete { background: #e8f5e9; color: #166534; font-weight: bold; }
        .status-pending { background: #fdecec; color: #991b1b; font-weight: bold; }
        .muted { color: #64748b; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <h1>Control de documentos PDF por propiedad</h1>
    <p class="subtitle">Generado el {{ $generatedAt->format('d/m/Y H:i') }}. Incluye únicamente documentos actuales cargados en PDF.</p>

    <table class="metrics">
        <tr>
            <td>Propiedades<br><strong>{{ $summary['properties'] }}</strong></td>
            <td>Con PDF<br><strong>{{ $summary['properties_with_pdf'] }}</strong></td>
            <td>Sin PDF<br><strong>{{ $summary['properties'] - $summary['properties_with_pdf'] }}</strong></td>
            <td>PDFs cargados<br><strong>{{ $summary['pdfs_loaded'] }}</strong></td>
        </tr>
    </table>

    <h2>Resumen por propiedad</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 18%">Propiedad</th>
                <th style="width: 11%">Referencia</th>
                <th style="width: 7%">PDFs</th>
                <th style="width: 12%">Requeridos PDF</th>
                <th style="width: 14%">Estado</th>
                <th style="width: 38%">Documentos requeridos sin PDF</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summaryRows as $row)
                <tr>
                    <td>{{ $row['property']->internal_name }}</td>
                    <td>{{ $row['property']->internal_reference ?: '-' }}</td>
                    <td>{{ $row['pdf_count'] }}</td>
                    <td>{{ $row['required_pdf_count'] }} de {{ $row['required_total'] }}</td>
                    <td class="{{ $row['missing_labels'] === '' ? 'status-complete' : 'status-pending' }}">
                        {{ $row['missing_labels'] === '' ? 'Completo en PDF' : 'Pendiente' }}
                    </td>
                    <td>{{ $row['missing_labels'] ?: 'Sin faltantes configurados' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>
    <h2>PDFs actualmente cargados</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 19%">Propiedad</th>
                <th style="width: 10%">Referencia</th>
                <th style="width: 20%">Documento</th>
                <th style="width: 11%">Clasificación</th>
                <th style="width: 12%">Estado</th>
                <th style="width: 20%">Archivo PDF</th>
                <th style="width: 8%">Cargado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($documentRows as $row)
                <tr>
                    <td>{{ $row['property']->internal_name }}</td>
                    <td>{{ $row['property']->internal_reference ?: '-' }}</td>
                    <td>{{ $row['document']->label ?: $row['document']->document_type }}</td>
                    <td>{{ $row['is_required'] ? 'Requerido' : 'Adicional' }}</td>
                    <td>{{ $row['document']->status_label }}</td>
                    <td>{{ $row['file_name'] }}</td>
                    <td>{{ $row['uploaded_at']?->format('d/m/Y') ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted">No hay documentos PDF cargados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
