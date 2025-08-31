<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxConfiguration extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'country_code',
        'year',
        'tax_type',
        'income_tax_rate',
        'realization_tax_rate',
        'fortune_tax_rate',
        'property_tax_rate',
        'standard_deduction',
        'fortune_tax_threshold_low',
        'fortune_tax_threshold_high',
        'fortune_tax_rate_low',
        'fortune_tax_rate_high',
        'tax_shield_rate',
        'is_active',
        'configuration_data',
        'description',
    ];

    protected $casts = [
        'year' => 'integer',
        'income_tax_rate' => 'decimal:4',
        'realization_tax_rate' => 'decimal:4',
        'fortune_tax_rate' => 'decimal:4',
        'property_tax_rate' => 'decimal:4',
        'standard_deduction' => 'decimal:2',
        'fortune_tax_threshold_low' => 'decimal:2',
        'fortune_tax_threshold_high' => 'decimal:2',
        'fortune_tax_rate_low' => 'decimal:4',
        'fortune_tax_rate_high' => 'decimal:4',
        'tax_shield_rate' => 'decimal:4',
        'is_active' => 'boolean',
        'configuration_data' => 'json',
    ];

    // Asset types that support tax configurations
    public const TAX_TYPES = [
        'house' => 'House',
        'rental' => 'Rental Property',
        'cabin' => 'Cabin',
        'car' => 'Car',
        'boat' => 'Boat',
        'cash' => 'Cash',
        'bank' => 'Bank Account',
        'equityfund' => 'Equity Fund',
        'bondfund' => 'Bond Fund',
        'stock' => 'Stock',
        'crypto' => 'Cryptocurrency',
        'gold' => 'Gold',
        'otp' => 'Occupational Pension',
        'pension' => 'Public Pension',
        'ips' => 'Individual Pension Savings',
        'ask' => 'Equity Savings Account',
        'inheritance' => 'Inheritance',
        'salary' => 'Salary',
        'income' => 'Other Income',
        'child' => 'Child',
        'soleproprietorship' => 'Sole Proprietorship',
        'loantocompany' => 'Loan to Company',
        'airbnb' => 'Airbnb',
        'property' => 'Property',
    ];

    // Country codes
    public const COUNTRIES = [
        'no' => 'Norway',
        'se' => 'Sweden',
        'dk' => 'Denmark',
        'us' => 'United States',
        'en' => 'United Kingdom',
    ];

    // Helper methods
    public function getTaxTypeLabel(): string
    {
        return self::TAX_TYPES[$this->tax_type] ?? $this->tax_type;
    }

    public function getCountryLabel(): string
    {
        return self::COUNTRIES[$this->country_code] ?? $this->country_code;
    }

    public function getIncomeTaxDecimal(): float
    {
        return $this->income_tax_rate / 100;
    }

    public function getRealizationTaxDecimal(): float
    {
        return $this->realization_tax_rate / 100;
    }

    public function getFortuneTaxDecimal(): float
    {
        return $this->fortune_tax_rate / 100;
    }

    public function getPropertyTaxDecimal(): float
    {
        return $this->property_tax_rate / 100;
    }

    // Scope methods
    public function scopeForCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForTaxType($query, string $taxType)
    {
        return $query->where('tax_type', $taxType);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
