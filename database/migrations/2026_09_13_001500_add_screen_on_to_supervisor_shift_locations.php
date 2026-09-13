<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisor_shift_locations', function (Blueprint $table) {
            $table->boolean('screen_on')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('supervisor_shift_locations', function (Blueprint $table) {
            $table->dropColumn('screen_on');
        });
    }
};
