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
use App\Support\ValueObjects\RealizationTaxResult;
use App\Support\ValueObjects\TaxShieldResult;
use Illuminate\Support\Facades\Log;

/**
 * Class TaxRealizationService
 *
 * Handles realization (capital gains) tax calculations for various asset types.
 * Includes support for tax shield (skjermingsfradrag) calculations.
 */
class TaxRealizationService implements TaxCalculatorInterface
{
    /**
     * Country code for tax lookups (e.g., 'no').
     */
    private string $country;

    /**
     * Shared TaxConfigRepository instance.
     */
    private TaxConfigRepository $taxConfigRepo;

    /**
     * TaxSalaryService instance for OTP calculations.
     */
    private TaxSalaryService $taxsalary;

    /**
     * Create a new TaxRealizationService service.
     *
     * @param  string  $country  Country code for tax calculations (default: 'no')
     * @param  TaxConfigRepository|null  $taxConfigRepo  Optional repository instance for dependency injection
     * @param  TaxSalaryService|null  $taxSalary  Optional TaxSalaryService instance for dependency injection
     */
    public function __construct(
        string $country = 'no',
        ?TaxConfigRepository $taxConfigRepo = null,
        ?TaxSalaryService $taxSalary = null
    ) {
        $this->country = strtolower($country) ?: 'no';
        $this->taxConfigRepo = $taxConfigRepo ?? app(TaxConfigRepository::class);
        $this->taxsalary = $taxSalary ?? new TaxSalaryService($this->country, $this->taxConfigRepo);
    }

    /**
     * Get the country code this calculator is configured for.
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Calculates the tax realization.
     *
     * This method calculates the tax realization based on various parameters such as tax group, tax type, year, amount, acquisition amount, asset difference amount, previous tax shield amount, and acquisition year.
     * It handles different tax types and calculates the tax realization accordingly.
     * It also handles the tax shield, which is only used when transferring between private assets or from company to private asset.
     *
     * @param  bool  $debug  If true, debug information will be logged.
     * @param  bool  $transfer  If true, the tax shield is used.
     * @param  string  $taxGroup  The tax group for the calculation.
     * @param  string  $taxType  The type of tax for the calculation.
     * @param  int  $year  The year for which the tax is being calculated.
     * @param  float  $amount  The amount for the calculation.
     * @param  float  $acquisitionAmount  The acquisition amount for the calculation.
     * @param  float  $assetDiffAmount  The asset difference amount for the calculation.
     * @param  float  $taxShieldPrevAmount  The previous tax shield amount for the calculation.
     * @param  int|null  $acquisitionYear  The acquisition year for the calculation. If null, it is considered as 0.
     */
    public function taxCalculationRealization(
        bool $debug,
        bool $transfer,
        string $taxGroup,
        string $taxType,
        int $year,
        float $amount,
        float $acquisitionAmount,
        float $assetDiffAmount,
        float $taxShieldPrevAmount = 0,
        ?int $acquisitionYear = 0
    ): RealizationTaxResult {
        $this->logCalculationStart($debug, $amount, $taxGroup, $taxType, $year, $acquisitionAmount, $taxShieldPrevAmount, $acquisitionYear);

        $taxRates = $this->calculateTaxRates($taxType, $year);
        $baseTaxCalculation = $this->calculateBaseTax($taxType, $taxGroup, $amount, $acquisitionAmount, $year, $taxRates);

        $finalResult = $this->applyTaxShieldAndFinalize(
            $baseTaxCalculation,
            $taxRates,
            $year,
            $taxGroup,
            $taxType,
            $transfer,
            $amount,
            $acquisitionAmount,
            $taxShieldPrevAmount
        );

        $this->logCalculationEnd($taxGroup, $taxType, $year, $baseTaxCalculation['taxAmount'], $finalResult);

        return $finalResult;
    }

    /**
     * Calculate the tax shield (skjermingsfradrag) for an asset.
     *
     * Tax shield accumulates annually based on the asset value and shield rate,
     * and is used to reduce realization tax when assets are transferred or sold.
     * Only applies to private assets, not company assets.
     *
     * @param  int  $year  The tax year
     * @param  string  $taxGroup  The tax group ('private' or 'company')
     * @param  string  $taxType  The type of asset
     * @param  bool  $transfer  Whether this is an actual transfer (uses shield) or simulation (accumulates shield)
     * @param  float  $amount  The asset value
     * @param  float  $realizationTaxAmount  The calculated realization tax before shield
     * @param  float  $taxShieldPrevAmount  The accumulated tax shield from previous years
     */
    public function taxShield(
        int $year,
        string $taxGroup,
        string $taxType,
        bool $transfer,
        float $amount,
        float $realizationTaxAmount,
        float $taxShieldPrevAmount
    ): TaxShieldResult {
        $explanation = '';
        $realizationTaxShieldAmount = 0;

        $realizationTaxShieldPercent = $this->taxConfigRepo->getTaxShieldRealizationRate($taxType, $year);
        $realizationTaxShieldRate = $this->taxConfigRepo->getTaxRealizationRate($taxType, $year);

        // Skjermingsfradrag
        if ($realizationTaxShieldPercent > 0) {
            // TaxShield is calculated on an assets value from 1/1 each year, and accumulated until used.
            $realizationTaxShieldAmount = round(($amount * $realizationTaxShieldPercent) + $taxShieldPrevAmount); // Tax shield accumulates over time, until you actually transfer an amount, then it is reduced accordigly until zero.
            $explanation = 'TaxShieldPercent:'.$realizationTaxShieldPercent * 100 .'. ';
        } else {
            $realizationTaxShieldAmount = $taxShieldPrevAmount;
            $explanation = 'TaxShieldPercent:'.$realizationTaxShieldPercent * 100 .'. ';
        }
        if ($realizationTaxShieldAmount < 0) { // Tax shield can not go below zero.
            $realizationTaxShieldAmount = 0;
        }

        if ($transfer) {
            if ($taxGroup == 'private') {
                // tax shield is only used when tansfering between private assets or from company to private asset - never between company assets.
                // We run simulations for every year that should not change the Shield, only a real transfer reduces the shield, all other activity increases the shield
                if ($realizationTaxAmount >= $realizationTaxShieldAmount) {
                    $explanation .= "Taxshield ($realizationTaxShieldAmount) lower than tax ($realizationTaxAmount), using entire shield. ";

                    $realizationTaxAmount -= $realizationTaxShieldAmount; // Reduce the tax amount by the taxShieldAmount
                    $realizationTaxShieldAmount = 0; // Then taxShieldAmount is used and has to go to zero.
                } else {
                    $explanation .= "Taxshield ($realizationTaxShieldAmount) bigger than tax ($realizationTaxAmount), using part of the shield. ";

                    $realizationTaxShieldAmount -= $realizationTaxAmount; // We reduce it by the amount we used
                    $realizationTaxAmount = 0; // Then taxAmount is zero, since the entire emount was taxShielded.
                }
            } else {
                $explanation .= "Only taxshield on private group assets, found #$taxGroup#. ";
            }
        } else {
            $explanation .= 'Taxshield simulation, not an actual transfer. ';
        }

        $result = new TaxShieldResult(
            taxAmount: $realizationTaxAmount,
            taxShieldAmount: $realizationTaxShieldAmount,
            taxShieldPercent: $realizationTaxShieldPercent,
            taxShieldRate: $realizationTaxShieldRate,
            explanation: $explanation
        );

        Log::debug('Tax shield calculation', ['year' => $year, 'amount' => $amount, 'result' => (array) $result]);

        return $result;
    }

    /**
     * Log the start of realization tax calculation.
     */
    private function logCalculationStart(
        bool $debug,
        float $amount,
        string $taxGroup,
        string $taxType,
        int $year,
        float $acquisitionAmount,
        float $taxShieldPrevAmount,
        ?int $acquisitionYear
    ): void {
        if ($debug && $amount != 0) {
            Log::debug('Realization tax calculation start', [
                'tax_group' => $taxGroup,
                'tax_type' => $taxType,
                'year' => $year,
                'amount' => $amount,
                'acquisition_amount' => $acquisitionAmount,
                'tax_shield_prev_amount' => $taxShieldPrevAmount,
                'acquisition_year' => $acquisitionYear,
                'realization_tax_rate' => $this->taxConfigRepo->getTaxRealizationRate($taxType, $year),
            ]);
        }
    }

    /**
     * Log the end of realization tax calculation.
     */
    private function logCalculationEnd(
        string $taxGroup,
        string $taxType,
        int $year,
        float $realizationBeforeShieldTaxAmount,
        RealizationTaxResult $result
    ): void {
        Log::debug('Realization tax calculation end', [
            'taxGroup' => $taxGroup,
            'taxType' => $taxType,
            'year' => $year,
            'realizationBeforeShieldTaxAmount' => $realizationBeforeShieldTaxAmount,
            'result' => (array) $result,
        ]);
    }

    /**
     * Calculate tax rates for the given tax type and year.
     */
    private function calculateTaxRates(string $taxType, int $year): array
    {
        $realizationTaxRate = $this->taxConfigRepo->getTaxRealizationRate($taxType, $year);

        return [
            'rate' => $realizationTaxRate,
            'percent' => $realizationTaxRate * 100,
            'shieldRate' => $realizationTaxRate,
        ];
    }

    /**
     * Calculate base tax before tax shield application.
     */
    private function calculateBaseTax(
        string $taxType,
        string $taxGroup,
        float $amount,
        float $acquisitionAmount,
        int $year,
        array $taxRates
    ): array {
        $taxableAmount = 0;
        $taxAmount = 0;
        $explanation = '';

        switch ($taxType) {
            case 'salary':
            case 'pension':
            case 'income':
            case 'house':
            case 'cabin':
            case 'car':
            case 'boat':
                // These asset types have no realization tax
                break;

            case 'property':
            case 'rental':
                if ($amount - $acquisitionAmount > 0) {
                    $taxableAmount = $amount - $acquisitionAmount;
                    $taxAmount = $taxType === 'rental'
                        ? round($taxableAmount * $taxRates['rate'])
                        : $taxableAmount * $taxRates['rate'];
                }
                break;

            case 'stock':
                if ($taxGroup === 'company') {
                    // Fritaksmodellen - no tax for company-owned stocks
                    if ($amount - $acquisitionAmount > 0) {
                        $taxableAmount = 0;
                        $taxAmount = 0;
                    }
                } else {
                    if ($amount - $acquisitionAmount > 0) {
                        $taxableAmount = $amount - $acquisitionAmount;
                        $taxAmount = round($taxableAmount * $taxRates['rate']);
                    }
                }
                break;

            case 'bondfund':
            case 'equityfund':
            case 'ask':
            case 'ips':
            case 'crypto':
            case 'gold':
                if ($amount - $acquisitionAmount > 0) {
                    $taxableAmount = $amount - $acquisitionAmount;
                    $taxAmount = round($taxableAmount * $taxRates['rate']);
                }
                break;

            case 'otp':
                // OTP is taxed as pension income when realized
                $salaryTaxResult = $this->taxsalary->calculatesalarytax(false, $year, (int) $amount);
                $taxAmount = $salaryTaxResult->taxAmount;
                $taxRates['rate'] = $salaryTaxResult->taxRate;
                $explanation = $salaryTaxResult->explanation;
                break;

            case 'cash':
                $taxableAmount = $amount - $acquisitionAmount;
                $taxAmount = 0; // No tax on cash
                break;

            case 'none':
                // No tax
                break;

            default:
                if ($amount > 0) {
                    $taxableAmount = $amount - $acquisitionAmount;
                    $taxAmount = round($taxableAmount * $taxRates['rate']);
                }
                break;
        }

        return [
            'taxableAmount' => $taxableAmount,
            'taxAmount' => $taxAmount,
            'explanation' => $explanation,
        ];
    }

    /**
     * Apply tax shield and finalize the realization tax result.
     */
    private function applyTaxShieldAndFinalize(
        array $baseTaxCalculation,
        array $taxRates,
        int $year,
        string $taxGroup,
        string $taxType,
        bool $transfer,
        float $amount,
        float $acquisitionAmount,
        float $taxShieldPrevAmount
    ): RealizationTaxResult {
        $realizationTaxAmount = $baseTaxCalculation['taxAmount'];
        $realizationTaxShieldAmount = 0;
        $realizationTaxShieldPercent = 0;
        $explanation = $baseTaxCalculation['explanation'];

        // Apply tax shield if applicable
        if ($this->taxConfigRepo->hasTaxShield($taxType)) {
            $taxShieldResult = $this->taxShield(
                $year,
                $taxGroup,
                $taxType,
                $transfer,
                $amount,
                $realizationTaxAmount,
                $taxShieldPrevAmount
            );

            $realizationTaxAmount = $taxShieldResult->taxAmount;
            $realizationTaxShieldAmount = $taxShieldResult->taxShieldAmount;
            $realizationTaxShieldPercent = $taxShieldResult->taxShieldPercent;
            $explanation = $taxShieldResult->explanation;
        }

        // Ensure tax amount cannot be negative
        if ($realizationTaxAmount < 0) {
            $realizationTaxAmount = 0;
        }

        // Update acquisition amount
        $updatedAcquisitionAmount = $acquisitionAmount - $amount;
        if ($updatedAcquisitionAmount < 0) {
            $updatedAcquisitionAmount = 0;
        }

        return new RealizationTaxResult(
            acquisitionAmount: $updatedAcquisitionAmount,
            taxableAmount: $baseTaxCalculation['taxableAmount'],
            taxAmount: $realizationTaxAmount,
            taxPercent: $taxRates['percent'],
            taxRate: $taxRates['rate'],
            taxShieldAmount: $realizationTaxShieldAmount,
            taxShieldPercent: $realizationTaxShieldPercent,
            taxShieldRate: $taxRates['shieldRate'],
            explanation: $explanation
        );
    }
}
