<?php

namespace App\Support\ValueObjects;

/**
 * Value object for income tax calculation results.
 *
 * Represents the result of calculating income tax for various asset and income types.
 *
 * All amount fields are automatically cast to integers in the constructor.
 */
readonly class IncomeTaxResult
{
    public int $taxAmount;

    public function __construct(
        float $taxAmount,
        public float $taxRate,
        public string $explanation
    ) {
        $this->taxAmount = (int) round($taxAmount);
    }

    /**
     * Convert to array for backward compatibility.
     *
     * @return array{0: float, 1: float, 2: string}
     */
    public function toArray(): array
    {
        return [
            $this->taxAmount,
            $this->taxRate,
            $this->explanation,
        ];
    }
}
