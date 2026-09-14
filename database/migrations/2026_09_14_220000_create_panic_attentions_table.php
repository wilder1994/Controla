<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panic_attentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operational_alert_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attended_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 16);
            $table->text('observations')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['security_company_id', 'status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panic_attentions');
    }
};
