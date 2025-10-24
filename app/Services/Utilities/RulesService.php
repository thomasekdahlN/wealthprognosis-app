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

namespace App\Services\Utilities;

use Illuminate\Support\Facades\Log;

/**
 * Class Rules
 *
 * Handles parsing and execution of financial rule strings for asset calculations.
 * Supports percentage changes, divisors, dynamic divisors, and fixed amount adjustments.
 *
 * Rule formats:
 * - Percentage: "+10%", "-5%", "10%" (add, subtract, or extract percentage)
 * - Divisor: "+1/4", "-1/2", "1/10" (add, subtract, or extract fraction)
 * - Dynamic Divisor: "+1|4", "-1|2" (counts down denominator each use)
 * - Fixed Amount: "+1000", "-500" (add or subtract fixed amount, multiplied by factor)
 */
class RulesService
{
    /**
     * Main entry point for calculating rule transformations on amounts.
     *
     * Parses a rule string and applies the appropriate calculation method.
     * Returns the new amount, calculated difference, updated rule (for dynamic divisors), and explanation.
     *
     * @param  bool  $debug  Whether to output debug information
     * @param  int  $amount  The current amount to transform
     * @param  int  $acquisitionAmount  The original acquisition amount (used for context)
     * @param  string|null  $rule  The rule string to parse and execute (e.g., "+10%", "1/4", "-1|2")
     * @param  int  $factor  Multiplier for fixed amount rules (default: 1)
     * @return array{0: int, 1: int, 2: string|null, 3: string} Returns array for backward compatibility
     */
    public function calculateRule(bool $debug, int $amount, int $acquisitionAmount, ?string $rule, int $factor = 1): array
    {
        // Handle null rule - multiply by factor
        if ($rule === null) {
            $factorAmount = $amount * $factor;
            $explanation = "Multiplied by factor: $amount * $factor = $factorAmount";

            if ($debug) {
                Log::debug('Rule calculation: null rule', [
                    'amount' => $amount,
                    'factor' => $factor,
                    'result' => $factorAmount,
                ]);
            }

            // Return array for backward compatibility
            return [$factorAmount, $factorAmount, null, $explanation];
        }

        $newAmount = 0;
        $calcAmount = 0;
        $explanation = '';

        if ($debug) {
            Log::debug('Rule calculation input', [
                'amount' => $amount,
                'rule' => $rule,
            ]);
        }

        if ($rule) {
            if (preg_match('/(\+|\-)?(\d*)(\%)/i', $rule, $ruleH, PREG_OFFSET_CAPTURE)) {
                [$newAmount, $calcAmount, $explanation] = $this->calculationPercentage($debug, $amount, $ruleH);

            } elseif (preg_match('/(\+|\-)?(\d*)\/(\d*)/i', $rule, $ruleH, PREG_OFFSET_CAPTURE)) {
                // When divisor sign is pipe / -Normal divisor
                [$newAmount, $calcAmount, $explanation] = $this->calculationDivisor($debug, $amount, $ruleH);

            } elseif (preg_match('/(\+|\-)?(\d*)\|(\d*)/i', $rule, $ruleH, PREG_OFFSET_CAPTURE)) {
                // When divisor sign is pipe | - we dynamically count down divisor according to rules
                [$newAmount, $calcAmount, $rule, $explanation] = $this->calculationDynamicDivisor($debug, $amount, $ruleH);

            } elseif (preg_match('/(\+|\-)?(\d*)/i', $rule, $ruleH, PREG_OFFSET_CAPTURE)) {
                [$newAmount, $calcAmount, $explanation] = $this->calculationPlusMinus($debug, $amount, $ruleH, $factor);

            } else {
                Log::error('Unsupported rule format', ['rule' => $rule]);
            }
        }

        // Return array for backward compatibility
        return [$newAmount, $calcAmount, $rule, $explanation];
    }

    /**
     * Calculate a dynamic divisor rule that counts down with each use.
     *
     * Dynamic divisors use the pipe "|" symbol and decrement the denominator after each calculation.
     * For example, "1|4" becomes "1/3" after use, then "1/2", then "1/1", then null.
     *
     * @param  bool  $debug  Whether to output debug information
     * @param  int  $amount  The amount to calculate on
     * @param  array<int, array<int, mixed>>  $ruleH  Regex match array from preg_match containing rule components
     * @return array{0: int, 1: int, 2: string|null, 3: string} [newAmount, calcAmount, updatedRule, explanation]
     */
    private function calculationDynamicDivisor(bool $debug, int $amount, array $ruleH): array
    {
        $rule = null;

        [$newAmount, $calcAmount, $explanation] = $this->calculationDivisor($debug, $amount, $ruleH);

        $ruleH[3][0]--; // Dynamic divisor, we count down.

        if ($ruleH[3][0] > 0) {
            // If there's no sign (empty), only show the denominator part
            if (empty($ruleH[1][0])) {
                $rule = '|'.$ruleH[3][0];
            } else {
                // Use '/' for the returned rule format (not '|')
                $rule = $ruleH[1][0].$ruleH[2][0].'/'.$ruleH[3][0];
            }
        }

        $explanation .= " rewritten rule: $rule";

        if ($debug) {
            Log::debug('Dynamic divisor calculation', [
                'amount' => $amount,
                'new_amount' => $newAmount,
                'calc_amount' => $calcAmount,
                'new_rule' => $rule,
            ]);
        }

        return [$newAmount, $calcAmount, $rule, $explanation]; // Returns rewritten rule, has to be remembered
    }

    /**
     * Calculate a divisor (fraction) rule.
     *
     * Divisor rules use the "/" symbol to divide the amount.
     * - With "+": adds the fraction to the amount (e.g., "+1/4" adds 25%)
     * - With "-": subtracts the fraction from the amount (e.g., "-1/2" subtracts 50%)
     * - Without sign: extracts the fraction without changing the original amount
     *
     * @param  bool  $debug  Whether to output debug information
     * @param  int  $amount  The amount to calculate on
     * @param  array<int, array<int, mixed>>  $ruleH  Regex match array from preg_match containing rule components
     * @return array{0: int, 1: int, 2: string} [newAmount, calcAmount, explanation]
     */
    private function calculationDivisor(bool $debug, int $amount, array $ruleH): array
    {
        $divisor = (int) $ruleH[3][0];
        $baseCalcAmount = (int) round($amount / $divisor);

        if ($ruleH[1][0] == '-') {
            $newAmount = $amount - $baseCalcAmount;
            $calcAmount = -$baseCalcAmount; // Apply negative sign to calcAmount
            $explanation = "Subtracting division: $amount/$divisor=".$baseCalcAmount;

        } elseif ($ruleH[1][0] == '+') {
            $newAmount = $amount + $baseCalcAmount;
            $calcAmount = $baseCalcAmount; // Positive calcAmount
            $explanation = "Adding division: $amount/$divisor=".$baseCalcAmount;
        } else {
            // When no sign is given,  we only want the part of the amount. Its like taking this divisor out of the amount.
            $newAmount = $amount; // We do not change the original amount
            $calcAmount = $baseCalcAmount; // Positive calcAmount
            $explanation = "Division: $amount/$divisor=".$baseCalcAmount;
        }

        return [$newAmount, $calcAmount, $explanation];
    }

    /**
     * Calculate a percentage rule.
     *
     * Percentage rules use the "%" symbol.
     * - With "+": increases the amount by the percentage (e.g., "+10%" adds 10%)
     * - With "-": decreases the amount by the percentage (e.g., "-5%" subtracts 5%)
     * - Without sign: extracts the percentage without changing the original amount
     *
     * @param  bool  $debug  Whether to output debug information
     * @param  int  $amount  The amount to calculate on
     * @param  array<int, array<int, mixed>>  $ruleH  Regex match array from preg_match containing rule components
     * @return array{0: int, 1: int, 2: string} [newAmount, calcAmount, explanation]
     */
    private function calculationPercentage(bool $debug, int $amount, array $ruleH): array
    {
        $percent = (int) $ruleH[2][0];
        $calcAmount = 0;

        if ($ruleH[1][0] == '-') {
            $newAmount = (int) round($amount * ((-$percent / 100) + 1));
            $explanation = "$amount-$percent%=$newAmount";
            $calcAmount = $newAmount - $amount;

        } elseif ($ruleH[1][0] == '+') {
            $newAmount = (int) round($amount * (($percent / 100) + 1));
            $explanation = "$amount+$percent%=$newAmount";
            $calcAmount = $newAmount - $amount;

        } else {
            // When no sign is given, we only want the part of the amount. Its like taking this percentage out of the amount.
            $newAmount = $amount; // We do not change the original amount
            $calcAmount = (int) round($amount * ($percent / 100));
            $explanation = "$percent% of $amount=$calcAmount";
        }

        return [$newAmount, $calcAmount, $explanation];
    }

    /**
     * Calculate a fixed amount (plus/minus) rule.
     *
     * Fixed amount rules use numeric values with optional +/- signs.
     * The amount is multiplied by the factor parameter (useful for monthly vs yearly calculations).
     * - With "+": adds the fixed amount (e.g., "+1000" adds 1000 * factor)
     * - With "-": subtracts the fixed amount (e.g., "-500" subtracts 500 * factor)
     * - Without sign: extracts the amount without changing the original amount
     *
     * @param  bool  $debug  Whether to output debug information
     * @param  int  $amount  The amount to calculate on
     * @param  array<int, array<int, mixed>>  $ruleH  Regex match array from preg_match containing rule components
     * @param  int  $factor  Multiplier for the fixed amount (default: 1)
     * @return array{0: int, 1: int, 2: string} [newAmount, calcAmount, explanation]
     */
    private function calculationPlusMinus(bool $debug, int $amount, array $ruleH, int $factor = 1): array
    {
        $extraAmount = $ruleH[2][0];
        // Handle empty or non-numeric values
        if (empty($extraAmount) || ! is_numeric($extraAmount)) {
            $extraAmount = 0;
        }
        $baseCalcAmount = (int) round((int) $extraAmount * $factor);

        if ($ruleH[1][0] == '-') {
            $newAmount = $amount - $baseCalcAmount;
            $calcAmount = -$baseCalcAmount; // Apply negative sign to calcAmount
            $explanation = "Subtracting: $amount-($extraAmount*$factor)=$newAmount ";

        } elseif ($ruleH[1][0] == '+') {
            $newAmount = $amount + $baseCalcAmount;
            $calcAmount = $baseCalcAmount; // Positive calcAmount
            $explanation = "Adding: $amount+($extraAmount*$factor)=$newAmount ";
        } else {
            // When no sign is given, we only want the part of the amount. Its like taking this extra amount out of the amount.
            $newAmount = $amount; // We do not change the original amount
            $calcAmount = $baseCalcAmount; // Positive calcAmount
            $explanation = "Extra amount: $extraAmount*$factor=$baseCalcAmount ";
        }

        return [$newAmount, $calcAmount, $explanation];
    }
}
