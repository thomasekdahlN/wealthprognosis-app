<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationAssetYear extends Model
{
    use Auditable, HasFactory;

    protected $table = 'simulation_asset_years';

    protected static function booted(): void
    {
        parent::booted();

        // Apply team-based filtering
        static::addGlobalScope(new TeamScope);
    }

    protected $fillable = [
        'user_id',
        'team_id',
        'year',
        'asset_id',
        'asset_configuration_id',
        // Unified year description
        'description',
        // Income fields
        'income_amount',
        'income_factor',
        'income_rule',
        'income_transfer',
        'income_transfer_amount',
        'income_source',
        'income_changerate',
        'income_repeat',
        // Expense fields
        'expence_amount',
        'expence_factor',
        'expence_rule',
        'expence_transfer',
        'expence_transfer_amount',
        'expence_source',
        'expence_changerate',
        'expence_repeat',
        // Cashflow fields
        'cashflow_description',
        'cashflow_after_taxamount',
        'cashflow_before_taxamount',
        'cashflow_before_tax_aggregated_amount',
        'cashflow_after_tax_aggregatedamount',
        'cashflow_tax_amount',
        'cashflow_tax_percent',
        'cashflow_rule',
        'cashflow_transfer',
        'cashflow_transfer_amount',
        'cashflow_source',
        'cashflow_changerate',
        'cashflow_repeat',
        // Asset fields
        'asset_market_amount',
        'asset_market_mortgage_deducted_amount',
        'asset_acquisition_amount',
        'asset_acquisition_initial_amount',
        'asset_equity_amount',
        'asset_equity_initial_amount',
        'asset_paid_amount',
        'asset_paid_initial_amount',
        'asset_transfered_amount',
        'asset_mortgage_rate_percent',
        'asset_taxable_percent',
        'asset_taxable_amount',
        'asset_taxable_initial_amount',
        'asset_taxable_amount_override',
        'asset_tax_percent',
        'asset_tax_amount',
        'asset_taxable_property_percent',
        'asset_taxable_property_amount',
        'asset_tax_property_percent',
        'asset_tax_property_amount',
        'asset_changerate',
        'asset_changerate_percent',
        'asset_rule',
        'asset_transfer',
        'asset_source',
        'asset_repeat',
        // Mortgage fields
        'mortgage_amount',
        'mortgage_term_amount',
        'mortgage_interest_amount',
        'mortgage_principal_amount',
        'mortgage_balance_amount',
        'mortgage_extra_downpayment_amount',
        'mortgage_transfered_amount',
        'mortgage_interest_percent',
        'mortgage_years',
        'mortgage_gebyr_amount',
        'mortgage_tax_deductable_amount',
        'mortgage_tax_deductable_percent',
        // Realization fields
        'realization_description',
        'realization_amount',
        'realization_taxable_amount',
        'realization_tax_amount',
        'realization_tax_percent',
        'realization_tax_shield_amount',
        'realization_tax_shield_percent',
        // Yield fields
        'yield_brutto_percent',
        'yield_netto_percent',
        // Potential fields
        'potential_income_amount',
        'potential_mortgage_amount',
        // F.I.R.E. fields
        'fire_percent',
        'fire_income_amount',
        'fire_expence_amount',
        'fire_rate_percent',
        'fire_cashflow_amount',
        'fire_savings_amount',
        'fire_savings_rate_percent',
        // Audit fields
        'created_by',
        'updated_by',
        'created_checksum',
        'updated_checksum',
    ];

    protected $casts = [
        'year' => 'integer',
        // Income
        'income_amount' => 'decimal:2',
        'income_transfer_amount' => 'decimal:2',
        'income_repeat' => 'boolean',
        // Expense
        'expence_amount' => 'decimal:2',
        'expence_transfer_amount' => 'decimal:2',
        'expence_repeat' => 'boolean',
        // Cashflow
        'cashflow_after_taxamount' => 'decimal:2',
        'cashflow_before_taxamount' => 'decimal:2',
        'cashflow_before_tax_aggregated_amount' => 'decimal:2',
        'cashflow_after_tax_aggregatedamount' => 'decimal:2',
        'cashflow_tax_amount' => 'decimal:2',
        'cashflow_tax_percent' => 'decimal:2',
        'cashflow_transfer_amount' => 'decimal:2',
        'cashflow_repeat' => 'boolean',
        // Asset
        'asset_market_amount' => 'decimal:2',
        'asset_market_mortgage_deducted_amount' => 'decimal:2',
        'asset_acquisition_amount' => 'decimal:2',
        'asset_acquisition_initial_amount' => 'decimal:2',
        'asset_equity_amount' => 'decimal:2',
        'asset_equity_initial_amount' => 'decimal:2',
        'asset_paid_amount' => 'decimal:2',
        'asset_paid_initial_amount' => 'decimal:2',
        'asset_transfered_amount' => 'decimal:2',
        'asset_mortgage_rate_percent' => 'decimal:2',
        'asset_taxable_percent' => 'decimal:2',
        'asset_taxable_amount' => 'decimal:2',
        'asset_taxable_initial_amount' => 'decimal:2',
        'asset_taxable_amount_override' => 'boolean',
        'asset_tax_percent' => 'decimal:2',
        'asset_tax_amount' => 'decimal:2',
        'asset_taxable_property_percent' => 'decimal:2',
        'asset_taxable_property_amount' => 'decimal:2',
        'asset_tax_property_percent' => 'decimal:2',
        'asset_tax_property_amount' => 'decimal:2',
        'asset_changerate_percent' => 'decimal:2',
        'asset_repeat' => 'boolean',
        // Mortgage
        'mortgage_amount' => 'decimal:2',
        'mortgage_term_amount' => 'decimal:2',
        'mortgage_interest_amount' => 'decimal:2',
        'mortgage_principal_amount' => 'decimal:2',
        'mortgage_balance_amount' => 'decimal:2',
        'mortgage_extra_downpayment_amount' => 'decimal:2',
        'mortgage_transfered_amount' => 'decimal:2',
        'mortgage_years' => 'integer',
        'mortgage_gebyr_amount' => 'decimal:2',
        'mortgage_tax_deductable_amount' => 'decimal:2',
        'mortgage_tax_deductable_percent' => 'decimal:2',
        // Realization
        'realization_amount' => 'decimal:2',
        'realization_taxable_amount' => 'decimal:2',
        'realization_tax_amount' => 'decimal:2',
        'realization_tax_percent' => 'decimal:2',
        'realization_tax_shield_amount' => 'decimal:2',
        'realization_tax_shield_percent' => 'decimal:2',
        // Yield
        'yield_brutto_percent' => 'decimal:2',
        'yield_netto_percent' => 'decimal:2',
        // Potential
        'potential_income_amount' => 'decimal:2',
        'potential_mortgage_amount' => 'decimal:2',
        // F.I.R.E.
        'fire_percent' => 'decimal:2',
        'fire_income_amount' => 'decimal:2',
        'fire_expence_amount' => 'decimal:2',
        'fire_rate_percent' => 'decimal:2',
        'fire_cashflow_amount' => 'decimal:2',
        'fire_savings_amount' => 'decimal:2',
        'fire_savings_rate_percent' => 'decimal:2',
    ];

    // Factor enum constants
    public const FACTOR_MONTHLY = 'monthly';

    public const FACTOR_YEARLY = 'yearly';

    /**
     * Get valid factor options
     */
    public static function getFactorOptions(): array
    {
        return [
            self::FACTOR_MONTHLY => 'Monthly',
            self::FACTOR_YEARLY => 'Yearly',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function assetConfiguration(): BelongsTo
    {
        return $this->belongsTo(AssetConfiguration::class);
    }

    public function simulationAsset(): BelongsTo
    {
        return $this->belongsTo(SimulationAsset::class);
    }

    /**
     * Convert factor enum to numeric multiplier for calculations
     */
    public function getIncomeFactorMultiplier(): int
    {
        return $this->income_factor === self::FACTOR_MONTHLY ? 12 : 1;
    }

    /**
     * Convert factor enum to numeric multiplier for calculations
     */
    public function getExpenseFactorMultiplier(): int
    {
        return $this->expence_factor === self::FACTOR_MONTHLY ? 12 : 1;
    }

    /**
     * Get total annual income amount
     */
    public function getTotalIncomeAmount(): float
    {
        return ($this->income_amount ?? 0) * $this->getIncomeFactorMultiplier();
    }

    /**
     * Get total annual expense amount
     */
    public function getTotalExpenseAmount(): float
    {
        return ($this->expence_amount ?? 0) * $this->getExpenseFactorMultiplier();
    }

    /**
     * Calculate net cashflow after tax
     */
    public function getNetCashflowAmount(): float
    {
        return $this->cashflow_after_taxamount ?? 0;
    }

    /**
     * Calculate F.I.R.E. savings rate
     */
    public function getFireSavingsRatePercent(): float
    {
        return $this->fire_savings_rate_percent ?? 0;
    }

    /**
     * Check if asset is on track for F.I.R.E. goals
     */
    public function isOnTrackForFire(): bool
    {
        return $this->getFireSavingsRatePercent() >= 25; // 25% savings rate is common F.I.R.E. target
    }
}
