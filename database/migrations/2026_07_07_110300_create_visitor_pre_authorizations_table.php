<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_pre_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('structure_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('structure_members')->nullOnDelete();
            $table->string('visitor_name', 150);
            $table->string('visitor_document', 30)->nullable();
            $table->string('visitor_category', 20);
            $table->date('valid_for_date');
            $table->string('qr_auth_token', 100)->nullable()->unique();
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'valid_for_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_pre_authorizations');
    }
};
