<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installations', function (Blueprint $table): void {
            $table->dropUnique(['client_id', 'name']);
            $table->string('kind', 20)->nullable()->after('code');
            $table->string('dane_code', 20)->nullable()->after('kind');
        });

        Schema::table('installations', function (Blueprint $table): void {
            $table->unique('dane_code');
        });

        Schema::table('client_user_installation_assignments', function (Blueprint $table): void {
            $table->string('site_permission', 16)->default('admin')->after('installation_id');
        });
    }

    public function down(): void
    {
        Schema::table('client_user_installation_assignments', function (Blueprint $table): void {
            $table->dropColumn('site_permission');
        });

        Schema::table('installations', function (Blueprint $table): void {
            $table->dropUnique(['dane_code']);
            $table->dropColumn(['kind', 'dane_code']);
            $table->unique(['client_id', 'name']);
        });
    }
};
