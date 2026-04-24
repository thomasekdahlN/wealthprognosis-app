<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_asset_years', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained('teams', 'id')->name('sim_years_team_id_foreign')->onDelete('cascade');
            $table->integer('year');
            $table->foreignId('asset_id')->constrained('simulation_assets', 'id')->name('sim_years_asset_id_foreign')->onDelete('cascade');
            $table->foreignId('asset_configuration_id')->nullable()->constrained('asset_configurations', 'id')->name('sim_years_asset_config_id_foreign')->onDelete('cascade')->index();

            // Unified description field (replaces income_description, expence_description, asset_description, mortgage_description)
            $table->text('description')->nullable();

            // Income
            $table->decimal('income_amount', 15, 2)->nullable();
            $table->enum('income_factor', ['monthly', 'yearly'])->default('monthly');
            $table->string('income_rule')->nullable();
            $table->string('income_transfer')->nullable();
            $table->decimal('income_transfer_amount', 15, 2)->nullable();
            $table->string('income_source')->nullable();
            $table->string('income_changerate')->nullable();
            $table->decimal('income_changerate_percent', 5, 2)->nullable();
            $table->boolean('income_repeat')->default(false);
            $table->text('income_description')->nullable();

            // Expence
            $table->decimal('expence_amount', 15, 2)->nullable();
            $table->enum('expence_factor', ['monthly', 'yearly'])->default('monthly');
            $table->string('expence_rule')->nullable();
            $table->string('expence_transfer')->nullable();
            $table->decimal('expence_transfer_amount', 15, 2)->nullable();
            $table->string('expence_source')->nullable();
            $table->string('expence_changerate')->nullable();
            $table->decimal('expence_changerate_percent', 5, 2)->nullable();
            $table->boolean('expence_repeat')->default(false);
            $table->text('expence_description')->nullable();

            // Cashflow
            $table->text('cashflow_description')->nullable();
            $table->decimal('cashflow_after_tax_amount', 15, 2)->nullable();
            $table->decimal('cashflow_before_tax_amount', 15, 2)->nullable();
            $table->decimal('cashflow_before_tax_aggregated_amount', 15, 2)->nullable();
            $table->decimal('cashflow_after_tax_aggregated_amount', 15, 2)->nullable();
            $table->decimal('cashflow_tax_amount', 15, 2)->nullable();
            $table->decimal('cashflow_tax_percent', 5, 2)->nullable();
            $table->string('cashflow_rule')->nullable();
            $table->string('cashflow_transfer')->nullable();
            $table->decimal('cashflow_transfer_amount', 15, 2)->nullable();
            $table->string('cashflow_source')->nullable();
            $table->string('cashflow_changerate')->nullable();
            $table->boolean('cashflow_repeat')->default(false);

            // Asset
            $table->decimal('asset_market_amount', 15, 2)->nullable();
            $table->decimal('asset_market_mortgage_deducted_amount', 15, 2)->nullable();
            $table->decimal('asset_acquisition_amount', 15, 2)->nullable();
            $table->decimal('asset_acquisition_initial_amount', 15, 2)->nullable();
            $table->decimal('asset_equity_amount', 15, 2)->nullable();
            $table->decimal('asset_equity_initial_amount', 15, 2)->nullable();
            $table->decimal('asset_paid_amount', 15, 2)->nullable();
            $table->decimal('asset_paid_initial_amount', 15, 2)->nullable();
            $table->decimal('asset_transfered_amount', 15, 2)->nullable();
            $table->decimal('asset_taxable_percent', 5, 2)->nullable();
            $table->decimal('asset_taxable_amount', 15, 2)->nullable();
            $table->decimal('asset_taxable_initial_amount', 15, 2)->nullable();
            $table->boolean('asset_taxable_amount_override')->nullable();
            $table->decimal('asset_tax_percent', 5, 2)->nullable();
            $table->decimal('asset_tax_amount', 15, 2)->nullable();
            $table->decimal('asset_taxable_property_percent', 5, 2)->nullable();
            $table->decimal('asset_taxable_property_amount', 15, 2)->nullable();
            $table->decimal('asset_tax_property_percent', 5, 2)->nullable();
            $table->decimal('asset_tax_property_amount', 15, 2)->nullable();
            $table->decimal('asset_taxable_fortune_amount', 15, 2)->nullable();
            $table->decimal('asset_taxable_fortune_percent', 5, 2)->nullable();
            $table->decimal('asset_tax_fortune_amount', 15, 2)->nullable();
            $table->decimal('asset_tax_fortune_percent', 5, 2)->nullable();
            $table->decimal('asset_gjeldsfradrag_amount', 15, 2)->nullable();
            $table->string('asset_changerate')->nullable();
            $table->decimal('asset_changerate_percent', 5, 2)->nullable();
            $table->string('asset_rule')->nullable();
            $table->string('asset_transfer')->nullable();
            $table->string('asset_source')->nullable();
            $table->boolean('asset_repeat')->default(true);
            $table->text('asset_description')->nullable();

            // Mortgage
            $table->decimal('mortgage_amount', 15, 2)->nullable();
            $table->decimal('mortgage_term_amount', 15, 2)->nullable();
            $table->decimal('mortgage_interest_amount', 15, 2)->nullable();
            $table->decimal('mortgage_principal_amount', 15, 2)->nullable();
            $table->decimal('mortgage_balance_amount', 15, 2)->nullable();
            $table->decimal('mortgage_extra_downpayment_amount', 15, 2)->nullable();
            $table->decimal('mortgage_transfered_amount', 15, 2)->nullable();
            $table->decimal('mortgage_interest_percent', 5, 2)->nullable();
            $table->integer('mortgage_years')->nullable();
            $table->integer('mortgage_interest_only_years')->nullable();
            $table->decimal('mortgage_gebyr_amount', 15, 2)->nullable();
            $table->decimal('mortgage_tax_deductable_amount', 15, 2)->nullable();
            $table->decimal('mortgage_tax_deductable_percent', 5, 2)->nullable();
            $table->text('mortgage_description')->nullable();

            // Realization
            $table->text('realization_description')->nullable();
            $table->decimal('realization_amount', 15, 2)->nullable();
            $table->decimal('realization_taxable_amount', 15, 2)->nullable();
            $table->decimal('realization_tax_amount', 15, 2)->nullable();
            $table->decimal('realization_tax_percent', 3, 2)->nullable();
            $table->decimal('realization_tax_shield_amount', 15, 2)->nullable();
            $table->decimal('realization_tax_shield_percent', 3, 2)->nullable();

            // Yield
            $table->decimal('yield_gross_percent', 5, 2)->nullable();
            $table->decimal('yield_net_percent', 5, 2)->nullable();
            $table->decimal('yield_cap_percent', 5, 2)->nullable();

            // Potential
            $table->decimal('potential_income_amount', 15, 2)->nullable();
            $table->decimal('potential_mortgage_amount', 15, 2)->nullable();

            // Metrics (financial metrics from YearlyProcessor)
            $table->decimal('metrics_roi_percent', 5, 2)->nullable();
            $table->decimal('metrics_total_return_amount', 15, 2)->nullable();
            $table->decimal('metrics_total_return_percent', 5, 2)->nullable();
            $table->decimal('metrics_coc_percent', 5, 2)->nullable();
            $table->decimal('metrics_noi', 15, 2)->nullable();
            $table->decimal('metrics_grm', 5, 2)->nullable();
            $table->decimal('metrics_dscr', 5, 2)->nullable();
            $table->decimal('metrics_ltv_percent', 5, 2)->nullable();
            $table->decimal('metrics_de_ratio', 5, 2)->nullable();
            $table->decimal('metrics_roe_percent', 5, 2)->nullable();
            $table->decimal('metrics_roa_percent', 5, 2)->nullable();
            $table->decimal('metrics_pb_ratio', 5, 2)->nullable();
            $table->decimal('metrics_ev_ebitda', 5, 2)->nullable();
            $table->decimal('metrics_current_ratio', 5, 2)->nullable();

            // F.I.R.E.
            $table->decimal('fire_percent', 5, 2)->nullable();
            $table->decimal('fire_income_amount', 15, 2)->nullable();
            $table->decimal('fire_expence_amount', 15, 2)->nullable();
            $table->decimal('fire_cashflow_amount', 15, 2)->nullable();
            $table->decimal('fire_saving_amount', 15, 2)->nullable();
            $table->decimal('fire_saving_rate_percent', 5, 2)->nullable();

            // div
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->name('sim_years_created_by_foreign');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->name('sim_years_updated_by_foreign');
            $table->char('created_checksum', 64)->nullable();
            $table->char('updated_checksum', 64)->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'year']);
            $table->index(['user_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_asset_years');
    }
};
