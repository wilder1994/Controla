<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('supervision_code_id')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->dateTime('log_time');
            $table->string('type', 30)->default('general');
            $table->string('shift_type', 20)->default('diurno');
            $table->text('description');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('supervision_code_id')
                ->references('id')
                ->on('supervision_codes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisions');
    }
};