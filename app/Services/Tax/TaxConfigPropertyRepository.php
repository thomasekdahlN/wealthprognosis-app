<?php

declare(strict_types=1);

namespace App\Services\Tax;

use App\Models\TaxProperty;
use App\Support\ValueObjects\PropertyTaxConfig;

/**
 * Repository for property tax data with year fallback and in-memory caching.
 *
 * Retrieves property tax rates and deductions from the TaxProperty model,
 * falling back to the closest previous year if exact year is not found.
 */
class TaxConfigPropertyRepository
{
    /**
     * Cache structure: [country][code][year][taxGroup] => PropertyTaxConfig
     *
     * @var array<string, array<string, array<int, array<string, PropertyTaxConfig>>>>
     */
    private array $cache = [];

    private string $country;

    public function __construct(string $country = 'no')
    {
        $this->country = strtolower($country);
    }

    /**
     * Get the property tax configuration (rate and deduction).
     *
     * Returns both the property tax rate and deduction amount for a given
     * municipality and tax group. The rate is based on the tax group (private/company),
     * while the deduction is the same for both groups.
     *
     * @param  string  $taxGroup  The tax group ('private' or 'company').
     * @param  string  $taxPropertyArea  The municipality/property code.
     * @param  int  $year  The year for which the tax is being calculated.
     * @return PropertyTaxConfig Value object containing tax rate and deduction amount.
     */
    public function getPropertyTaxConfig(string $taxGroup, string $taxPropertyArea, int $year): PropertyTaxConfig
    {
        $cacheKey = $taxGroup;

        if (isset($this->cache[$this->country][$taxPropertyArea][$year][$cacheKey])) {
            return $this->cache[$this->country][$taxPropertyArea][$year][$cacheKey];
        }

        try {
            // Find the most recent record at or before the requested year
            $taxPropertyAreaModel = TaxProperty::query()
                ->forCountry($this->country)
                ->where('code', $taxPropertyArea)
                ->where('year', '<=', $year)
                ->active()
                ->orderByDesc('year')
                ->first();

            if ($taxPropertyAreaModel) {
                // Determine which rate to use based on tax group
                if ($taxGroup === 'company' && $taxPropertyAreaModel->tax_company_permill) {
                    // Convert permille to decimal (e.g., 3.5 permille = 0.0035)
                    $rate = (float) $taxPropertyAreaModel->tax_company_permill / 1000;
                } elseif ($taxGroup === 'private' && $taxPropertyAreaModel->tax_home_permill) {
                    // Convert permille to decimal (e.g., 3.5 permille = 0.0035)
                    $rate = (float) $taxPropertyAreaModel->tax_home_permill / 1000;
                } else {
                    $rate = 0.0;
                }

                // Get deduction amount (same for both private and company)
                $deduction = $taxPropertyAreaModel->deduction ? (float) $taxPropertyAreaModel->deduction : 0.0;

                // Get taxable percent (percentage of market value that is taxable, e.g., 70.00 for 70%)
                $taxablePercent = $taxPropertyAreaModel->taxable_percent ? (float) $taxPropertyAreaModel->taxable_percent : 100.0;
            } else {
                $rate = 0.0;
                $deduction = 0.0;
                $taxablePercent = 100.0;
            }
        } catch (\Throwable $e) {
            $rate = 0.0;
            $deduction = 0.0;
            $taxablePercent = 100.0;
        }

        // Create and cache the result
        $config = new PropertyTaxConfig(
            taxRate: $rate,
            deductionAmount: $deduction,
            taxablePercent: $taxablePercent
        );

        $this->cache[$this->country][$taxPropertyArea][$year][$cacheKey] = $config;

        return $config;
    }
}
