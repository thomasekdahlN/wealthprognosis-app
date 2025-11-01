<?php

/*
 * Copyright (C) 2024 Thomas Ekdahl
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Support\ValueObjects;

/**
 * MortgageCalculation
 *
 * Value object representing intermediate mortgage calculation values.
 * Used during the amortization calculation process.
 *
 * All amount fields are automatically cast to integers in the constructor.
 */
readonly class MortgageCalculation
{
    public int $interestAmount;

    public int $termAmount;

    public int $principalAmount;

    public int $balanceAmount;

    public function __construct(
        public float $interestPercent,           // Interest rate as percentage (e.g., 5.5)
        public float $interestRate,              // Interest rate as decimal (e.g., 0.055)
        float $interestAmount,                   // Interest payment for this period
        float $termAmount,                       // Total payment for this period
        float $principalAmount,                  // Principal payment for this period
        float $balanceAmount,                    // Remaining balance after this period
        public float $denominator,               // Amortization formula denominator
        public bool $isInterestOnly,             // Whether this is an interest-only period
        public ?string $explanation = null       // Optional calculation explanation
    ) {
        $this->interestAmount = (int) round($interestAmount);
        $this->termAmount = (int) round($termAmount);
        $this->principalAmount = (int) round($principalAmount);
        $this->balanceAmount = (int) round($balanceAmount);
    }

    /**
     * Create a calculation for an interest-only period.
     */
    public static function interestOnly(
        float $interestPercent,
        float $interestRate,
        float $interestAmount,
        int $remainingBalanceAmount,
        float $extraDownpaymentAmount,
        float $denominator
    ): self {
        return new self(
            interestPercent: $interestPercent,
            interestRate: $interestRate,
            interestAmount: $interestAmount,
            termAmount: $interestAmount,
            principalAmount: $extraDownpaymentAmount,
            balanceAmount: $remainingBalanceAmount - $extraDownpaymentAmount,
            denominator: $denominator,
            isInterestOnly: true,
            explanation: 'Interest-only period'
        );
    }

    /**
     * Create a calculation for a regular amortization period.
     */
    public static function regular(
        float $interestPercent,
        float $interestRate,
        float $interestAmount,
        float $termAmount,
        float $extraDownpaymentAmount,
        int $remainingBalanceAmount,
        float $denominator
    ): self {
        $principalAmount = $termAmount - $interestAmount + $extraDownpaymentAmount;
        $balanceAmount = $remainingBalanceAmount - $principalAmount;

        return new self(
            interestPercent: $interestPercent,
            interestRate: $interestRate,
            interestAmount: $interestAmount,
            termAmount: $termAmount,
            principalAmount: $principalAmount,
            balanceAmount: $balanceAmount,
            denominator: $denominator,
            isInterestOnly: false,
            explanation: 'Regular amortization'
        );
    }

    /**
     * Create a fallback calculation for edge cases (zero/negative interest).
     */
    public static function fallback(
        float $interestPercent,
        float $interestRate,
        float $interestAmount,
        float $extraDownpaymentAmount,
        int $remainingBalanceAmount
    ): self {
        return new self(
            interestPercent: $interestPercent,
            interestRate: $interestRate,
            interestAmount: max(0.0, $interestAmount),
            termAmount: max(0.0, $interestAmount),
            principalAmount: max(0.0, $extraDownpaymentAmount),
            balanceAmount: max(0.0, $remainingBalanceAmount - $extraDownpaymentAmount),
            denominator: 0.0,
            isInterestOnly: true,
            explanation: 'Fallback calculation used due to invalid denominator'
        );
    }
}
