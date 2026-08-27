<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('installation_id')->constrained()->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name', 100);
            $table->string('address', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('geo_radius_m')->default(250);
            $table->string('type', 20)->default('access_point');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['client_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
