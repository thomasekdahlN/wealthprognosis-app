<?php

declare(strict_types=1);

namespace App\Services\Tax;

use App\Models\TaxProperty;

/**
 * Repository for property tax data with year fallback and in-memory caching.
 *
 * Retrieves property tax rates, deductions, and taxable percentages from the
 * TaxProperty model, falling back to the closest previous year if exact year is not found.
 */
class TaxConfigPropertyRepository
{
    /**
     * Cache structure: [country][code][year][field] => value
     *
     * @var array<string, array<string, array<int, array<string, mixed>>>>
     */
    private array $cache = [];

    private string $country;

    public function __construct(string $country = 'no')
    {
        $this->country = strtolower($country);
    }

    /**
     * Get the taxable portion of property for wealth tax purposes.
     *
     * Returns the percentage of property value that is taxable for wealth tax.
     * For example, 70.00 in the database means 70% of the property value is taxable.
     *
     * @param  string  $taxGroup  The tax group (e.g., 'private', 'company') - currently unused but kept for API compatibility.
     * @param  string  $taxProperty  The municipality/property code.
     * @param  int  $year  The year for which the tax is being calculated.
     * @return float The taxable portion as a decimal (e.g., 0.70 for 70%).
     */
    public function getPropertyTaxableRate(string $taxGroup, string $taxProperty, int $year): float
    {
        $cacheKey = 'taxable_rate';

        if (isset($this->cache[$this->country][$taxProperty][$year][$cacheKey])) {
            return $this->cache[$this->country][$taxProperty][$year][$cacheKey];
        }

        try {
            // Find the most recent record at or before the requested year
            $taxPropertyModel = TaxProperty::query()
                ->forCountry($this->country)
                ->where('code', $taxProperty)
                ->where('year', '<=', $year)
                ->active()
                ->orderByDesc('year')
                ->first();

            if ($taxPropertyModel && $taxPropertyModel->fortune_taxable_percent !== null) {
                // Database stores as percentage (e.g., 70.00), return as decimal (0.70)
                $rate = (float) ($taxPropertyModel->fortune_taxable_percent / 100);
            } else {
                $rate = 0.0;
            }
        } catch (\Throwable $e) {
            $rate = 0.0;
        }

        // Cache the result
        $this->cache[$this->country][$taxProperty][$year][$cacheKey] = $rate;

        return $rate;
    }

    /**
     * Get the standard deduction amount for property tax.
     *
     * Returns the deduction amount (bunnfradrag) that is subtracted from the
     * taxable property value before calculating property tax.
     *
     * @param  string  $taxGroup  The tax group (e.g., 'private', 'company') - currently unused but kept for API compatibility.
     * @param  string  $taxProperty  The municipality/property code.
     * @param  int  $year  The year for which the tax is being calculated.
     * @return float The standard deduction amount.
     */
    public function getPropertyTaxStandardDeductionAmount(string $taxGroup, string $taxProperty, int $year): float
    {
        $cacheKey = 'deduction';

        if (isset($this->cache[$this->country][$taxProperty][$year][$cacheKey])) {
            return $this->cache[$this->country][$taxProperty][$year][$cacheKey];
        }

        try {
            // Find the most recent record at or before the requested year
            $taxPropertyModel = TaxProperty::query()
                ->forCountry($this->country)
                ->where('code', $taxProperty)
                ->where('year', '<=', $year)
                ->active()
                ->orderByDesc('year')
                ->first();

            if ($taxPropertyModel && $taxPropertyModel->deduction !== null) {
                $deduction = (float) $taxPropertyModel->deduction;
            } else {
                $deduction = 0.0;
            }
        } catch (\Throwable $e) {
            $deduction = 0.0;
        }

        // Cache the result
        $this->cache[$this->country][$taxProperty][$year][$cacheKey] = $deduction;

        return $deduction;
    }

    /**
     * Get the property tax rate (permille).
     *
     * Returns the property tax rate based on the tax group (private/company).
     * The rate is stored in permille in the database and converted to a decimal.
     *
     * @param  string  $taxGroup  The tax group ('private' or 'company').
     * @param  string  $taxProperty  The municipality/property code.
     * @param  int  $year  The year for which the tax is being calculated.
     * @return float The property tax rate as a decimal (e.g., 0.0035 for 3.5 permille).
     */
    public function getPropertyTaxRate(string $taxGroup, string $taxProperty, int $year): float
    {
        $cacheKey = "tax_rate_{$taxGroup}";

        if (isset($this->cache[$this->country][$taxProperty][$year][$cacheKey])) {
            return $this->cache[$this->country][$taxProperty][$year][$cacheKey];
        }

        try {
            // Find the most recent record at or before the requested year
            $taxPropertyModel = TaxProperty::query()
                ->forCountry($this->country)
                ->where('code', $taxProperty)
                ->where('year', '<=', $year)
                ->active()
                ->orderByDesc('year')
                ->first();

            if ($taxPropertyModel) {
                // Determine which rate to use based on tax group
                if ($taxGroup === 'company' && $taxPropertyModel->tax_company_permill !== null) {
                    // Convert permille to decimal (e.g., 3.5 permille = 0.0035)
                    $rate = (float) ($taxPropertyModel->tax_company_permill / 1000);
                } elseif ($taxGroup === 'private' && $taxPropertyModel->tax_home_permill !== null) {
                    // Convert permille to decimal (e.g., 3.5 permille = 0.0035)
                    $rate = (float) ($taxPropertyModel->tax_home_permill / 1000);
                } else {
                    $rate = 0.0;
                }
            } else {
                $rate = 0.0;
            }
        } catch (\Throwable $e) {
            $rate = 0.0;
        }

        // Cache the result
        $this->cache[$this->country][$taxProperty][$year][$cacheKey] = $rate;

        return $rate;
    }
}
