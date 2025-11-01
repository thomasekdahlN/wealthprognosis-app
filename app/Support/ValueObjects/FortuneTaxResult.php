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
 * Value object representing the result of a fortune tax calculation.
 *
 * This is returned by TaxFortuneService::calculatefortunetax() and provides
 * a type-safe, self-documenting alternative to array destructuring.
 *
 * All amount fields are automatically cast to integers in the constructor.
 */
readonly class FortuneTaxResult
{
    public int $taxableFortuneAmount;

    public int $taxFortuneAmount;

    public function __construct(
        float $taxableFortuneAmount,
        public float $taxableFortunePercent,
        public float $taxableFortuneRate,
        float $taxFortuneAmount,
        public float $taxFortunePercent,
        public float $taxFortuneRate,
        public float $taxFortuneAveragePercent,
        public float $taxFortuneAverageRate,
        public string $explanation
    ) {
        $this->taxableFortuneAmount = (int) round($taxableFortuneAmount);
        $this->taxFortuneAmount = (int) round($taxFortuneAmount);
    }

    /**
     * Convert to array format for backward compatibility.
     *
     * @return array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float, 6: float, 7: float, 8: string}
     */
    public function toArray(): array
    {
        return [
            $this->taxableFortuneAmount,
            $this->taxableFortunePercent,
            $this->taxableFortuneRate,
            $this->taxFortuneAmount,
            $this->taxFortunePercent,
            $this->taxFortuneRate,
            $this->taxFortuneAveragePercent,
            $this->taxFortuneAverageRate,
            $this->explanation,
        ];
    }
}
