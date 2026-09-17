<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('access_logs', 'destination_structure_id')) {
                $table->foreignId('destination_structure_id')->nullable()->after('location_id')->constrained('structures')->nullOnDelete();
            }
            if (! Schema::hasColumn('access_logs', 'destination_text')) {
                $table->string('destination_text', 255)->nullable()->after('destination_structure_id');
            }
            if (! Schema::hasColumn('access_logs', 'authorized_member_id')) {
                $table->foreignId('authorized_member_id')->nullable()->after('authorized_by')->constrained('structure_members')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            if (Schema::hasColumn('access_logs', 'authorized_member_id')) {
                $table->dropConstrainedForeignId('authorized_member_id');
            }
            if (Schema::hasColumn('access_logs', 'destination_structure_id')) {
                $table->dropConstrainedForeignId('destination_structure_id');
            }
            if (Schema::hasColumn('access_logs', 'destination_text')) {
                $table->dropColumn('destination_text');
            }
        });
    }
};
