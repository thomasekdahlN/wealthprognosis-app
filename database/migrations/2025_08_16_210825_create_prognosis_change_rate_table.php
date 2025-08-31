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
        Schema::create('prognosis_change_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('scenario_type');
            $table->string('asset_type');
            $table->integer('year');
            $table->decimal('change_rate', 8, 4);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->string('created_checksum')->nullable();
            $table->string('updated_checksum')->nullable();
            $table->timestamps();

            $table->unique(['scenario_type', 'asset_type', 'year'], 'prognosis_change_rates_scenario_asset_year_unique');
            $table->index(['scenario_type', 'year'], 'prognosis_change_rates_scenario_year_index');
            $table->index(['user_id', 'team_id'], 'prognosis_change_rates_user_team_index');
            $table->index(['asset_type', 'is_active'], 'prognosis_change_rates_asset_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prognosis_change_rates');
    }
};
