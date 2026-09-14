<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_module_grants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 20);
            $table->unsignedBigInteger('scope_id');
            $table->string('module', 40);
            $table->string('level', 10);
            $table->timestamps();
            $table->unique(['user_id', 'scope', 'scope_id', 'module'], 'user_module_grants_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_module_grants');
    }
};
