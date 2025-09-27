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
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('year')->index();
            $table->foreignId('asset_id')->constrained('simulation_assets')->onDelete('cascade')->index();
            $table->foreignId('asset_configuration_id')->nullable()->constrained('asset_configurations')->onDelete('cascade')->index();

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
            $table->boolean('income_repeat')->default(false);

            // Expence
            $table->decimal('expence_amount', 15, 2)->nullable();
            $table->enum('expence_factor', ['monthly', 'yearly'])->default('monthly');
            $table->string('expence_rule')->nullable();
            $table->string('expence_transfer')->nullable();
            $table->decimal('expence_transfer_amount', 15, 2)->nullable();
            $table->string('expence_source')->nullable();
            $table->string('expence_changerate')->nullable();
            $table->boolean('expence_repeat')->default(false);

            // Cashflow
            $table->text('cashflow_description')->nullable();
            $table->decimal('cashflow_after_taxamount', 15, 2)->nullable();
            $table->decimal('cashflow_before_taxamount', 15, 2)->nullable();
            $table->decimal('cashflow_before_tax_aggregated_amount', 15, 2)->nullable();
            $table->decimal('cashflow_after_tax_aggregatedamount', 15, 2)->nullable();
            $table->decimal('cashflow_tax_amount', 15, 2)->nullable();
            $table->decimal('cashflow_tax_percent', 3, 2)->nullable();
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
            $table->decimal('asset_mortgage_rate_percent', 3, 2)->nullable();
            $table->decimal('asset_taxable_percent', 3, 2)->nullable();
            $table->decimal('asset_taxable_amount', 15, 2)->nullable();
            $table->decimal('asset_taxable_initial_amount', 15, 2)->nullable();
            $table->boolean('asset_taxable_amount_override')->nullable();
            $table->decimal('asset_tax_percent', 3, 2)->nullable();
            $table->decimal('asset_tax_amount', 15, 2)->nullable();
            $table->decimal('asset_taxable_property_percent', 3, 2)->nullable();
            $table->decimal('asset_taxable_property_amount', 15, 2)->nullable();
            $table->decimal('asset_tax_property_percent', 3, 2)->nullable();
            $table->decimal('asset_tax_property_amount', 15, 2)->nullable();
            $table->string('asset_changerate')->nullable();
            $table->decimal('asset_changerate_percent', 3, 2)->nullable();
            $table->string('asset_rule')->nullable();
            $table->string('asset_transfer')->nullable();
            $table->string('asset_source')->nullable();
            $table->boolean('asset_repeat')->default(true);

            // Mortgage
            $table->decimal('mortgage_amount', 15, 2)->nullable();
            $table->decimal('mortgage_term_amount', 15, 2)->nullable();
            $table->decimal('mortgage_interest_amount', 15, 2)->nullable();
            $table->decimal('mortgage_principal_amount', 15, 2)->nullable();
            $table->decimal('mortgage_balance_amount', 15, 2)->nullable();
            $table->decimal('mortgage_extra_downpayment_amount', 15, 2)->nullable();
            $table->decimal('mortgage_transfered_amount', 15, 2)->nullable();
            $table->string('mortgage_interest_percent')->nullable();
            $table->integer('mortgage_years')->nullable();
            $table->decimal('mortgage_gebyr_amount', 15, 2)->nullable();
            $table->decimal('mortgage_tax_deductable_amount', 15, 2)->nullable();
            $table->decimal('mortgage_tax_deductable_percent', 3, 2)->nullable();

            // Realization
            $table->text('realization_description')->nullable();
            $table->decimal('realization_amount', 15, 2)->nullable();
            $table->decimal('realization_taxable_amount', 15, 2)->nullable();
            $table->decimal('realization_tax_amount', 15, 2)->nullable();
            $table->decimal('realization_tax_percent', 3, 2)->nullable();
            $table->decimal('realization_tax_shield_amount', 15, 2)->nullable();
            $table->decimal('realization_tax_shield_percent', 3, 2)->nullable();

            // Yield
            $table->decimal('yield_brutto_percent', 3, 2)->nullable();
            $table->decimal('yield_netto_percent', 3, 2)->nullable();

            // Potential
            $table->decimal('potential_income_amount', 15, 2)->nullable();
            $table->decimal('potential_mortgage_amount', 15, 2)->nullable();

            // F.I.R.E.
            $table->decimal('fire_percent', 3, 2)->nullable();
            $table->decimal('fire_income_amount', 15, 2)->nullable();
            $table->decimal('fire_expence_amount', 15, 2)->nullable();
            $table->decimal('fire_rate_percent', 3, 2)->nullable();
            $table->decimal('fire_cashflow_amount', 15, 2)->nullable();
            $table->decimal('fire_savings_amount', 15, 2)->nullable();
            $table->decimal('fire_savings_rate_percent', 3, 2)->nullable();

            // div
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->string('created_checksum')->nullable();
            $table->string('updated_checksum')->nullable();
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_asset_years');
    }
};
