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
            $table->string('plan_tier', 20)->default('economic');
            $table->unsignedSmallInteger('max_structures')->default(20);
            $table->string('logo_path')->nullable();
            $table->string('access_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['security_company_id', 'slug']);
            $table->unique(['security_company_id', 'login_suffix']);
            $table->index(['security_company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
