<?php

declare(strict_types=1);

namespace App\Services\Tax;

use App\Models\AssetType;
use App\Models\TaxConfiguration;
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
    public function getTaxConfig(int $year, ?string $taxType = null): array
    {

        if (isset($this->cache[$this->country][$taxType][$year])) {
            return $this->cache[$this->country][$taxType][$year];
        }

        $record = TaxConfiguration::query()
            ->active()
            ->forCountry($this->country)
            ->forTaxType($taxType)
            ->where('year', '<=', $year)
            ->orderByDesc('year')
            ->first();

        $config = $record?->configuration ?? [];
        $this->cache[$this->country][$taxType][$year] = $config;

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
        $configH = $this->getTaxConfig($year, $taxType);

        return Arr::get($configH, 'income', 0) / 100;
    }

    // ***************************************************************************************
    // Fortune spesific helper functions.
    // Returns the percentage of the value of this taxtype that is taxable.
    public function getTaxFortuneTaxableRate(string $taxType, int $year): float
    {
        $configH = $this->getTaxConfig($year, $taxType);

        return Arr::get($configH, 'fortune', 0) / 100;
    }

    /**
     * Returns the fortune tax bracket configuration.
     * Similar to salary tax brackets, supports dynamic number of tax levels.
     *
     * @param  int  $year  The year for which the tax is being calculated.
     * @return array The fortune tax bracket configuration.
     */
    public function getFortuneTaxBracketConfig(int $year): array
    {
        $configH = $this->getTaxConfig($year, 'fortune');

        return Arr::get($configH, 'bracket', []);
    }

    // ***************************************************************************************
    // ** Realization spesific helper functions */
    public function getTaxRealizationRate(string $taxType, int $year): float
    {
        $configH = $this->getTaxConfig($year, $taxType);

        return Arr::get($configH, 'realization', 0) / 100;
    }

    public function getTaxStandardDeductionAmount(string $taxType, int $year): float
    {
        $configH = $this->getTaxConfig($year, $taxType);

        return Arr::get($configH, 'standardDeduction', 0);
    }

    public function getTaxShieldAmount(int $year): float
    {
        $config = $this->getTaxConfig($year, 'shareholdershield');

        return (float) Arr::get($config, 'percent', 0);
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

    public function getTaxShieldRealizationRate(string $taxType, int $year): float
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
        $configH = $this->getTaxConfig($year, 'salary');

        return $configH[$TaxSubType] ?? [];
    }

    public function getSalaryTaxCommonRate(int $year): float
    {
        $taxSalaryConfigH = $this->getTaxSalaryConfig('common', (int) $year);

        return Arr::get($taxSalaryConfigH, 'percent', 0) / 100;
    }

    public function getSalaryTaxDeductionConfig($year): array
    {
        return $this->getTaxSalaryConfig('deduction', (int) $year);
    }

    public function getSalaryTaxSocialSecurityRate($year): float
    {
        $taxSalaryConfigH = $this->getTaxSalaryConfig('socialsecurity', (int) $year);

        return Arr::get($taxSalaryConfigH, 'percent', 0) / 100;
    }

    public function getSalaryTaxBracketConfig($year): array
    {
        return $this->getTaxSalaryConfig('bracket', (int) $year);
    }
}
