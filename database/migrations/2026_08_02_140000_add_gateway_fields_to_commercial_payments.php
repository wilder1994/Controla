<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_payments', function (Blueprint $table) {
            $table->string('gateway_driver', 30)->nullable()->after('method');
            $table->string('gateway_transaction_id', 64)->nullable()->after('gateway_driver');
            $table->string('gateway_status', 40)->nullable()->after('gateway_transaction_id');
            $table->foreignId('initiated_by_user_id')->nullable()->after('recorded_by_user_id')
                ->constrained('users')->nullOnDelete();

            $table->unique('gateway_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('commercial_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('initiated_by_user_id');
            $table->dropUnique(['gateway_transaction_id']);
            $table->dropColumn(['gateway_driver', 'gateway_transaction_id', 'gateway_status']);
        });
    }
};
