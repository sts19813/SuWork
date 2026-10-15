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
                $technicians = $ticket->assignments
                    ->map(fn ($assignment) => $assignment->provider?->name)
                    ->filter()
                    ->push($ticket->currentProvider?->name)
                    ->filter()
                    ->unique()
                    ->values()
                    ->implode(', ');

                return [
                    'folio' => $ticket->reference ?: '#'.$ticket->id,
                    'property' => trim(implode(' · ', array_filter([
                        $ticket->property?->internal_name ?: 'Sin propiedad',
                        $ticket->property?->internal_reference,
                    ]))),
                    'title' => $ticket->title ?: 'Sin título',
                    'technicians' => $technicians ?: 'Sin asignar',
                    'status' => MaintenanceTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status,
                    'created_at' => $ticket->reported_at?->format('d/m/Y H:i') ?: '-',
                    'completed_at' => $ticket->completed_at?->format('d/m/Y H:i') ?: '-',
                ];
            })
            ->values()
            ->all();
    }
}
