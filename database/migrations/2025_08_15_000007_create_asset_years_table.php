<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_years', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained('teams', 'id')->name('asset_years_team_id_foreign')->onDelete('cascade');
            $table->integer('year');
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->foreignId('asset_configuration_id')->nullable()->constrained('asset_configurations', 'id')->name('asset_years_asset_configuration_id_foreign')->onDelete('cascade');

            // Unified description field (replaces income_description, expence_description, asset_description, mortgage_description)
            $table->text('description')->nullable();

            // Income data
            $table->decimal('income_amount', 15, 2)->nullable();
            $table->enum('income_factor', ['monthly', 'yearly'])->default('monthly');
            $table->string('income_rule')->nullable();
            $table->string('income_transfer')->nullable();
            $table->string('income_source')->nullable();
            $table->string('income_changerate')->nullable();
            $table->boolean('income_repeat')->default(false);

            // Expense data
            $table->decimal('expence_amount', 15, 2)->nullable();
            $table->enum('expence_factor', ['monthly', 'yearly'])->default('monthly');
            $table->string('expence_rule')->nullable();
            $table->string('expence_transfer')->nullable();
            $table->string('expence_source')->nullable();
            $table->string('expence_changerate')->nullable();
            $table->boolean('expence_repeat')->default(false);

            // Asset data
            $table->decimal('asset_market_amount', 15, 2)->nullable();
            $table->decimal('asset_acquisition_amount', 15, 2)->nullable();
            $table->decimal('asset_equity_amount', 15, 2)->nullable();
            $table->decimal('asset_taxable_initial_amount', 15, 2)->nullable();
            $table->decimal('asset_paid_amount', 15, 2)->nullable();
            $table->string('asset_changerate')->nullable();
            $table->string('asset_rule')->nullable();
            $table->string('asset_transfer')->nullable();
            $table->string('asset_source')->nullable();
            $table->boolean('asset_repeat')->default(true);

            // Mortgage data
            $table->decimal('mortgage_amount', 15, 2)->nullable();
            $table->smallInteger('mortgage_years')->nullable();
            $table->string('mortgage_interest')->nullable();
            $table->smallInteger('mortgage_interest_only_years')->nullable();
            $table->string('mortgage_extra_downpayment_amount')->nullable();
            $table->decimal('mortgage_gebyr', 15, 2)->nullable();
            $table->decimal('mortgage_tax', 5, 2)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->name('asset_years_created_by_foreign');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->name('asset_years_updated_by_foreign');
            $table->char('created_checksum', 64)->nullable();
            $table->char('updated_checksum', 64)->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'year']);
            $table->index(['user_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_years');
    }
};
