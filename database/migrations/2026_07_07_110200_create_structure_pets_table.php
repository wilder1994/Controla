<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structure_pets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('structure_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('species', 20);
            $table->string('breed', 50)->nullable();
            $table->boolean('is_potentially_dangerous')->default(false);
            $table->string('vaccination_card_path', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'species']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('structure_pets');
    }
};
