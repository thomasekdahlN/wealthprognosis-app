<?php

namespace App\Support\ValueObjects;

/**
 * Value object for realization (capital gains) tax calculation results.
 *
 * Represents the result of calculating realization tax for asset sales/transfers,
 * including tax shield calculations.
 */
readonly class RealizationTaxResult
{
    public function __construct(
        public float $acquisitionAmount,
        public float $taxableAmount,
        public float $taxAmount,
        public float $taxPercent,
        public float $taxRate,
        public float $taxShieldAmount,
        public float $taxShieldPercent,
        public float $taxShieldRate,
        public string $explanation
    ) {}

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
