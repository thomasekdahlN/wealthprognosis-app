<?php

declare(strict_types=1);

namespace App\Services\Tax;

use App\Models\AssetType;
use App\Models\TaxConfiguration;
use App\Models\TaxProperty;
use Illuminate\Support\Arr;

/**
 * Centralized loader for tax_configurations with year fallback and in-memory caching.
 *
 * Caches results per request to avoid repeated DB queries during a single run.
 */
class TaxConfigRepository
{
    /**
     * [country][taxType][year] => configuration array
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
     * Fetch configuration for a given country and year with a specific tax type, falling back to
     * the most recent configuration at or before the requested year.
     *
     * Supports both signatures:
     * - getTaxConfig(country, year, taxType)
     * - getTaxConfig(taxType, year) // uses instance country
     *
     * @return array<string, mixed> Configuration array (may be empty if not found).
     */
    public function getTaxConfig(string $arg1, int $year, ?string $taxType = null): array
    {
        $country = $this->country;
        if ($taxType === null) {
            // Backward-compatible: first argument is taxType
            $taxType = $arg1;
        } else {
            // New signature: first argument is country
            $country = strtolower($arg1);
        }

        if (isset($this->cache[$country][$taxType][$year])) {
            return $this->cache[$country][$taxType][$year];
        }

        $record = TaxConfiguration::query()
            ->active()
            ->forCountry($country)
            ->forTaxType($taxType)
            ->where('year', '<=', $year)
            ->orderByDesc('year')
            ->first();

        $config = $record?->configuration ?? [];
        $this->cache[$country][$taxType][$year] = $config;

        return $config;
    }

    /**
     * Shortcut for property tax configs using tax_type like "property_{code}".
     *
     * @param  string  $propertyCode  Municipality/property code (e.g., 'holmestrand').
     * @return array<string, mixed>
     */
    public function getTaxIncomeRate(string $taxType, int $year): float
    {
        $configH = $this->getTaxConfig($this->country, $year, $taxType);

        return Arr::get($configH, 'income', 0) / 100;
    }

    // Fortune spesific helper functions.
    // Returns the portion of the fortune that is taxable.
    public function getTaxFortuneTaxableRate(string $taxType, int $year): float
    {
        $configH = $this->getTaxConfig($this->country, $year, $taxType);

        return Arr::get($configH, 'fortune', 0) / 100;
    }

    /**
     * Returns the fortune tax percentage.
     *
     * @param  int  $year  The year for which the tax is being calculated.
     * @param  string  $type  The type of tax.
     * @return float The fortune tax percentage.
     */
    public function getFortuneTaxRate(int $year, string $type): float
    {
        $configH = $this->getTaxConfig($this->country, $year, 'fortune');

        return Arr::get($configH, "$type.income", 0) / 100;
    }

    /**
     * Returns the standard deduction for the fortune tax.
     *
     * @param  string  $taxGroup  The tax group.
     * @param  int  $year  The year for which the tax is being calculated.
     * @return int The standard deduction for the fortune tax.
     */
    public function getFortuneTaxStandardDeduction(string $taxGroup, int $year): int
    {
        $configH = $this->getTaxConfig($this->country, $year, 'fortune');

        return (int) Arr::get($configH, 'standardDeduction', 0);
    }

    // ** Realization spesific helper functions */
    public function getTaxRealizationRate(string $taxType, int $year): float
    {
        $configH = $this->getTaxConfig($this->country, $year, $taxType);

        return Arr::get($configH, 'realization', 0) / 100;
    }

    public function getTaxStandardDeductionAmount(string $taxType, int $year): float
    {
        $configH = $this->getTaxConfig($this->country, $year, $taxType);

        return Arr::get($configH, 'standardDeduction', 0);
    }

    public function getTaxShieldAmount(int $year): float
    {
        $config = $this->getTaxConfig($this->country, $year, 'shareholdershield');
        if (is_array($config)) {
            $value = Arr::get($config, (string) $year, Arr::get($config, 'all', 0));

            return (float) $value;
        }

        return (float) ($config ?? 0);
    }

    /**
     * Determine if a given asset type participates in the tax shield scheme.
     *
     * The parameter represents the asset type key (e.g., 'stock', 'equityfund').
     * We check the AssetType registry and return its `tax_shield` boolean.
     * Falls back to searching by `tax_type` as some datasets may pass the mapped tax type.
     */
    public function hasTaxShield(string $taxType): bool
    {
        if ($taxType === '') {
            return false;
        }

        // In-request cache to avoid repetitive queries during long computations
        static $cache = [];
        if (array_key_exists($taxType, $cache)) {
            return $cache[$taxType];
        }

        try {
            // Prefer explicit tax_type mapping when present
            $flag = AssetType::query()->where('tax_type', $taxType)->value('tax_shield');
            if ($flag === null) {
                // Fallback: some call sites pass the asset 'type' code directly
                $flag = AssetType::query()->where('type', $taxType)->value('tax_shield');
            }

            return $cache[$taxType] = (bool) ($flag ?? false);
        } catch (\Throwable $e) {
            return $cache[$taxType] = false;
        }
    }

    public function getTaxShieldRealizationRate($taxType, $year)
    {
        $percent = 0.0;

        if ($this->hasTaxShield($taxType)) {
            $value = $this->getTaxShieldAmount($year);

            $percent = ((float) $value) / 100;
        }

        return $percent;
    }

    // *****************************************************************************
    // Helper functions to retrieve correct salary tax config
    private function getTaxSalaryConfig(string $TaxSubType, int $year): array
    {
        $configH = $this->getTaxConfig($this->country, $year, 'salary');

        return $configH[$TaxSubType] ?? [];
    }

    public function getSalaryTaxCommonRate(int $year): float
    {
        $taxSalaryConfigH = $this->getTaxSalaryConfig('common', (int) $year);

        return Arr::get($taxSalaryConfigH, 'rate', 0) / 100;
    }

    public function getSalaryTaxDeductionConfig($year): array
    {
        return $this->getTaxSalaryConfig('deduction', (int) $year);
    }

    public function getSalaryTaxSocialSecurityRate($year): float
    {
        $taxSalaryConfigH = $this->getTaxSalaryConfig('socialsecurity', (int) $year);

        return Arr::get($taxSalaryConfigH, 'rate', 0) / 100;
    }

    public function getSalaryTaxBracketConfig($year): array
    {
        return $this->getTaxSalaryConfig('bracket', (int) $year);
    }

    // ******************************************************************************
    // Helper functions for property tax

    /**
     * Returns the taxable portion of the property.
     * First tries to get from JSON config, then falls back to database TaxProperty model.
     *
     * @param  string  $taxGroup  The tax group (e.g., 'private', 'company').
     * @param  string  $taxProperty  The type of property (municipality code).
     * @param  int  $year  The year for which the tax is being calculated.
     * @return float The taxable portion of the property as a decimal (e.g., 0.70 for 70%).
     */
    public function getPropertyTaxable(string $taxGroup, string $taxProperty, int $year): float
    {
        try {
            $taxPropertyModel = TaxProperty::query()
                ->forCountry($this->country)
                ->forYear($year)
                ->where('code', $taxProperty)
                ->active()
                ->first();

            if ($taxPropertyModel && $taxPropertyModel->fortune_taxable_percent !== null) {
                // Database stores as percentage (e.g., 70.00), return as decimal (0.70)
                return (float) ($taxPropertyModel->fortune_taxable_percent / 100);
            }
        } catch (\Throwable $e) {
            // Ignore and continue to default
        }

        // Default to 0 if neither source has the value
        return 0.0;
    }

    /**
     * Returns the standard deduction for the property tax.
     *
     * @param  string  $taxGroup  The tax group.
     * @param  string  $taxProperty  The type of property.
     * @param  int  $year  The year for which the tax is being calculated.
     * @return int The standard deduction for the property tax.
     */
    public function getPropertyTaxStandardDeductionAmount($taxGroup, $taxProperty, $year): int
    {
        $taxConfigH = $this->getTaxSalaryConfig('property', (int) $year);

        return Arr::get($taxConfigH, "$taxProperty.standardDeduction", 0);
    }

    /**
     * Returns the property tax percentage.
     *
     * @param  string  $taxGroup  The tax group.
     * @param  string  $taxProperty  The type of property.
     * @param  int  $year  The year for which the tax is being calculated.
     * @return float The property tax percentage.
     */
    public function getPropertyTaxRate(string $taxGroup, string $taxProperty, int $year): float
    {
        $taxConfigH = $this->getTaxSalaryConfig('property', $year);

        return Arr::get($taxConfigH, "$taxProperty.income", 0) / 100;
    }

    // Backward-compatibility wrappers aligning with existing callers
    public function getPropertyTax(string $taxGroup, string $taxProperty, int $year): float
    {
        return $this->getPropertyTaxRate($taxGroup, $taxProperty, $year);
    }

    public function getPropertyTaxStandardDeduction(string $taxGroup, string $taxProperty, int $year): int
    {
        return $this->getPropertyTaxStandardDeductionAmount($taxGroup, $taxProperty, $year);
    }
}
