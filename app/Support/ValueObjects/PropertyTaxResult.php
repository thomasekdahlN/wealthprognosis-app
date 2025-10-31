<?php

namespace App\Support\ValueObjects;

/**
 * Value object for property tax calculation results.
 *
 * Represents the result of calculating property tax for real estate assets.
 */
readonly class PropertyTaxResult
{
    public function __construct(
        public string $taxPropertyArea,
        public float $taxablePropertyAmount,
        public float $taxablePropertyPercent,
        public float $taxablePropertyRate,
        public float $taxPropertyDeductionAmount,
        public float $taxPropertyAmount,
        public float $taxPropertyPercent,
        public float $taxPropertyRate,
        public string $explanation
    ) {}

    /**
     * Convert to array for backward compatibility.
     *
     * @return array{0: string, 1: float, 2: float, 3: float, 4: float, 5: float, 6: float, 7: float, 8: string}
     */
    public function toArray(): array
    {
        return [
            $this->taxPropertyArea,
            $this->taxablePropertyAmount,
            $this->taxablePropertyPercent,
            $this->taxablePropertyRate,
            $this->taxPropertyDeductionAmount,
            $this->taxPropertyAmount,
            $this->taxPropertyPercent,
            $this->taxPropertyRate,
            $this->explanation,
        ];
    }
}
