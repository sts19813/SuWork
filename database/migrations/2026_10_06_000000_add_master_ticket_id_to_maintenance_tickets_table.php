<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_tickets', function (Blueprint $table): void {
            $table->foreignId('master_ticket_id')
                ->nullable()
                ->after('current_provider_id')
                ->constrained('maintenance_tickets')
                ->nullOnDelete();

            $table->index(['master_ticket_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_tickets', function (Blueprint $table): void {
            $table->dropIndex(['master_ticket_id', 'status']);
            $table->dropConstrainedForeignId('master_ticket_id');
        });
    }
};
