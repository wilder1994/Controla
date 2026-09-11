<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('admin_origin', 16)->nullable();
            $table->string('document_number', 30)->nullable();
        });

        Schema::create('client_user_installation_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installation_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'installation_id'], 'client_user_inst_assign_unique');
        });

        $clientAdminIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'client-admin')
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->pluck('model_has_roles.model_id');

        if ($clientAdminIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('id', $clientAdminIds)
                ->whereNotNull('employee_id')
                ->update(['admin_origin' => 'internal']);

            DB::table('users')
                ->whereIn('id', $clientAdminIds)
                ->whereNull('employee_id')
                ->update(['admin_origin' => 'external']);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_user_installation_assignments');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['admin_origin', 'document_number']);
        });
    }
};
