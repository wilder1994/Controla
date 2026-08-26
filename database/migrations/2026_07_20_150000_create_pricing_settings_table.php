<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('unit_price_manual', 12, 2);
            $table->decimal('unit_price_hardware', 12, 2);
            $table->decimal('unit_price_supervision', 12, 2)->default(80000);
            $table->string('currency', 3)->default('COP');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('pricing_settings')->insert([
            'unit_price_manual' => config('tenancy.pricing.default_unit_manual', 80_000),
            'unit_price_hardware' => config('tenancy.pricing.default_unit_hardware', 150_000),
            'unit_price_supervision' => config('tenancy.pricing.default_unit_supervision', 80_000),
            'currency' => config('tenancy.pricing.currency', 'COP'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_settings');
    }
};
