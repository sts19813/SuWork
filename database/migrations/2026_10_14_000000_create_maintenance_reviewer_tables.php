<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_reviewer_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('range_type', 20)->default('relative');
            $table->unsignedInteger('days_before')->nullable();
            $table->unsignedInteger('days_after')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance_ticket_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained('maintenance_tickets')->cascadeOnDelete();
            $table->foreignId('validated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('validated_at');
            $table->timestamps();
        });

        $permissionIds = collect(['mantenimiento.consultar_tickets', 'mantenimiento.validar_tickets'])
            ->map(function (string $name): int {
                $id = DB::table('permissions')
                    ->where('name', $name)
                    ->where('guard_name', 'web')
                    ->value('id');

                return $id ?: DB::table('permissions')->insertGetId([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        $reviewerRoleId = DB::table('roles')
            ->where('name', 'revisor de tickets')
            ->where('guard_name', 'web')
            ->value('id');
        $reviewerRoleId ??= DB::table('roles')->insertGetId([
            'name' => 'revisor de tickets',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $reviewerRoleId,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_ticket_validations');
        Schema::dropIfExists('maintenance_reviewer_accesses');
    }
};
