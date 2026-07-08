<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('structure_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            $table->string('assigned_parking_spot', 50)->nullable()->after('photo_path');
            $table->string('tag_rfid', 100)->nullable()->after('assigned_parking_spot');
            $table->date('soat_expires_at')->nullable()->after('tag_rfid');
            $table->date('license_expires_at')->nullable()->after('soat_expires_at');
            $table->boolean('is_visitor_vehicle')->default(false)->after('license_expires_at');

            $table->index(['client_id', 'structure_id']);
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['structure_id']);
            $table->dropColumn([
                'structure_id',
                'assigned_parking_spot',
                'tag_rfid',
                'soat_expires_at',
                'license_expires_at',
                'is_visitor_vehicle',
            ]);
        });
    }
};
