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

namespace App\Models\Core\ValueObjects;

/**
 * Value object representing the result of a realization tax calculation.
 */
readonly class RealizationCalculationResult
{
    public function __construct(
        public float $taxableAmount,
        public float $taxAmount,
        public float $acquisitionAmount,
        public float $taxPercent,
        public float $taxShieldAmount,
        public float $taxShieldPercent,
        public string $explanation
    ) {}

    /**
     * Convert to array format for backward compatibility.
     *
     * @return array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float, 6: string}
     */
    public function toArray(): array
    {
        return [
            $this->taxableAmount,
            $this->taxAmount,
            $this->acquisitionAmount,
            $this->taxPercent,
            $this->taxShieldAmount,
            $this->taxShieldPercent,
            $this->explanation,
        ];
    }
}
