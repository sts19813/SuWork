<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_cuts', function (Blueprint $table): void {
            $table->foreignId('provider_id')
                ->nullable()
                ->after('paid_by_user_id')
                ->constrained('maintenance_providers')
                ->nullOnDelete();
        });

        DB::table('maintenance_cuts')->orderBy('id')->eachById(function (object $cut): void {
            $providerId = DB::table('maintenance_cut_items')
                ->join('maintenance_tickets', 'maintenance_tickets.id', '=', 'maintenance_cut_items.ticket_id')
                ->where('maintenance_cut_items.maintenance_cut_id', $cut->id)
                ->value('maintenance_tickets.current_provider_id');

            if ($providerId !== null) {
                DB::table('maintenance_cuts')->where('id', $cut->id)->update(['provider_id' => $providerId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_cuts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('provider_id');
        });
    }
};
