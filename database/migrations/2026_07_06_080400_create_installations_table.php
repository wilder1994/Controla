<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('code', 40)->nullable();
            $table->string('kind', 20)->nullable();
            $table->string('dane_code', 20)->nullable()->unique();
            $table->string('commune', 80)->nullable();
            $table->string('area_kind', 20)->nullable();
            $table->foreignId('rector_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_client_site')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('address', 255)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('department', 120)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['client_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installations');
    }
};
