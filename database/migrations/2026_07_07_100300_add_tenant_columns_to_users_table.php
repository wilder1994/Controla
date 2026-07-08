<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('security_company_id')
                ->nullable()
                ->after('area_key')
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('primary_client_id')
                ->nullable()
                ->after('security_company_id')
                ->constrained('clients')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_client_id');
            $table->dropConstrainedForeignId('security_company_id');
        });
    }
};
