<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_companies', function (Blueprint $table) {
            $table->text('field_sheet_intro')->nullable()->after('logo_path');
        });

        Schema::table('supervisor_shift_reviews', function (Blueprint $table) {
            $table->text('sheet_intro')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('supervisor_shift_reviews', function (Blueprint $table) {
            $table->dropColumn('sheet_intro');
        });

        Schema::table('security_companies', function (Blueprint $table) {
            $table->dropColumn('field_sheet_intro');
        });
    }
};
