<?php

namespace App\Support\ValueObjects;

/**
 * Value object for bracket tax calculation results.
 *
 * Represents the result of calculating progressive bracket tax (trinnskatt).
 */
readonly class BracketTaxResult
{
    public function __construct(
        public float $taxAmount,
        public float $taxAveragePercent,
        public float $taxAverageRate,
        public string $explanation
    ) {}

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
