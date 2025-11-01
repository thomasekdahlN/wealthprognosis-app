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
 * MortgageData
 *
 * Value object representing mortgage data for a specific year.
 * Contains all mortgage-related fields including amounts, rates, and metadata.
 *
 * All amount fields are automatically cast to integers to ensure consistency
 * and eliminate the need for rounding throughout the application.
 */
readonly class MortgageData
{
    public int $amount;                    // Original loan amount

    public int $termAmount;                // Total annual payment (interest + principal + fees)

    public int $interestAmount;            // Interest payment for the year

    public int $principalAmount;           // Principal payment (amortization)

    public int $balanceAmount;             // Remaining loan balance

    public int $extraDownpaymentAmount;    // Extra payment made this year

    public int $gebyrAmount;               // Annual fee

    public int $taxDeductableAmount;       // Tax deduction amount

    public function __construct(
        float $amount,
        float $termAmount,
        float $interestAmount,
        public float $interestPercent,           // Interest rate as percentage (e.g., 5.5 for 5.5%)
        public float $interestRate,              // Interest rate as decimal (e.g., 0.055 for 5.5%)
        float $principalAmount,
        float $balanceAmount,
        float $extraDownpaymentAmount,
        public int $years,                       // Remaining years on the loan
        public int $interestOnlyYears,           // Remaining interest-only years
        float $gebyrAmount,
        float $taxDeductableAmount,
        public float $taxDeductablePercent,      // Tax deduction as percentage (e.g., 22 for 22%)
        public float $taxDeductableRate,         // Tax deduction as decimal (e.g., 0.22 for 22%)
        public ?string $description = null       // Optional description/explanation
    ) {
        // Cast all amount fields to integers
        $this->amount = (int) round($amount);
        $this->termAmount = (int) round($termAmount);
        $this->interestAmount = (int) round($interestAmount);
        $this->principalAmount = (int) round($principalAmount);
        $this->balanceAmount = (int) round($balanceAmount);
        $this->extraDownpaymentAmount = (int) round($extraDownpaymentAmount);
        $this->gebyrAmount = (int) round($gebyrAmount);
        $this->taxDeductableAmount = (int) round($taxDeductableAmount);
    }

    /**
     * Convert to array for storage in dataH structure.
     *
     * All amount fields are already cast to integers in the constructor,
     * so no rounding is needed here.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'termAmount' => $this->termAmount,
            'interestAmount' => $this->interestAmount,
            'interestPercent' => $this->interestPercent,
            'interestRate' => round($this->interestRate, 4),
            'principalAmount' => $this->principalAmount,
            'balanceAmount' => $this->balanceAmount,
            'extraDownpaymentAmount' => $this->extraDownpaymentAmount,
            'years' => $this->years,
            'interestOnlyYears' => $this->interestOnlyYears,
            'gebyrAmount' => $this->gebyrAmount,
            'taxDeductableAmount' => $this->taxDeductableAmount,
            'taxDeductablePercent' => $this->taxDeductablePercent,
            'taxDeductableRate' => $this->taxDeductableRate,
            'description' => $this->description,
        ];
    }

    /**
     * Create a MortgageData instance for a paid-off mortgage.
     *
     * @param  int  $yearsPaidEarly  Number of years the mortgage was paid off early
     */
    public static function paidOff(int $yearsPaidEarly): self
    {
        return new self(
            amount: 0,
            termAmount: 0,
            interestAmount: 0,
            interestPercent: 0,
            interestRate: 0,
            principalAmount: 0,
            balanceAmount: 0,
            extraDownpaymentAmount: 0,
            years: 0,
            interestOnlyYears: 0,
            gebyrAmount: 0,
            taxDeductableAmount: 0,
            taxDeductablePercent: 0,
            taxDeductableRate: 0,
            description: "Mortgage paid $yearsPaidEarly years faster due to extraDownpayments"
        );
    }
}
