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
        $keep = DB::table('supervisor_post_employee')
            ->select('employee_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('employee_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($keep as $row) {
            DB::table('supervisor_post_employee')
                ->where('employee_id', $row->employee_id)
                ->where('id', '!=', $row->keep_id)
                ->delete();
        }

        Schema::table('supervisor_post_employee', function (Blueprint $table) {
            $table->unique('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('supervisor_post_employee', function (Blueprint $table) {
            $table->dropUnique(['employee_id']);
        });
    }
};
