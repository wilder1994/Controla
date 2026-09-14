<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('installation_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('supervisor_post_id')->nullable();
            $table->string('title');
            $table->text('body');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['security_company_id', 'type', 'id']);
            $table->index(['client_id', 'type', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_alerts');
    }
};
