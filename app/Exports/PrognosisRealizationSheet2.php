<?php
namespace App\Exports;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\FromView;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PrognosisRealizationSheet2
{
    private $name;
    private $totalH;
    private $companyH;
    private $config;
    private $spreadsheet;
    public $worksheet;
    public $periodStart;
    public $periodEnd;
    public static $letters = array(1 => "A", 2 => "B", 3=> "C", 4 => "D", 5 => "E", 6 => "F", 7 => "G", 8=> "H", 9=> "I", 10 => "J", 11 =>"K", 12 => "L", 13 => "M", 14 => "N", 15=> "O", 16 => "P", 17 => "Q", 18 => "R", 19 => "S", 20 => "T", 21 => "U", 22 => "V", 23 => "W", 24 => "X", 25 => "Y", 26 => "Z");

    public $columns = 5;
    public $rows = 4;


    public function __construct($spreadsheet, $config, $totalH)
    {
        $this->name = "Realisasjon";
        $this->config = $config;
        $this->totalH = $totalH;

        $this->spreadsheet = $spreadsheet;
        $this->birthYear  = (integer) Arr::get($this->config, 'meta.birthYear', 1990);
        $this->economyStartYear = $this->birthYear + 16; #We look at economy from 16 years of age
        $this->deathYear  = (integer) $this->birthYear + Arr::get($this->config, 'meta.deathYear', 82);

        $mask = '£#,##0.00_-';

        $this->worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($this->spreadsheet, $this->name);

        $this->worksheet->setCellValue('A1', $this->name );

        $this->worksheet->setCellValue('A3',"Year");
        $this->worksheet->setCellValue('B3',"Age");

        $this->worksheet->setCellValue('C3',"Asset verdi");
        $this->worksheet->setCellValue('D3',"Skatt ved realisasjon");
        $this->worksheet->setCellValue('E3',"Verdi etter realisasjon");

        #total
        #print " $this->economyStartYear <= $this->deathYear\n";
        for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {

            #print "$year\n";
            $this->worksheet->setCellValue("A$this->rows",$year);
            $this->worksheet->setCellValue("B$this->rows",$year-Arr::get($this->config, 'meta.birthYear'));

            #dd($this->totalH);

            #Total
            #if(isset($this->totalH[$year])) {
                $this->worksheet->setCellValue("C$this->rows", Arr::get($this->totalH, "$year.asset.amount"));
                $this->worksheet->setCellValue("D$this->rows", Arr::get($this->totalH, "$year.tax.amountTaxableRealization"));
                $this->worksheet->setCellValue("E$this->rows", Arr::get($this->totalH, "$year.asset.amount") - Arr::get($this->totalH, "$year.tax.amountTaxableRealization"));
            #}

            $this->rows++;
        }
        $this->rows--;
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
