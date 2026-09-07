<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 64)->nullable()->after('name');
        });

        $used = [];
        foreach (DB::table('users')->orderBy('id')->get(['id', 'email']) as $row) {
            $local = strtolower((string) strstr((string) $row->email, '@', true));
            $base = preg_replace('/[^a-z0-9._-]/', '', $local) ?: 'user'.$row->id;
            $candidate = $base;
            $n = 1;
            while (isset($used[$candidate])) {
                $n++;
                $candidate = $base.'.'.$n;
            }
            $used[$candidate] = true;
            DB::table('users')->where('id', $row->id)->update(['username' => $candidate]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });

        DB::statement('ALTER TABLE users MODIFY username VARCHAR(64) NOT NULL');
        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::table('users')->whereNull('email')->update(['email' => DB::raw("CONCAT('pending-', id, '@local.test')")]);
        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
