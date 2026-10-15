<?php

namespace App\Services;

use App\Models\MaintenanceTicket;
use Illuminate\Support\Collection;

class MaintenanceTicketReportData
{
    /**
     * @param  Collection<int, MaintenanceTicket>  $tickets
     * @return array<int, array<string, string>>
     */
    public function rows(Collection $tickets): array
    {
        return $tickets
            ->map(function (MaintenanceTicket $ticket): array {
                return [
                    'folio' => $ticket->reference ?: '#'.$ticket->id,
                    'property' => trim(implode(' · ', array_filter([
                        $ticket->property?->internal_name ?: 'Sin propiedad',
                        $ticket->property?->internal_reference,
                    ]))),
                    'title' => $ticket->title ?: 'Sin título',
                    'technician' => $ticket->currentProvider?->name ?: 'Sin asignar',
                    'status' => $ticket->status === 'completado' && $ticket->cutItem?->cut
                        ? 'Pagado'
                        : (MaintenanceTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status),
                    'created_at' => $ticket->reported_at?->format('d/m/Y') ?: '-',
                    'completed_at' => $ticket->completed_at?->format('d/m/Y') ?: '-',
                    'cut' => $ticket->cutItem?->cut
                        ? $ticket->cutItem->cut->display_reference."\n"
                            .$ticket->cutItem->cut->paid_at?->format('d/m/Y')."\n$"
                            .number_format((float) $ticket->cutItem->grand_total, 2)
                        : '-',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, MaintenanceTicket>  $tickets
     * @return array<int, array{label: string, value: string}>|null
     */
    public function indicators(Collection $tickets): ?array
    {
        $completedTickets = $tickets
            ->filter(fn (MaintenanceTicket $ticket): bool => $ticket->status === 'completado')
            ->values();
        $paidTickets = $completedTickets
            ->filter(fn (MaintenanceTicket $ticket): bool => $ticket->cutItem?->cut !== null)
            ->values();

        if ($paidTickets->isEmpty()) {
            return null;
        }

        $pendingTickets = $completedTickets
            ->reject(fn (MaintenanceTicket $ticket): bool => $ticket->cutItem?->cut !== null)
            ->values();
        $laborPending = (float) $pendingTickets->sum(fn (MaintenanceTicket $ticket): float => (float) ($ticket->labor_total ?? 0));
        $materialsPending = (float) $pendingTickets->sum(fn (MaintenanceTicket $ticket): float => (float) ($ticket->material_total ?? 0));

        return [
            ['label' => 'Total de tickets pagados', 'value' => (string) $paidTickets->count()],
            ['label' => 'Mano de obra pendiente', 'value' => '$'.number_format($laborPending, 2)],
            ['label' => 'Materiales pendientes', 'value' => '$'.number_format($materialsPending, 2)],
            ['label' => 'Total', 'value' => '$'.number_format($laborPending + $materialsPending, 2)],
        ];
    }
}
