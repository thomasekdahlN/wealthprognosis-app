<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('simulation_configurations', function (Blueprint $table) {
            $table->string('tax_country', 2)->default('no')->after('risk_tolerance')->comment('Country code for tax calculations (no, se, ch, etc.)');
            $table->string('prognosis_type')->default('realistic')->after('tax_country')->comment('Prognosis scenario type (realistic, positive, negative, tenpercent, zero, variable)');
            $table->string('group')->default('private')->after('prognosis_type')->comment('Asset group filter (private, company, or both)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simulation_configurations', function (Blueprint $table) {
            $table->dropColumn(['tax_country', 'prognosis_type', 'group']);
        });
    }
};
