<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrognosisResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'prognosis_id',
        'asset_id',
        'year',
        'age',
        'prognosis_type',

        // Income data
        'income_amount',
        'income_tax_amount',
        'income_after_tax_amount',

        // Expense data
        'expense_amount',
        'expense_after_tax_amount',

        // Asset data
        'asset_market_amount',
        'asset_equity_amount',
        'asset_taxable_amount',
        'asset_tax_amount',

        // Mortgage data
        'mortgage_balance_amount',
        'mortgage_payment_amount',
        'mortgage_interest_amount',
        'mortgage_principal_amount',

        // Cashflow data
        'cashflow_before_tax_amount',
        'cashflow_after_tax_amount',
        'cashflow_accumulated_amount',

        // Tax data
        'total_tax_amount',
        'fortune_tax_amount',
        'property_tax_amount',

        // FIRE data
        'fire_income_amount',
        'fire_expense_amount',
        'fire_rate_decimal',
        'fire_cashflow_amount',
        'fire_saving_amount',
        'fire_saving_rate_decimal',

        // Realization data
        'realization_amount',
        'realization_tax_amount',
        'realization_after_tax_amount',

        // Yield data
        'yield_gross_percent',
        'yield_net_percent',

        // Additional data
        'change_rate_percent',
        'transferred_amount',
        'description',
        'calculation_data',
    ];

    protected $casts = [
        'year' => 'integer',
        'age' => 'integer',
        'income_amount' => 'decimal:2',
        'income_tax_amount' => 'decimal:2',
        'income_after_tax_amount' => 'decimal:2',
        'expense_amount' => 'decimal:2',
        'expense_after_tax_amount' => 'decimal:2',
        'asset_market_amount' => 'decimal:2',
        'asset_equity_amount' => 'decimal:2',
        'asset_taxable_amount' => 'decimal:2',
        'asset_tax_amount' => 'decimal:2',
        'mortgage_balance_amount' => 'decimal:2',
        'mortgage_payment_amount' => 'decimal:2',
        'mortgage_interest_amount' => 'decimal:2',
        'mortgage_principal_amount' => 'decimal:2',
        'cashflow_before_tax_amount' => 'decimal:2',
        'cashflow_after_tax_amount' => 'decimal:2',
        'cashflow_accumulated_amount' => 'decimal:2',
        'total_tax_amount' => 'decimal:2',
        'fortune_tax_amount' => 'decimal:2',
        'property_tax_amount' => 'decimal:2',
        'fire_income_amount' => 'decimal:2',
        'fire_expense_amount' => 'decimal:2',
        'fire_rate_decimal' => 'decimal:4',
        'fire_cashflow_amount' => 'decimal:2',
        'fire_saving_amount' => 'decimal:2',
        'fire_saving_rate_decimal' => 'decimal:4',
        'realization_amount' => 'decimal:2',
        'realization_tax_amount' => 'decimal:2',
        'realization_after_tax_amount' => 'decimal:2',
        'yield_gross_percent' => 'decimal:4',
        'yield_net_percent' => 'decimal:4',
        'change_rate_percent' => 'decimal:4',
        'transferred_amount' => 'decimal:2',
        'calculation_data' => 'json',
    ];

    public function prognosis(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PrognosisType::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    // Helper methods
    public function getNetWorth(): float
    {
        return $this->asset_market_amount - $this->mortgage_balance_amount;
    }

    public function getTotalTaxRate(): float
    {
        if ($this->income_amount <= 0) {
            return 0;
        }

        return $this->total_tax_amount / $this->income_amount;
    }

    public function isFireAchieved(): bool
    {
        return $this->fire_rate_decimal >= 1.0;
    }

    public function getFirePercentage(): float
    {
        return $this->fire_rate_decimal * 100;
    }

    // Scope methods
    public function scopeForPrognosis($query, int $prognosisId)
    {
        return $query->where('prognosis_id', $prognosisId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForYearRange($query, int $startYear, int $endYear)
    {
        return $query->whereBetween('year', [$startYear, $endYear]);
    }

    public function scopeForAsset($query, int $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    public function scopeFireAchieved($query)
    {
        return $query->where('fire_rate_decimal', '>=', 1.0);
    }
}
