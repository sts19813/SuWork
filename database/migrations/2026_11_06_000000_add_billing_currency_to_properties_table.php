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
            $table->string('billing_currency', 3)->default('mxn')->after('monthly_rent_price')->index();
        });

        DB::table('properties')->whereNull('billing_currency')->update(['billing_currency' => 'mxn']);
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropIndex(['billing_currency']);
            $table->dropColumn('billing_currency');
        });
    }
};
