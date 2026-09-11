<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisor_posts', function (Blueprint $table) {
            $table->unsignedTinyInteger('modality')->default(12)->after('name');
        });

        Schema::create('supervisor_post_employee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_post_id')->constrained('supervisor_posts')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['supervisor_post_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_post_employee');

        Schema::table('supervisor_posts', function (Blueprint $table) {
            $table->dropColumn('modality');
        });
    }
};
