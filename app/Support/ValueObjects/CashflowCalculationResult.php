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
 * CashflowCalculationResult
 *
 * Value object representing the result of a cashflow calculation.
 * Contains both before-tax and after-tax amounts, along with tax details.
 *
 * All amount fields are automatically cast to integers in the constructor.
 */
readonly class CashflowCalculationResult
{
    public int $beforeTaxAmount;

    public int $afterTaxAmount;

    public int $taxAmount;

    public function __construct(
        float $beforeTaxAmount,
        float $afterTaxAmount,
        float $taxAmount,
        public float $taxPercent,
        public float $taxRate,
        public string $description
    ) {
        $this->beforeTaxAmount = (int) round($beforeTaxAmount);
        $this->afterTaxAmount = (int) round($afterTaxAmount);
        $this->taxAmount = (int) round($taxAmount);
    }
}
