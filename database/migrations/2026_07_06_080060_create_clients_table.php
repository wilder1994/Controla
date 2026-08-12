<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 80);
            $table->string('login_suffix', 80);
            $table->string('address')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('department', 120)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('plan_tier', 20)->default('economic');
            $table->unsignedSmallInteger('max_structures')->default(20);
            $table->string('logo_path')->nullable();
            $table->string('access_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('service_started_at')->nullable();
            $table->string('lifecycle', 30)->default('active');
            $table->timestamp('released_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('tenant_data_purged_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['security_company_id', 'slug']);
            $table->unique(['security_company_id', 'login_suffix']);
            $table->index(['security_company_id', 'is_active']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('security_company_id')
                ->references('id')
                ->on('security_companies')
                ->nullOnDelete();
            $table->foreign('primary_client_id')
                ->references('id')
                ->on('clients')
                ->nullOnDelete();
            $table->unique(['security_company_id', 'supervisor_code'], 'users_company_supervisor_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_company_supervisor_code_unique');
            $table->dropForeign(['primary_client_id']);
            $table->dropForeign(['security_company_id']);
        });

        Schema::dropIfExists('clients');
    }
};
