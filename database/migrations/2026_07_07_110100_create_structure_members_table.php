<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structure_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('structure_id')->constrained()->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('document_number', 30);
            $table->string('phone_primary', 20)->nullable();
            $table->string('phone_secondary', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('member_type', 30);
            $table->boolean('has_app_access')->default(false);
            $table->string('access_code', 64)->nullable()->unique();
            $table->string('photo_path', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['client_id', 'document_number']);
            $table->index(['structure_id', 'member_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('structure_members');
    }
};
