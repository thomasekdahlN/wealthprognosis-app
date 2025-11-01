<?php

namespace App\Support\ValueObjects;

/**
 * Value object for salary tax calculation results.
 *
 * Represents the result of calculating total salary tax including
 * common tax, bracket tax, and social security tax.
 *
 * All amount fields are automatically cast to integers in the constructor.
 */
readonly class SalaryTaxResult
{
    public int $taxAmount;

    public function __construct(
        float $taxAmount,
        public float $taxAveragePercent,
        public float $taxAverageRate,
        public string $explanation
    ) {
        $this->taxAmount = (int) round($taxAmount);
    }

    /**
     * Convert to array for backward compatibility.
     *
     * @return array{0: float, 1: float, 2: float, 3: string}
     */
    public function toArray(): array
    {
        return [
            $this->taxAmount,
            $this->taxAveragePercent,
            $this->taxAverageRate,
            $this->explanation,
        ];
    }
}
