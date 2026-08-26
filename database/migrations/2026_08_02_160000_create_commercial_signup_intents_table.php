<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_signup_intents', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('status', 30)->default('draft');
            $table->string('package_sku', 40);
            $table->string('supervision_package_sku', 40)->nullable();
            $table->string('billing_cycle', 20);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('COP');
            $table->string('party_type', 20)->nullable();
            $table->string('legal_name')->nullable();
            $table->string('trade_name')->nullable();
            $table->string('tax_id', 40)->nullable();
            $table->string('admin_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('department', 120)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('password')->nullable();
            $table->string('representative_name')->nullable();
            $table->string('representative_role')->nullable();
            $table->string('representative_document_type', 20)->nullable();
            $table->string('representative_document_number', 40)->nullable();
            $table->json('corpus_snapshot')->nullable();
            $table->char('content_hash', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_signup_intents');
    }
};
