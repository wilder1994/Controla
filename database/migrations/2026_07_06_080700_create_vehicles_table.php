<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('structure_id')->nullable()->index();
            $table->foreignId('visitor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('resident_id')->nullable()->index();
            $table->string('plate', 20)->unique();
            $table->string('brand', 50)->nullable();
            $table->string('model', 50)->nullable();
            $table->string('color', 30)->nullable();
            $table->string('type', 20)->default('carro');
            $table->string('photo_path', 255)->nullable();
            $table->string('assigned_parking_spot', 50)->nullable();
            $table->string('tag_rfid', 100)->nullable();
            $table->date('soat_expires_at')->nullable();
            $table->date('license_expires_at')->nullable();
            $table->boolean('is_visitor_vehicle')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'structure_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
