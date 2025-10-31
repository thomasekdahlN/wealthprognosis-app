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

namespace App\Services\Tax;

use App\Support\Contracts\TaxCalculatorInterface;
use App\Support\ValueObjects\FortuneCalculationResult;
use App\Support\ValueObjects\FortuneTaxResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Class TaxFortuneService
 *
 * Handles fortune (wealth) tax calculations including:
 * - Fortune tax (formuesskatt)
 * - Bracket-based progressive fortune taxation
 */
class TaxFortuneService implements TaxCalculatorInterface
{
    /**
     * Country code for tax lookups.
     */
    private string $country;

    /**
     * Shared TaxConfigRepository instance.
     */
    private TaxConfigRepository $taxConfigRepo;

    /**
     * Create a new TaxFortuneService service.
     *
     * @param  string  $country  Country code for tax calculations (default: 'no')
     * @param  TaxConfigRepository|null  $taxConfigRepo  Optional repository instance for dependency injection
     */
    public function __construct(
        string $country = 'no',
        ?TaxConfigRepository $taxConfigRepo = null,
    ) {
        $this->country = strtolower($country) ?: 'no';
        $this->taxConfigRepo = $taxConfigRepo ?? app(TaxConfigRepository::class);
    }

    /**
     * Get the country code this calculator is configured for.
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Calculates the fortune tax based on the given parameters.
     *
     * @param  string  $taxGroup  The tax group for the calculation.
     * @param  string|null  $taxType  The tax type for the calculation.
     * @param  int  $year  The year for which the tax is being calculated.
     * @param  int|null  $marketAmount  The market amount for the calculation. If null, it is considered as 0.
     * @param  int|null  $taxableInitialAmount  The taxable amount for the calculation. If null, it is considered as 0.
     * @param  int|null  $mortgageBalanceAmount  The mortgage balance amount.
     * @param  bool|null  $taxableAmountOverride  If true, the taxable amount is overridden. FIX: Why did wee need this?
     * @return FortuneCalculationResult Value object containing all calculation results
     */
    public function taxCalculationFortune(
        string $taxGroup,
        ?string $taxType,
        int $year,
        ?int $marketAmount,
        ?int $taxableInitialAmount,
        ?int $mortgageBalanceAmount,
        ?bool $taxableAmountOverride = false
    ): FortuneCalculationResult {
        $taxableFortunePercent = 0;
        $taxableFortuneRate = 0;
        $explanation = '';

        if ($taxableAmountOverride) {
            // Fortune tax can be negative by the amount of taxableInitialAmount minus mortgage if the asset value had been zero. This is how it is calculated in the tax system.

            $taxableFortuneAmount = $taxableInitialAmount;
            $taxableFortunePercent = 0; // If $fortuneTaxableAmount is set, we ignore the $fortuneTaxablePercent since that should be calculated from the market value and when $fortuneTaxableAmount is set, we do not releate tax to market value anymore.
            $taxableFortuneRate = 0; // If $fortuneTaxableAmount is set, we ignore the $fortuneTaxableRate since that should be calculated from the market value and when $fortuneTaxableAmount is set, we do not releate tax to market value anymore.

            $explanation .= 'Override taxable. ';

            Log::debug('Fortune tax calculation - override', [
                'taxable_initial_amount' => $taxableInitialAmount,
                'mortgage_balance_amount' => $mortgageBalanceAmount,
            ]);
        } else {
            $taxableFortuneRate = $this->taxConfigRepo->getTaxFortuneTaxableRate($taxType, $year);

            $taxableFortuneAmount = round($marketAmount * $taxableFortuneRate); // Calculate the amount from which the tax is calculated from the market value minus mortgage.
            $taxableFortunePercent = $taxableFortuneRate * 100;

            $explanation .= 'Market taxable. ';
        }

        $fortuneTaxResult = $this->calculatefortunetax(false, $year, $taxGroup, $taxableFortuneAmount, $mortgageBalanceAmount, false);

        $result = new FortuneCalculationResult(
            taxableFortuneAmount: $fortuneTaxResult->taxableFortuneAmount,
            taxableFortunePercent: $taxableFortunePercent,
            taxableFortuneRate: $taxableFortuneRate,
            taxFortuneAmount: $fortuneTaxResult->taxFortuneAmount,
            taxFortunePercent: $fortuneTaxResult->taxFortunePercent,
            taxFortuneRate: $fortuneTaxResult->taxFortuneRate,
            taxFortuneAveragePercent: $fortuneTaxResult->taxFortuneAveragePercent,
            taxFortuneAverageRate: $fortuneTaxResult->taxFortuneAverageRate,
            explanation: $explanation.' '.$fortuneTaxResult->explanation
        );

        Log::debug('taxCalculationFortune', ['result' => (array) $result]);

        return $result;
    }

    /**
     * Calculates the fortune tax based on the given parameters.
     *
     * @param  bool  $debug  If true, debug information will be logged.
     * @param  int  $year  The year for which the tax is being calculated.
     * @param  string  $taxGroup  The tax group
     * @param  float  $amount  The taxable amount of the fortune
     * @param  float  $mortgageAmount  The mortgage amount.
     * @param  bool  $deduct  if false we remove the first bracket (to simulate full fortune tax without the deduction), if true we run a full fortune tax calculation with deduction
     *                        Each asset wil run without deduction to simulate the full fortune tax cost of each asset, but when grouping all assets we run the deduction so it will be correct on a sum level.
     * @return FortuneTaxResult Value object containing tax calculation results
     */
    public function calculatefortunetax(bool $debug, int $year, string $taxGroup, float $amount, float $mortgageAmount, bool $deduct = true): FortuneTaxResult
    {
        $taxFortuneAmount = 0;
        $taxFortunePercent = 0;
        $taxFortuneRate = 0;
        $taxableFortunePercent = 0;
        $taxableFortuneRate = 0;

        $explanation = '';

        // Get the fortune tax bracket configuration (includes standard deduction as first bracket with 0%)
        $brackets = $this->taxConfigRepo->getFortuneTaxBracketConfig($year);

        // If $deduct is false, remove the first bracket (standard deduction with 0% tax to simulate the full fortune tax cost of an asset assuming you are in the position to pay fortune tax anyhow)
        if (! $deduct && count($brackets) > 0) {
            array_shift($brackets);
        }

        if ($debug) {
            Log::debug('Fortune tax calculation input', [
                'year' => $year,
                'tax_group' => $taxGroup,
                'amount' => $amount,
                'mortgageAmount' => $mortgageAmount,
                'deduct' => $deduct,
                'brackets_count' => count($brackets),
            ]);
        }

        // FIX: Not all assets is allowed to have mortgage deducted. Only private house/rental/cabins. Check tax laws.
        $taxableFortuneAmount = $amount - $mortgageAmount;

        // Calculate the tax amount using bracket-based progressive taxation
        // The first bracket is typically the standard deduction with 0% tax (unless $deduct is false)
        $previousLimit = 0;

        foreach ($brackets as $bracket) {
            $percent = Arr::get($bracket, 'percent', 0); // Percentage (e.g., 1.0 = 1%)
            $rate = $percent / 100; // Convert percentage to decimal
            $limit = Arr::get($bracket, 'limit', PHP_FLOAT_MAX); // If no limit, use max float

            if ($amount > $previousLimit) {
                // Calculate the amount in this bracket
                $amountInBracket = min($amount, $limit) - $previousLimit;

                // Add tax for this bracket
                $taxFortuneAmount += $amountInBracket * $rate;

                // Track the marginal tax rate (the rate of the highest bracket reached)
                $taxFortunePercent = $percent; // Well keep the last and highets one, as thats what we refer to when talking about fortune tax
                $taxFortuneRate = $rate; // Well keep the last and highets one, as thats what we refer to when talking about fortune tax

                if ($debug) {
                    Log::debug('Fortune tax bracket calculation', [
                        'limit' => $limit,
                        'percent' => $percent,
                        '$amountInBracket' => $amountInBracket,
                        'taxBracket' => $amountInBracket * $rate,
                    ]);
                }

                // If we've reached the amount, stop
                if ($amount <= $limit) {
                    break;
                }

                $previousLimit = $limit;
            }
        }

        // Calculate the average tax rate and percent
        $taxFortuneAveragePercent = $amount > 0 ? ($taxFortuneAmount / $amount) * 100 : 0;
        $taxFortuneAverageRate = $amount > 0 ? $taxFortuneAmount / $amount : 0;
        $explanation = 'Average fortune tax based on actual taxamount after bracket calculation. ';

        // Handle negative taxable amount (mortgage > asset value)
        if ($taxableFortuneAmount < 0) {
            $explanation = 'Negative asset value after deducting mortgage, reducing fortune value. ';
        }

        $result = new FortuneTaxResult(
            taxableFortuneAmount: $taxableFortuneAmount,
            taxableFortunePercent: $taxableFortunePercent,
            taxableFortuneRate: $taxableFortuneRate,
            taxFortuneAmount: $taxFortuneAmount,
            taxFortunePercent: $taxFortunePercent,
            taxFortuneRate: $taxFortuneRate,
            taxFortuneAveragePercent: $taxFortuneAveragePercent,
            taxFortuneAverageRate: $taxFortuneAverageRate,
            explanation: $explanation
        );

        Log::debug('FortuneTaxResult', ['result' => (array) $result]);

        return $result;
    }
}
