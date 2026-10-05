<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('maintenance_ticket_costs')
            ->join('maintenance_cut_items', 'maintenance_cut_items.ticket_id', '=', 'maintenance_ticket_costs.ticket_id')
            ->join('maintenance_cuts', 'maintenance_cuts.id', '=', 'maintenance_cut_items.maintenance_cut_id')
            ->whereNotNull('maintenance_ticket_costs.expense_id')
            ->orderBy('maintenance_ticket_costs.id')
            ->select([
                'maintenance_ticket_costs.expense_id',
                'maintenance_cuts.paid_at',
            ])
            ->chunk(500, function ($rows): void {
                $updatedAt = now();

                foreach ($rows as $row) {
                    DB::table('expenses')
                        ->where('id', $row->expense_id)
                        ->whereNull('paid_at')
                        ->update([
                            'paid_at' => $row->paid_at,
                            'updated_at' => $updatedAt,
                        ]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
