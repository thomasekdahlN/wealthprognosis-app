<?php

namespace App\Support\ValueObjects;

/**
 * Value object for tax shield (skjermingsfradrag) calculation results.
 *
 * Represents the result of calculating tax shield for an asset,
 * including the adjusted tax amount after shield application.
 */
readonly class TaxShieldResult
{
    public function __construct(
        public float $taxAmount,
        public float $taxShieldAmount,
        public float $taxShieldPercent,
        public float $taxShieldRate,
        public string $explanation
    ) {}

    /**
     * Convert to array for backward compatibility.
     *
     * @return array{0: float, 1: float, 2: float, 3: float, 4: string}
     */
    public function toArray(): array
    {
        return [
            $this->taxAmount,
            $this->taxShieldAmount,
            $this->taxShieldPercent,
            $this->taxShieldRate,
            $this->explanation,
        ];
    }
}
