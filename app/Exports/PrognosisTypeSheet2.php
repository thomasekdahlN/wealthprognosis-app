<?php
namespace App\Exports;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\FromView;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PrognosisTypeSheet2
{
    private $name;
    private $groupH;
    private $config;
    private $spreadsheet;
    public $worksheet;
    public $periodStart;
    public $periodEnd;

    public $columns = 0;
    public $rows = 0;
    public $groups = 0;

    public static $letters = array(1 => "A", 2 => "B", 3=> "C", 4 => "D", 5 => "E", 6 => "F", 7 => "G", 8=> "H", 9=> "I", 10 => "J", 11 =>"K", 12 => "L", 13 => "M", 14 => "N", 15=> "O", 16 => "P", 17 => "Q", 18 => "R", 19 => "S", 20 => "T", 21 => "U", 22 => "V", 23 => "W", 24 => "X", 25 => "Y", 26 => "Z");


    public function __construct($spreadsheet, $config, $groupH)
    {
        $this->name = "Group";
        $this->config = $config;
        $this->groupH = $groupH;

        #print_r($this->groupH);

        $this->spreadsheet = $spreadsheet;
        $this->periodStart = (integer) Arr::get($this->config, 'period.start');
        $this->periodEnd  = (integer) Arr::get($this->config, 'period.end');

        $mask = '£#,##0.00_-';

        $this->worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($this->spreadsheet, $this->name);

        $this->worksheet->setCellValue('A1', 'Group' );
        $this->worksheet->setCellValue('A2', $this->name );
        $this->worksheet->setCellValue('A3',"Year");
        $this->worksheet->setCellValue('B3',"Age");

        #repeat from here
        $this->groups = count($this->groupH);

        $this->columns = 3;
        foreach($this->groupH as $groupname => $groupH) {

            $this->worksheet->setCellValue($this->convertNumberToExcelCol($this->columns) . 2, $groupname);

            $this->worksheet->setCellValue($this->convertNumberToExcelCol($this->columns) . 3, "Income");
            $this->worksheet->setCellValue($this->convertNumberToExcelCol($this->columns+1) . 3, "Expence");
            $this->worksheet->setCellValue($this->convertNumberToExcelCol($this->columns+2) . 3, "Skatt");
            $this->worksheet->setCellValue($this->convertNumberToExcelCol($this->columns+3) . 3, "Fradrag");
            $this->worksheet->setCellValue($this->convertNumberToExcelCol($this->columns+4) . 3, "Cashflow");
            $this->worksheet->setCellValue($this->convertNumberToExcelCol($this->columns+5) . 3, "Asset");
            $this->worksheet->setCellValue($this->convertNumberToExcelCol($this->columns+6) . 3, "Asset - lån");
            $this->columns += 7;
        }

        #total
        $this->rows = 4;
        for ($year = $this->periodStart; $year <= $this->periodEnd; $year++) {
            $this->worksheet->setCellValue("A$this->rows",$year);
            $this->worksheet->setCellValue("B$this->rows",$year-Arr::get($this->config, 'meta.birthyear')); #Må bytte ut med en variabel her

            #Iterate all groups
            $this->columns = 3;
            foreach($this->groupH as $groupH) {
                if(isset($groupH[$year])) {
                    $this->worksheet->setCellValue($this->convertNumberToExcelCol($this->columns) . $this->rows, Arr::get($groupH[$year], "income.amount"));
                    $this->worksheet->setCellValue($this->convertNumberToExcelCol($this->columns + 1) . $this->rows, Arr::get($groupH[$year], "expence.amount"));
                    $this->worksheet->setCellValue($this->convertNumberToExcelCol($this->columns + 2) . $this->rows, Arr::get($groupH[$year], "tax.amountTaxableYearly"));
                    $this->worksheet->setCellValue($this->convertNumberToExcelCol($this->columns + 3) . $this->rows, Arr::get($groupH[$year], "tax.amountDeductableYearly"));
                    $this->worksheet->setCellValue($this->convertNumberToExcelCol($this->columns + 4) . $this->rows, Arr::get($groupH[$year], "cashflow.amount"));
                    $this->worksheet->setCellValue($this->convertNumberToExcelCol($this->columns + 5) . $this->rows, Arr::get($groupH[$year], "asset.amount"));
                    $this->worksheet->setCellValue($this->convertNumberToExcelCol($this->columns + 6) . $this->rows, Arr::get($groupH[$year], "asset.amountLoanDeducted"));

                }
                $this->columns += 7;
            }
            $this->rows++;
        }
        $this->rows--;

        #$this->worksheet->getStyle("B2:B$i")->getNumberFormat()->setFormatCode('#,##0.00');
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
