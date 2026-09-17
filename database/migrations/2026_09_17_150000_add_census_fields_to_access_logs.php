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
            if (! Schema::hasColumn('access_logs', 'structure_member_id')) {
                $table->foreignId('structure_member_id')->nullable()->after('visitor_id')->constrained('structure_members')->nullOnDelete();
            }
            if (! Schema::hasColumn('access_logs', 'photo_path')) {
                $table->string('photo_path')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('access_logs', 'vehicle_photo_path')) {
                $table->string('vehicle_photo_path')->nullable()->after('photo_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            if (Schema::hasColumn('access_logs', 'structure_member_id')) {
                $table->dropConstrainedForeignId('structure_member_id');
            }
            foreach (['photo_path', 'vehicle_photo_path'] as $col) {
                if (Schema::hasColumn('access_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
