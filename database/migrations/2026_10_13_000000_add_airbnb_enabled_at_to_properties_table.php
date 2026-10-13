<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->timestamp('airbnb_enabled_at')->nullable()->after('airbnb_managed_at');
        });

        DB::table('properties')
            ->whereNotNull('airbnb_managed_at')
            ->whereNull('airbnb_enabled_at')
            ->update(['airbnb_enabled_at' => DB::raw('airbnb_managed_at')]);
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn('airbnb_enabled_at');
        });
    }
};
