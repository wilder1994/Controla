<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correspondence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('visitor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('host_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('housing_unit_id')->nullable()->index();
            $table->unsignedBigInteger('resident_id')->nullable()->index();
            $table->string('carrier', 100)->nullable();
            $table->string('courier_guide', 100)->nullable();
            $table->string('package_type', 30)->default('documento');
            $table->dateTime('received_at');
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('delivered_at')->nullable();
            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('photo_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correspondence');
    }
};
