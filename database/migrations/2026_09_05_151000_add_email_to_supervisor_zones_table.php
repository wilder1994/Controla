<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisor_zones', function (Blueprint $table) {
            $table->string('email', 150)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('supervisor_zones', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
