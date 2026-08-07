<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_companies', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');
            $table->string('trade_name');
            $table->string('tax_id', 30)->nullable()->unique();
            $table->string('party_type', 20)->default('legal_entity');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('department', 120)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('package_size')->default(10);
            $table->string('package_modality', 20)->default('manual');
            $table->string('package_sku', 40)->nullable();
            $table->decimal('package_price_monthly', 12, 2)->nullable();
            $table->unsignedSmallInteger('max_clients')->default(10);
            $table->string('billing_cycle', 20)->default('monthly');
            $table->unsignedTinyInteger('billing_day')->nullable();
            $table->decimal('unit_price_snapshot', 12, 2)->nullable();
            $table->decimal('volume_discount_pct', 5, 4)->nullable();
            $table->decimal('annual_discount_pct', 5, 4)->nullable();
            $table->decimal('package_price_annual', 12, 2)->nullable();
            $table->timestamp('package_starts_at')->nullable();
            $table->timestamp('package_ends_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->string('archive_reason', 20)->nullable();
            $table->timestamp('commercial_anonymized_at')->nullable();
            $table->string('subscription_status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'trade_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_companies');
    }
};
