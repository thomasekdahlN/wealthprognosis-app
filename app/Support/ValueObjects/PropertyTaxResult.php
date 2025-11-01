<?php

namespace App\Support\ValueObjects;

/**
 * Value object for property tax calculation results.
 *
 * Represents the result of calculating property tax for real estate assets.
 *
 * All amount fields are automatically cast to integers in the constructor.
 */
readonly class PropertyTaxResult
{
    public int $taxablePropertyAmount;

    public int $taxPropertyDeductionAmount;

    public int $taxPropertyAmount;

    public function __construct(
        public string $taxPropertyArea,
        float $taxablePropertyAmount,
        public float $taxablePropertyPercent,
        public float $taxablePropertyRate,
        float $taxPropertyDeductionAmount,
        float $taxPropertyAmount,
        public float $taxPropertyPercent,
        public float $taxPropertyRate,
        public string $explanation
    ) {
        $this->taxablePropertyAmount = (int) round($taxablePropertyAmount);
        $this->taxPropertyDeductionAmount = (int) round($taxPropertyDeductionAmount);
        $this->taxPropertyAmount = (int) round($taxPropertyAmount);
    }

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
