<?php

/* Copyright (C) 2025 Thomas Ekdahl
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class TaxConfiguration
 *
 * Eloquent model representing tax configuration records stored in the database.
 * Each record contains tax rules for a specific country, year, and tax type.
 * Replaces the legacy JSON file-based tax configuration system.
 *
 * @property string $country_code Two-letter country code (e.g., 'no', 'se')
 * @property int $year The tax year this configuration applies to
 * @property string $tax_type The type of asset/income this tax applies to
 * @property string|null $description Optional description of this configuration
 * @property bool $is_active Whether this configuration is currently active
 * @property array $configuration JSON configuration data containing tax rates and rules
 */
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
        'created_by',
        'updated_by',
        'created_checksum',
        'updated_checksum',
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

    /**
     * Get the human-readable label for the tax type.
     *
     * @return string The tax type label (e.g., 'Equity Fund' for 'equityfund')
     */
    public function getTaxTypeLabel(): string
    {
        return self::TAX_TYPES[$this->tax_type] ?? $this->tax_type;
    }

    /**
     * Get the human-readable label for the country code.
     *
     * @return string The country name (e.g., 'Norway' for 'no')
     */
    public function getCountryLabel(): string
    {
        return self::COUNTRIES[$this->country_code] ?? $this->country_code;
    }

    /**
     * Scope query to a specific country.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $countryCode  Two-letter country code
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Scope query to a specific year.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $year  The tax year
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope query to a specific tax type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $taxType  The tax type code
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTaxType($query, string $taxType)
    {
        return $query->where('tax_type', $taxType);
    }

    /**
     * Scope query to only active configurations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the user who created this configuration.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this configuration.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the tax type definition for this configuration.
     */
    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class, 'tax_type', 'type');
    }
}
