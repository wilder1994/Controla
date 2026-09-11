<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('ceased_at');
            $table->string('education', 120)->nullable()->after('photo_path');
            $table->string('marital_status', 80)->nullable()->after('education');
            $table->unsignedTinyInteger('children_count')->nullable()->after('marital_status');
            $table->string('phone', 40)->nullable()->after('children_count');
            $table->string('residence_city', 120)->nullable()->after('phone');
            $table->string('address', 180)->nullable()->after('residence_city');
            $table->string('engagement_type', 80)->nullable()->after('address');
            $table->string('contributor_type', 80)->nullable()->after('engagement_type');
            $table->string('labor_contract_type', 80)->nullable()->after('contributor_type');
            $table->date('hired_on')->nullable()->after('labor_contract_type');
            $table->date('labor_contract_ends_on')->nullable()->after('hired_on');
            $table->date('left_on')->nullable()->after('labor_contract_ends_on');
            $table->string('eps_code', 40)->nullable()->after('left_on');
            $table->string('eps_name', 120)->nullable()->after('eps_code');
            $table->string('afp_code', 40)->nullable()->after('eps_name');
            $table->string('afp_name', 120)->nullable()->after('afp_code');
            $table->string('compensation_fund', 120)->nullable()->after('afp_name');
            $table->string('arl_name', 120)->nullable()->after('compensation_fund');
            $table->string('arl_risk_level', 40)->nullable()->after('arl_name');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'photo_path',
                'education',
                'marital_status',
                'children_count',
                'phone',
                'residence_city',
                'address',
                'engagement_type',
                'contributor_type',
                'labor_contract_type',
                'hired_on',
                'labor_contract_ends_on',
                'left_on',
                'eps_code',
                'eps_name',
                'afp_code',
                'afp_name',
                'compensation_fund',
                'arl_name',
                'arl_risk_level',
            ]);
        });
    }
};
