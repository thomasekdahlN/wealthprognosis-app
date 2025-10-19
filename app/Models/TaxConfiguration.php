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
        'description',
        'is_active',
        'configuration',
    ];

    protected $casts = [
        'year' => 'integer',
        'is_active' => 'boolean',
        'configuration' => 'json',
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
        'ch' => 'Switzerland',
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

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class, 'tax_type', 'type');
    }
}
