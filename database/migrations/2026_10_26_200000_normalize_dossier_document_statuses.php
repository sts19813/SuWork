<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['property_documents', 'tenant_documents', 'owner_documents'] as $table) {
            $this->normalizeTable($table);
        }
    }

    public function down(): void
    {
        // The prior intermediate status cannot be reconstructed safely.
    }

    private function normalizeTable(string $table): void
    {
        $now = now();
        $today = $now->toDateString();

        DB::table($table)
            ->where(function ($query): void {
                $query->whereNull('file_path')->orWhere('file_path', '');
            })
            ->update([
                'status' => 'pending',
                'updated_at' => $now,
            ]);

        DB::table($table)
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->whereDate('expires_at', '<', $today)
            ->update([
                'status' => 'expired',
                'updated_at' => $now,
            ]);

        DB::table($table)
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->where(function ($query) use ($today): void {
                $query->whereNull('expires_at')->orWhereDate('expires_at', '>=', $today);
            })
            ->update([
                'status' => 'approved',
                'updated_at' => $now,
            ]);
    }
};
