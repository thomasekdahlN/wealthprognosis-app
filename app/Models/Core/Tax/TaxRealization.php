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

namespace App\Models\Core\Tax;

use App\Models\Core\Contracts\TaxCalculatorInterface;
use App\Models\Core\ValueObjects\RealizationCalculationResult;
use App\Services\Tax\TaxConfigRepository;
use Illuminate\Support\Facades\Log;

/**
 * Class TaxRealization
 *
 * Handles realization (capital gains) tax calculations for various asset types.
 * Includes support for tax shield (skjermingsfradrag) calculations.
 */
class TaxRealization implements TaxCalculatorInterface
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
     * TaxSalary instance for OTP calculations.
     */
    private TaxSalary $taxsalary;

    /**
     * Create a new TaxRealization service.
     *
     * @param  string  $country  Country code for tax calculations (default: 'no')
     * @param  TaxConfigRepository|null  $taxConfigRepo  Optional repository instance for dependency injection
     * @param  TaxSalary|null  $taxSalary  Optional TaxSalary instance for dependency injection
     */
    public function __construct(
        string $country = 'no',
        ?TaxConfigRepository $taxConfigRepo = null,
        ?TaxSalary $taxSalary = null
    ) {
        $this->country = strtolower($country) ?: 'no';
        $this->taxConfigRepo = $taxConfigRepo ?? app(TaxConfigRepository::class);
        $this->taxsalary = $taxSalary ?? new TaxSalary($this->country, $this->taxConfigRepo);
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
     * @return array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float, 6: string} Returns array for backward compatibility
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
    ): array {
        $explanation = '';
        $numberOfYears = $year - $acquisitionYear;

        $realizationTaxRate = $this->taxConfigRepo->getTaxRealizationRate($taxType, $year);
        $realizationTaxShieldAmount = 0;
        $realizationTaxShieldPercent = 0;

        // Forskjell på hva man betaler skatt av
        $realizationTaxableAmount = 0; // The amount to pay tax from. Often calculated as taxof(MarketAmount - acquisitionAmount). We assume we always sell to market value
        $realizationTaxAmount = 0;

        if ($debug && $amount != 0) {
            Log::debug('Realization tax calculation start', [
                'tax_group' => $taxGroup,
                'tax_type' => $taxType,
                'year' => $year,
                'amount' => $amount,
                'acquisition_amount' => $acquisitionAmount,
                'tax_shield_prev_amount' => $taxShieldPrevAmount,
                'acquisition_year' => $acquisitionYear,
                'realization_tax_rate' => $realizationTaxRate,
            ]);
        }

        switch ($taxType) {
            case 'salary':
                $realizationTaxableAmount = 0;
                $realizationTaxAmount = 0;
                break;

            case 'pension':
                $realizationTaxableAmount = 0;
                $realizationTaxAmount = 0;
                break;

            case 'income':
                $realizationTaxableAmount = 0;
                $realizationTaxAmount = 0;
                break;

            case 'house':
                $realizationTaxableAmount = 0;
                $realizationTaxAmount = 0;  // Salg av eget hus er alltid skattefritt om man har bodd der minst ett år siste 2 år (regne på det?)
                break;

            case 'cabin':
                $realizationTaxableAmount = 0;
                $realizationTaxAmount = 0;  // Men må ha hatt hytta mer enn 5 eller 8 år for å bli skattefritt. (regne på det?)
                break;

            case 'car':
                $realizationTaxableAmount = 0;
                $realizationTaxAmount = 0;
                break;

            case 'boat':
                $realizationTaxableAmount = 0;
                $realizationTaxAmount = 0;
                break;

            case 'property':
                if ($amount - $acquisitionAmount > 0) {
                    $realizationTaxableAmount = $amount - $acquisitionAmount;  // verdien nå minus inngangsverdien skal skattes ved salg
                    $realizationTaxAmount = $realizationTaxableAmount * $realizationTaxRate;  // verdien nå minus inngangsverdien skal skattes ved salg
                }
                break;

            case 'rental':
                if ($amount - $acquisitionAmount > 0) {
                    $realizationTaxableAmount = $amount - $acquisitionAmount;  // verdien nå minus inngangsverdien skal skattes ved salg
                    $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxRate);  // verdien nå minus inngangsverdien skal skattes ved salg
                }
                break;

            case 'stock':

                if ($taxGroup == 'company') {
                    // Fritaksmodellen
                    if ($amount - $acquisitionAmount > 0) {
                        $realizationTaxableAmount = 0;
                        $realizationTaxAmount = 0;
                    }
                } else {
                    if ($amount - $acquisitionAmount > 0) {
                        $realizationTaxableAmount = $amount - $acquisitionAmount;  // verdien nå minus inngangsverdien skal skattes ved salg
                        $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxRate);  // verdien nå minus inngangsverdien skal skattes ved salg?
                    }
                }
                break;

            case 'bondfund':
                if ($amount - $acquisitionAmount > 0) {
                    $realizationTaxableAmount = $amount - $acquisitionAmount;  // verdien nå minus inngangsverdien skal skattes ved salg
                    $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxRate);  // verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
                }
                break;

            case 'equityfund':
                if ($amount - $acquisitionAmount > 0) {
                    $realizationTaxableAmount = $amount - $acquisitionAmount;  // verdien nå minus inngangsverdien skal skattes ved salg
                    $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxRate);  // verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
                }
                break;

            case 'ask':
                if ($amount - $acquisitionAmount > 0) {
                    $realizationTaxableAmount = $amount - $acquisitionAmount;  // verdien nå minus inngangsverdien skal skattes ved salg
                    $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxRate);  // verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
                }
                break;

            case 'otp':
                // OTP skattes som pensjonsinntekt når den realiseres
                [$realizationTaxAmount, $realizationTaxRate, $explanation] = $this->taxsalary->calculatesalarytax(false, $year, $amount);
                break;

            case 'ips':
                if ($amount - $acquisitionAmount > 0) {
                    $realizationTaxableAmount = $amount - $acquisitionAmount;  // verdien nå minus inngangsverdien skal skattes ved salg
                    $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxRate);  // verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
                }
                break;

            case 'crypto':
                if ($amount - $acquisitionAmount > 0) {
                    $realizationTaxableAmount = $amount - $acquisitionAmount;  // verdien nå minus inngangsverdien skal skattes ved salg
                    $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxRate);  // verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
                }
                break;

            case 'gold':
                if ($amount - $acquisitionAmount > 0) {
                    $realizationTaxableAmount = $amount - $acquisitionAmount;  // verdien nå minus inngangsverdien skal skattes ved salg
                    $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxRate);  // verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
                }
                break;

            case 'cash':
                $realizationTaxableAmount = $amount - $acquisitionAmount;  // verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = 0;  // Ingen skatt ved salg.
                break;

            case 'none': // No tax
                $realizationTaxableAmount = 0;
                $realizationTaxAmount = 0;
                break;

            default:
                if ($amount > 0) {
                    $realizationTaxableAmount = $amount - $acquisitionAmount;  // verdien nå minus inngangsverdien skal skattes ved salg
                    $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxRate);  // verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
                    break;
                }
        }

        // ###############################################################################################################
        // TaxShield handling
        // Skjermingsfradrag FIX: Trekker fra skjermingsfradraget fra skatten, men usikker på om det burde vært regnet ut i en ny kolonne igjen..... Litt inkonsekvent.
        $realizationBeforeShieldTaxAmount = $realizationTaxAmount;
        if ($this->taxConfigRepo->hasTaxShield($taxType)) {
            [$realizationTaxAmount, $realizationTaxShieldAmount, $realizationTaxShieldPercent, $explanation] = $this->taxShield($year, $taxGroup, $taxType, $transfer, $amount, $realizationTaxAmount, $taxShieldPrevAmount);
        }
        // ###############################################################################################################
        if ($realizationTaxAmount < 0) {
            $realizationTaxAmount = 0; // Skjermingsfradraget kan ikke være større enn skatten
        }
        $acquisitionAmount -= $amount; // We remove the transfered amount from the acquisitionAmount
        if ($acquisitionAmount < 0) {
            $acquisitionAmount = 0; // Kjøpsbeløpet kan ikke være negativt.
        }

        if ($debug) {
            Log::debug('Realization tax calculation end', [
                'tax_group' => $taxGroup,
                'tax_type' => $taxType,
                'year' => $year,
                'realization_taxable_amount' => $realizationTaxableAmount,
                'realization_before_shield_tax_amount' => $realizationBeforeShieldTaxAmount,
                'realization_tax_amount' => $realizationTaxAmount,
                'acquisition_amount' => $acquisitionAmount,
                'realization_tax_rate' => $realizationTaxRate,
                'realization_tax_shield_amount' => $realizationTaxShieldAmount,
                'realization_tax_shield_percent' => $realizationTaxShieldPercent,
            ]);
        }

        // V kan ikke kalkulere videre på $fortuneTaxableAmount fordi det er summen av skatter som er for fradrag, vi kan ikke summere på dette tallet etterpå. Bunnfradraget må alltid gjøres på total summen. Denne regner det bare ut isolert sett for en asset.
        // Return array for backward compatibility
        return [
            $realizationTaxableAmount,
            $realizationTaxAmount,
            $acquisitionAmount,
            $realizationTaxRate,
            $realizationTaxShieldAmount,
            $realizationTaxShieldPercent,
            $explanation,
        ];
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
     * @return array{0: float, 1: float, 2: float, 3: string} [realizationTaxAmount, realizationTaxShieldAmount, realizationTaxShieldPercent, explanation]
     */
    public function taxShield(
        int $year,
        string $taxGroup,
        string $taxType,
        bool $transfer,
        float $amount,
        float $realizationTaxAmount,
        float $taxShieldPrevAmount
    ): array {
        $explanation = '';
        $realizationTaxShieldAmount = 0;

        $realizationTaxShieldPercent = $this->taxConfigRepo->getTaxShieldRealizationRate($taxType, $year);

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

        Log::debug('Tax shield calculation', [
            'year' => $year,
            'amount' => $amount,
            'realization_tax_amount' => $realizationTaxAmount,
            'realization_tax_shield_amount' => $realizationTaxShieldAmount,
            'explanation' => $explanation,
        ]);

        return [$realizationTaxAmount, $realizationTaxShieldAmount, $realizationTaxShieldPercent, $explanation];
    }
}

