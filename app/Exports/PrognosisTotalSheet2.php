<?php
namespace App\Exports;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\FromView;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PrognosisTotalSheet2
{
    private $name;
    private $totalH;
    private $companyH;
    private $groupH;
    private $config;
    private $spreadsheet;
    public $worksheet;
    public $periodStart;
    public $periodEnd;
    public static $letters = array(1 => "A", 2 => "B", 3=> "C", 4 => "D", 5 => "E", 6 => "F", 7 => "G", 8=> "H", 9=> "I", 10 => "J", 11 =>"K", 12 => "L", 13 => "M", 14 => "N", 15=> "O", 16 => "P", 17 => "Q", 18 => "R", 19 => "S", 20 => "T", 21 => "U", 22 => "V", 23 => "W", 24 => "X", 25 => "Y", 26 => "Z");

    public $columns = 41;
    public $rows = 4;
    public $groups = 3;

    public function __construct($spreadsheet, $config, $totalH, $groupH)
    {
        $this->name = "Total";
        $this->config = $config;
        $this->totalH = $totalH;
        $this->groupH = $groupH;

        $this->spreadsheet = $spreadsheet;
        $this->birthYear  = (integer) Arr::get($this->config, 'meta.birthYear', 1970);
        $this->economyStartYear = $this->birthYear + 16; #We look at economy from 16 years of age
        $this->deathYear  = (integer) $this->birthYear + Arr::get($this->config, 'meta.deathYear', 82);

        $mask = '£#,##0.00_-';

        $this->worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($this->spreadsheet, $this->name);

        $this->worksheet->setCellValue('A1', $this->name );

        $this->worksheet->setCellValue('C2',"Total");
        $this->worksheet->setCellValue('N2',"Company");
        $this->worksheet->setCellValue('AA2',"Private");

        $this->worksheet->setCellValue('A3',"Year");
        $this->worksheet->setCellValue('B3',"Age");

        $this->worksheet->setCellValue('C3',"Income");
        $this->worksheet->setCellValue('D3',"Expence");
        $this->worksheet->setCellValue("E3","Termin");
        $this->worksheet->setCellValue("F3","Rente");
        $this->worksheet->setCellValue("G3","Avdrag");
        $this->worksheet->setCellValue("H3","Rest");
        $this->worksheet->setCellValue('I3',"Skatt");
        $this->worksheet->setCellValue('J3',"Fradrag");
        $this->worksheet->setCellValue('K3',"Cashflow");
        $this->worksheet->setCellValue('L3',"Asset");
        $this->worksheet->setCellValue('M3',"Asset - lån");
        $this->worksheet->setCellValue('N3',"Grad");


        $this->worksheet->setCellValue('O3',"Income");
        $this->worksheet->setCellValue('P3',"Expence");
        $this->worksheet->setCellValue("Q3","Termin");
        $this->worksheet->setCellValue("R3","Rente");
        $this->worksheet->setCellValue("S3","Avdrag");
        $this->worksheet->setCellValue("T3","Rest");
        $this->worksheet->setCellValue('U3',"Skatt");
        $this->worksheet->setCellValue('V3',"Fradrag");
        $this->worksheet->setCellValue('W3',"Cashflow");
        $this->worksheet->setCellValue('X3',"Asset");
        $this->worksheet->setCellValue('Y3',"Asset - Lån");
        $this->worksheet->setCellValue('Z3',"Grad");


        $this->worksheet->setCellValue('AA3',"Income");
        $this->worksheet->setCellValue('AB3',"Expence");
        $this->worksheet->setCellValue("AC3","Termin");
        $this->worksheet->setCellValue("AD3","Rente");
        $this->worksheet->setCellValue("AE3","Avdrag");
        $this->worksheet->setCellValue("AF3","Rest");
        $this->worksheet->setCellValue('AG3',"Skatt");
        $this->worksheet->setCellValue('AH3',"Formues Skatt");
        $this->worksheet->setCellValue('AI3',"Fradrag");
        $this->worksheet->setCellValue('AJ3',"Cashflow");
        $this->worksheet->setCellValue('AK3',"Asset");
        $this->worksheet->setCellValue('AL3',"Skattbar formue");
        $this->worksheet->setCellValue('AM3',"Asset - lån");
        $this->worksheet->setCellValue('AN3',"Grad");
        $this->worksheet->setCellValue("AO3","Betjeningsevne");
        $this->worksheet->setCellValue("AP3","Max lån");
        $this->worksheet->setCellValue("AQ3","Evne");
        $this->worksheet->setCellValue("AR3","FIRE inntekt");
        $this->worksheet->setCellValue("AS3","FIRE utgift");
        $this->worksheet->setCellValue("AT3","FIRE diff");
        $this->worksheet->setCellValue("AU3","FIRE %");
        $this->worksheet->setCellValue("AX3","KPI");


        #total
        #print " $this->economyStartYear <= $this->deathYear\n";
        for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {

            #print "$year\n";
            $this->worksheet->setCellValue("A$this->rows",$year);
            $this->worksheet->setCellValue("B$this->rows",$year-Arr::get($this->config, 'meta.birthYear')); #Må bytte ut med en variabel her

            #Total
            if(isset($this->totalH[$year])) {
                $this->worksheet->setCellValue("C$this->rows", Arr::get($this->totalH[$year], "income.amount"));
                $this->worksheet->setCellValue("D$this->rows", Arr::get($this->totalH[$year], "expence.amount"));
                $this->worksheet->setCellValue("E$this->rows", Arr::get($this->totalH[$year], "mortgage.payment"));
                $this->worksheet->setCellValue("F$this->rows", Arr::get($this->totalH[$year], "mortgage.interestAmount"));
                $this->worksheet->setCellValue("G$this->rows", Arr::get($this->totalH[$year], "mortgage.principal"));
                $this->worksheet->setCellValue("H$this->rows", Arr::get($this->totalH[$year], "mortgage.balance"));
                $this->worksheet->setCellValue("I$this->rows", Arr::get($this->totalH[$year], "tax.amountTaxableYearly"));
                $this->worksheet->setCellValue("J$this->rows", Arr::get($this->totalH[$year], "tax.amountDeductableYearly"));
                $this->worksheet->setCellValue("K$this->rows", Arr::get($this->totalH[$year], "cashflow.amount"));
                $this->worksheet->setCellValue("L$this->rows", Arr::get($this->totalH[$year], "asset.amount"));
                $this->worksheet->setCellValue("M$this->rows", Arr::get($this->totalH[$year], "asset.amountLoanDeducted"));
                if (isset($this->totalH[$year]['asset']) && Arr::get($this->totalH[$year], "asset.amount") > 0) {
                   $this->worksheet->setCellValue("N$this->rows", Arr::get($this->totalH[$year], "mortgage.balance") / Arr::get($this->totalH[$year], "asset.amount"));
                }
            }

            #Company
            if(isset($this->groupH['company'][$year])) {
                $this->worksheet->setCellValue("O$this->rows", Arr::get($this->groupH['company'][$year], "income.amount"));
                $this->worksheet->setCellValue("P$this->rows", Arr::get($this->groupH['company'][$year], "expence.amount"));
                $this->worksheet->setCellValue("Q$this->rows", Arr::get($this->groupH['company'][$year], "mortgage.payment"));
                $this->worksheet->setCellValue("R$this->rows", Arr::get($this->groupH['company'][$year], "mortgage.interestAmount"));
                $this->worksheet->setCellValue("S$this->rows", Arr::get($this->groupH['company'][$year], "mortgage.principal"));
                $this->worksheet->setCellValue("T$this->rows", Arr::get($this->groupH['company'][$year], "mortgage.balance"));
                $this->worksheet->setCellValue("U$this->rows", Arr::get($this->groupH['company'][$year], "tax.amountTaxableYearly"));
                $this->worksheet->setCellValue("V$this->rows", Arr::get($this->groupH['company'][$year], "tax.amountDeductableYearly"));
                $this->worksheet->setCellValue("W$this->rows", Arr::get($this->groupH['company'][$year], "cashflow.amount"));
                $this->worksheet->setCellValue("X$this->rows", Arr::get($this->groupH['company'][$year], "asset.amount"));
                $this->worksheet->setCellValue("Y$this->rows", Arr::get($this->groupH['company'][$year], "asset.amountLoanDeducted"));
                $this->worksheet->setCellValue("Z$this->rows", Arr::get($this->groupH['company'][$year], "mortgage.balance") / Arr::get($this->groupH['company'][$year], "asset.amount"));
            }

            #Private
            if(isset($this->groupH['private'][$year])) {
                $this->worksheet->setCellValue("AA$this->rows", Arr::get($this->groupH['private'][$year], "income.amount"));
                $this->worksheet->setCellValue("AB$this->rows", Arr::get($this->groupH['private'][$year], "expence.amount"));
                $this->worksheet->setCellValue("AC$this->rows", Arr::get($this->groupH['private'][$year], "mortgage.payment"));
                $this->worksheet->setCellValue("AD$this->rows", Arr::get($this->groupH['private'][$year], "mortgage.interestAmount"));
                $this->worksheet->setCellValue("AE$this->rows", Arr::get($this->groupH['private'][$year], "mortgage.principal"));
                $this->worksheet->setCellValue("AF$this->rows", Arr::get($this->groupH['private'][$year], "mortgage.balance"));
                $this->worksheet->setCellValue("AG$this->rows", Arr::get($this->groupH['private'][$year], "tax.amountTaxableYearly"));
                $this->worksheet->setCellValue("AH$this->rows", $this->fortuneTax(Arr::get($this->groupH['private'][$year], "tax.amountFortune")));
                $this->worksheet->setCellValue("AI$this->rows", Arr::get($this->groupH['private'][$year], "tax.amountDeductableYearly"));
                $this->worksheet->setCellValue("AJ$this->rows", Arr::get($this->groupH['private'][$year], "cashflow.amount"));
                $this->worksheet->setCellValue("AK$this->rows", Arr::get($this->groupH['private'][$year], "asset.amount"));
                $this->worksheet->setCellValue("AL$this->rows", Arr::get($this->groupH['private'][$year], "tax.amountFortune"));
                $this->worksheet->setCellValue("AM$this->rows", Arr::get($this->groupH['private'][$year], "asset.amountLoanDeducted"));
                if(isset($this->groupH['private'][$year]['asset']) && Arr::get($this->groupH['private'][$year], "asset.amount") > 0) {
                    $this->worksheet->setCellValue("AN$this->rows", Arr::get($this->groupH['private'][$year], "mortgage.balance") / Arr::get($this->groupH['private'][$year], "asset.amount"));
                }
                $this->worksheet->setCellValue("AO$this->rows", Arr::get($this->groupH['private'][$year], "potential.income"));
                $this->worksheet->setCellValue("AP$this->rows", Arr::get($this->groupH['private'][$year], "potential.loan"));
                $this->worksheet->setCellValue("AQ$this->rows", Arr::get($this->groupH['private'][$year], "potential.loan") - Arr::get($this->groupH['private'][$year], "mortgage.balance"));

                $this->worksheet->setCellValue("AR$this->rows", Arr::get($this->groupH['private'][$year], "fire.amountIncome"));
                $this->worksheet->setCellValue("AS$this->rows", Arr::get($this->groupH['private'][$year], "fire.amountExpence"));
                $this->worksheet->setCellValue("AT$this->rows", Arr::get($this->groupH['private'][$year], "fire.amountDiff"));
                if(Arr::get($this->groupH['private'][$year], "fire.amountExpence") > 0 ) {
                    $this->worksheet->setCellValue("AU$this->rows", Arr::get($this->groupH['private'][$year], "fire.amountIncome") / Arr::get($this->groupH['private'][$year], "fire.amountExpence"));
                }
                $this->worksheet->setCellValue("AV$this->rows", Arr::get($this->groupH['private'][$year], "kpi.amount"));
            }
            $this->rows++;
        }
        $this->rows--;
    }

    public static function fortuneTax($amount) {
        $tax = ($amount - 1700000) * 0.0095; #This hardcoding should be configurable pr year. https://www.skatteetaten.no/satser/formuesskatt/?year=2022#rateShowYear
        if($tax < 0) { $tax = 0;}
        return $tax;
    }

    /**
     * Convert a $number to the letter (or combination of letters) representing a column in excel.
     *   Will return an empty string if $number is not a valid value.
     *
     * @param number Int must be is_numeric() and > 0 and < 16,385.
     *
     * @return String
     */
    public static function convertNumberToExcelCol($number){

        $column = "";

        if (is_numeric($number) and $number > 0 and $number < 16385){

            if ($number < 27){

                $column = self::$letters[$number];
            }
            elseif ($number < 703){

                if ($number % 26 === 0){

                    $first = floor($number / 26) - 1;

                    $second = 26;
                }
                else{

                    $first = floor($number / 26);

                    $second = $number % 26;
                }

                $column = self::$letters[$first] . self::$letters[$second];
            }
            else{

                if($number % 676 < 27){

                    $compensation = floor($number / 26) - 26;

                    $column = self::$letters[floor($number / 702)] . self::convertNumberToExcelCol($number % 702 + ($compensation % 26 === 0 ? $compensation : $compensation - 1));
                }
                else{
                    $column = self::$letters[floor($number / 676)] . self::convertNumberToExcelCol($number % 676);
                }
            }
        }

        return $column;
    }
}
