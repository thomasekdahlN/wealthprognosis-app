<?php

/* Copyright (C) 2025 Thomas Ekdahl
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

namespace App\Support\ValueObjects;

/**
 * Value object for property tax configuration.
 *
 * Contains the tax rate, deduction amount, and taxable percentage for a specific municipality and tax group.
 */
readonly class PropertyTaxConfig
{
    public function __construct(
        public float $taxRate,
        public float $deductionAmount,
        public float $taxablePercent
    ) {}
}
