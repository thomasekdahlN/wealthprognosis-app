<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class TaxIncome extends Model
{
    use HasFactory;

    public $taxH = [];

    //Will be rewritten to support yearly tax differences, just faking for now.
    //Should probably be a deep nested json structure.
    public function __construct($config, $startYear, $stopYear)
    {

        $file = config_path("tax/$config.json");
        $configH = File::json($file);
        echo "Leser: '$file'\n";

        foreach ($configH as $type => $typeH) {
            $this->taxH[$type] = $typeH;
        }
    }

    public function getTaxIncome($taxGroup, $taxType, $year)
    {

        return Arr::get($this->taxH, "$taxType.income", 0) / 100;
    }

    public function getTaxSalary($taxGroup, $taxType, $year)
    {
        return Arr::get($this->taxH, "salary.$taxType", 0) / 100;
    }

    public function getTaxBracket($taxGroup, $year)
    {

        return Arr::get($this->taxH, "salary.bracket");
    }

    public function getTaxStandardDeduction($taxGroup, $taxType, $year)
    {
        return Arr::get($this->taxH, "$taxType.standardDeduction", 0);
    }

    public function taxCalculationIncome(bool $debug, string $taxGroup, string $taxType, int $year, ?float $income, ?float $expence, ?float $interestAmount)
    {

        $explanation = '';
        $incomeTaxPercent = $this->getTaxIncome($taxGroup, $taxType, $year); //FIX
        $incomeTaxAmount = 0;

        if ($debug) {
            echo "\ntaxtype: $taxGroup.$taxType.$year: income: $income, expence: $expence, incomeTaxPercent: $incomeTaxPercent\n";
        }

        switch ($taxType) {

            case 'salary':

                [$incomeTaxAmount, $incomeTaxPercent, $explanation] = $this->calculatesalarytax(false, $year, $income);
                break;

            case 'pension':
                [$incomeTaxAmount, $incomeTaxPercent, $explanation] = $this->calculatesalarytax(false, $year, $income);
                break;

            case 'income':
                //The income category is special and we always assume everything that ends here, is taxed before transfer to this category.
                $incomeTaxAmount = round($income - $expence) * $incomeTaxPercent;
                break;

            case 'house':
                //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
                $incomeTaxAmount = ($income - $expence) * $incomeTaxPercent;
                break;

            case 'cabin':
                //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
                $standardDeduction = $this->getTaxStandardDeduction($taxGroup, 'airbnb', $year);
                if ($income - $standardDeduction > 0) {
                    $incomeTaxPercent = $this->getTaxIncome($taxGroup, 'airbnb', $year); //Should avoid hardcoding this extra tax check. Tax on each type within an asset? asset and income taxes?
                    $incomeTaxAmount = round(($income - $standardDeduction) * $incomeTaxPercent); //Airbnb skatten
                }
                break;

            case 'rental':
                //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
                $incomeTaxAmount = ($income - $expence) * $incomeTaxPercent;
                break;

            case 'property':
                //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
                $incomeTaxAmount = round($income - $expence) * $incomeTaxPercent;
                break;

            case 'stock':
                //Hm. Aksjer som selges skattes bare som formuesskatt og ved realisasjon
                $incomeTaxAmount = round($income - $expence) * $incomeTaxPercent;
                break;

            case 'equityfund':
                //skattes bare ved realisasjon + formuesskatt
                $incomeTaxAmount = round($income - $expence) * $incomeTaxPercent;
                break;

            case 'ask':
                //Aksjesparekonto.
                $incomeTaxAmount = round($income - $expence) * $incomeTaxPercent;
                break;

            case 'otp':
                //Pensjonssparing fra arbeidsgiver
                $incomeTaxAmount = round($income - $expence) * $incomeTaxPercent;
                break;

            case 'ips':
                //Pensjonssparing fra arbeidsgiver
                $incomeTaxAmount = round($income - $expence) * $incomeTaxPercent;
                break;

            case 'bank':
                $incomeTaxAmount = round($interestAmount * $incomeTaxPercent);
                if ($incomeTaxAmount != 0) {
                    $explanation = $incomeTaxPercent * 100 ."% tax on interest $interestAmount=$incomeTaxAmount";
                }
                break;

            case 'cash':
                $incomeTaxAmount = round($interestAmount * $incomeTaxPercent);
                if ($incomeTaxAmount != 0) {
                    $explanation = $incomeTaxPercent * 100 ."% tax on interest $interestAmount=$incomeTaxAmount";
                }
                break;

            case 'equitybond':
                $incomeTaxAmount = round($interestAmount * $incomeTaxPercent);
                if ($incomeTaxAmount != 0) {
                    $explanation = $incomeTaxPercent * 100 ."% tax on interest $interestAmount=$incomeTaxAmount";
                }
                break;

            default:
                //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
                $incomeTaxAmount = ($income - $expence) * $incomeTaxPercent;
                $explanation = "No tax rule found for: $taxType";
                break;
        }

        if ($debug) {
            echo "$taxType.$year: income: $income, incomeTaxAmount: $incomeTaxAmount, incomeTaxPercent: $incomeTaxPercent, explanation:$explanation\n";
        }

        //V kan ikke kalkulere videre på $fortuneTaxableAmount fordi det er summen av skatter som er for fradrag, vi kan ikke summere på dette tallet etterpå. Bunnfradraget må alltid gjøres på total summen. Denne regner det bare ut isolert sett for en asset.
        return [$incomeTaxAmount, $incomeTaxPercent, $explanation];
    }


    public function calculatesalarytax(bool $debug, int $year, int $amount)
    {
        $explanation = '';
        $commonTaxAmount = 0; //Fellesskatt
        $bracketTaxAmount = 0; //Trinnskatt
        $socialSecurityTaxAmount = 0; //Trygdeavgift
        $totalTaxAmount = 0; //Utregnet hva skatten faktisak er basert på de faktiske skattebeløpene.

        $commonTaxPercent = $this->getTaxSalary('private', 'common.rate', $year);
        $socialSecurityTaxPercent = $this->getTaxSalary('private', 'socialsecurity.rate', $year);
        $socialSecurityTaxDeductionAmount = $this->getTaxSalary('private', 'socialsecurity.deduction', $year);
        $totalTaxPercent = 0; //Utregnet hva skatten faktisak er basert på de faktiske skattebeløpene.

        $commonTaxAmount = round($amount * $commonTaxPercent);

        $socialSecurityTaxableAmount = ($amount - $socialSecurityTaxDeductionAmount);
        if($socialSecurityTaxableAmount > 0) {
            $socialSecurityTaxAmount = round($socialSecurityTaxableAmount * $socialSecurityTaxPercent);
        }
        [$bracketTaxAmount, $bracketTaxPercent, $explanation] = $this->calculateBracketTax(true, $year, $amount);

        $explanation = " Fellesskatt: " . $commonTaxPercent * 100 . "% gir $commonTaxAmount skatt, Trygdeavgift " . $socialSecurityTaxPercent * 100 . "% gir $socialSecurityTaxAmount skatt " . $explanation;

        $totalTaxAmount = $bracketTaxAmount + $commonTaxAmount + $socialSecurityTaxAmount;

        if($amount > 0 ) {
            $totalTaxPercent = round(($totalTaxAmount / $amount), 2); //We calculate a total percentage using the amounts
        }
        // Print debug information if debug is true
        if ($debug) {
            echo "   $year.salary, amount:$amount, totalTaxAmount:$totalTaxAmount, totalTaxPercent:" . $totalTaxPercent * 100 . ", $explanation\n";
        }

        return [$totalTaxAmount, $totalTaxPercent, $explanation];
    }


    public function calculateBracketTax(bool $debug, int $year, int $amount)
    {
        $explanation = '';
        $brackets = $this->getTaxBracket('private', 'bracket', $year);

        $bracketTaxAmount = 0;
        $bracketTotalTaxAmount = 0;
        $bracketTaxPercent = 0;
        $bracketTotalTaxPercent = 0;

        $prevLimitAmount = 0;
        foreach ($brackets as $bracket) {
            //print "Trinn " . $amount . " > " . $bracket['limit'] . "\n";
            $bracketTaxPercent = $bracket['rate'] / 100;

            if (isset($bracket['limit']) && $amount > $bracket['limit']) {
                $bracketTaxableAmount = $bracket['limit'] - $prevLimitAmount;
                $bracketTaxAmount = round($bracketTaxableAmount * $bracketTaxPercent);
                $bracketTotalTaxAmount += $bracketTaxAmount;

                $explanation .= " Bracket ($bracket[limit])$bracket[rate]%=$bracketTaxAmount,";
                //echo "Bracket limit $bracket[limit], amount: $amount, taxableAmount:$bracketTaxableAmount * $bracket[rate]% = tax: $bracketTaxAmount\n";

            } elseif(isset($bracket['limit'])) {
                //Amount is lower than limit, we are at the end and calculate the rest of the amount.
                $bracketTaxableAmount = $amount - $prevLimitAmount;
                $bracketTaxAmount = round($bracketTaxableAmount * $bracketTaxPercent);
                $bracketTotalTaxAmount += $bracketTaxAmount;
                $explanation .= " Bracket ($amount<)" . $bracket['limit'] . ")$bracket[rate]%=$bracketTaxAmount";
                //echo "Bracket $amount < " . $bracket['limit'] . " taxableAmount:$bracketTaxableAmount * $bracket[rate]% = tax: $bracketTaxAmount\n";

                break;
            } else {
                //Not set, then all tax after this is on bigger than logic, we are at the end of the calculation
                $bracketTaxableAmount = $amount - $prevLimitAmount;
                $bracketTaxAmount = round($bracketTaxableAmount * $bracketTaxPercent);
                $bracketTotalTaxAmount += $bracketTaxAmount;
                $explanation .= " Bracket (>$prevLimitAmount)$bracket[rate]%=$bracketTaxAmount";
                //echo "Bracket limit bigger than $prevLimitAmount taxableAmount:$bracketTaxableAmount * $bracket[rate]% = tax: $bracketTaxAmount\n";

                break;
            }
            $prevLimitAmount = $bracket['limit'];
        }

        if($amount > 0 ) {
            $bracketTotalTaxPercent = round(($bracketTaxAmount / $amount), 2); //We calculate a total percentage using the amounts
        }

        $explanation = " Trinnskatt:$bracketTotalTaxAmount snitt " . $bracketTotalTaxPercent * 100 . "%, " . $explanation;

        return [$bracketTotalTaxAmount, $bracketTotalTaxPercent, $explanation];
    }

}
