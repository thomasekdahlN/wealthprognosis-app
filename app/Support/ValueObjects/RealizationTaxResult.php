<?php

namespace App\Support\ValueObjects;

/**
 * Value object for realization (capital gains) tax calculation results.
 *
 * Represents the result of calculating realization tax for asset sales/transfers,
 * including tax shield calculations.
 *
 * All amount fields are automatically cast to integers in the constructor.
 */
readonly class RealizationTaxResult
{
    public int $acquisitionAmount;

    public int $taxableAmount;

    public int $taxAmount;

    public int $taxShieldAmount;

    public function __construct(
        float $acquisitionAmount,
        float $taxableAmount,
        float $taxAmount,
        public float $taxPercent,
        public float $taxRate,
        float $taxShieldAmount,
        public float $taxShieldPercent,
        public float $taxShieldRate,
        public string $explanation
    ) {
        $this->acquisitionAmount = (int) round($acquisitionAmount);
        $this->taxableAmount = (int) round($taxableAmount);
        $this->taxAmount = (int) round($taxAmount);
        $this->taxShieldAmount = (int) round($taxShieldAmount);
    }

    /**
     * Convert to array for backward compatibility.
     *
     * @return array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float, 6: float, 7: float, 8: string}
     */
    public function toArray(): array
    {
        return [
            $this->acquisitionAmount,
            $this->taxableAmount,
            $this->taxAmount,
            $this->taxPercent,
            $this->taxRate,
            $this->taxShieldAmount,
            $this->taxShieldPercent,
            $this->taxShieldRate,
            $this->explanation,
        ];
    }
}
