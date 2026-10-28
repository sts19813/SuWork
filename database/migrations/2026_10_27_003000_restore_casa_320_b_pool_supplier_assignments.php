<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TICKET_REFERENCES = ['00000736', '00000739', '00000745'];

    public function up(): void
    {
        $now = now();
        $tickets = DB::table('maintenance_tickets as ticket')
            ->join('properties as property', 'property.id', '=', 'ticket.property_id')
            ->where('property.internal_name', 'CASA 320 B')
            ->whereIn('ticket.reference', self::TICKET_REFERENCES)
            ->select('ticket.id', 'ticket.current_provider_id')
            ->get();

        foreach ($tickets as $ticket) {
            $supplierId = DB::table('maintenance_ticket_assignments as assignment')
                ->join('maintenance_providers as provider', 'provider.id', '=', 'assignment.provider_id')
                ->where('assignment.ticket_id', $ticket->id)
                ->where('provider.type', 'proveedor')
                ->orderBy('assignment.assigned_at')
                ->value('assignment.provider_id');

            if (! $supplierId || (int) $ticket->current_provider_id === (int) $supplierId) {
                continue;
            }

            DB::table('maintenance_ticket_assignments')
                ->where('ticket_id', $ticket->id)
                ->where('is_current', true)
                ->update([
                    'is_current' => false,
                    'unassigned_at' => $now,
                ]);

            DB::table('maintenance_ticket_assignments')->insert([
                'ticket_id' => $ticket->id,
                'provider_id' => $supplierId,
                'assigned_by_user_id' => null,
                'notes' => 'Corrección: se restaura el proveedor externo original del ticket.',
                'assigned_at' => $now,
                'unassigned_at' => null,
                'is_current' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('maintenance_tickets')
                ->where('id', $ticket->id)
                ->update([
                    'current_provider_id' => $supplierId,
                    'assigned_at' => $now,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        // This data correction deliberately preserves the restored supplier assignment.
    }
};
