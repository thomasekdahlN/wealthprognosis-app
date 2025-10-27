<?php

/* Copyright (C) 2024 Thomas Ekdahl
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

namespace App\Exports;

use Illuminate\Support\Arr;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PrognosisRealizationSheet2
{
    private string $name;

    /** @var array<string, mixed> */
    private array $totalH;

    /** @var array<string, mixed> */
    private array $config;

    private Spreadsheet $spreadsheet;

    public Worksheet $worksheet;

    public int $periodStart;

    public int $periodEnd;

    public int $birthYear;

    public int $economyStartYear;

    public int $deathYear;

    /** @var array<int, string> */
    public static array $letters = [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F', 7 => 'G', 8 => 'H', 9 => 'I', 10 => 'J', 11 => 'K', 12 => 'L', 13 => 'M', 14 => 'N', 15 => 'O', 16 => 'P', 17 => 'Q', 18 => 'R', 19 => 'S', 20 => 'T', 21 => 'U', 22 => 'V', 23 => 'W', 24 => 'X', 25 => 'Y', 26 => 'Z'];

    public int $columns = 5;

    public int $rows = 4;

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $totalH
     */
    public function __construct(Spreadsheet $spreadsheet, array $config, array $totalH)
    {
        $this->name = 'Realisasjon';
        $this->config = $config;
        $this->totalH = $totalH;

        $this->spreadsheet = $spreadsheet;
        $this->birthYear = (int) Arr::get($this->config, 'meta.birthYear', 1990);
        $this->economyStartYear = $this->birthYear + 16; // We look at economy from 16 years of age
        $this->deathYear = (int) $this->birthYear + Arr::get($this->config, 'meta.deathYear', 82);

        $mask = '£#,##0.00_-';

        $this->worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($this->spreadsheet, $this->name);

        $this->worksheet->setCellValue('A1', $this->name);

        $this->worksheet->setCellValue('A3', 'Year');
        $this->worksheet->setCellValue('B3', 'Age');

        $this->worksheet->setCellValue('C3', 'Asset verdi');
        $this->worksheet->setCellValue('D3', 'Skatt ved realisasjon');
        $this->worksheet->setCellValue('E3', 'Verdi etter realisasjon');

        // total
        // print " $this->economyStartYear <= $this->deathYear\n";
        for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {

            // print "$year\n";
            $this->worksheet->setCellValue("A$this->rows", $year);
            $this->worksheet->setCellValue("B$this->rows", $year - Arr::get($this->config, 'meta.birthYear'));

            // dd($this->totalH);

            // Total
            // if(isset($this->totalH[$year])) {
            $this->worksheet->setCellValue("C$this->rows", Arr::get($this->totalH, "$year.asset.amount"));
            $this->worksheet->setCellValue("D$this->rows", Arr::get($this->totalH, "$year.tax.amountTaxableRealization"));
            $this->worksheet->setCellValue("E$this->rows", Arr::get($this->totalH, "$year.asset.amount") - Arr::get($this->totalH, "$year.tax.amountTaxableRealization"));
            // }

            $this->rows++;
        }
        $this->rows--;
    }

    /**
     * Convert a $number to the letter (or combination of letters) representing a column in excel.
     *   Will return an empty string if $number is not a valid value.
     *
     * @param  int  $number  Must be is_numeric() and > 0 and < 16,385.
     * @return string
     */
    public static function convertNumberToExcelCol($number)
    {

        $column = '';

        if ($number > 0 and $number < 16385) {

            if ($number < 27) {

                $column = self::$letters[$number];
            } elseif ($number < 703) {

                if ($number % 26 === 0) {

                    $first = floor($number / 26) - 1;

                    $second = 26;
                } else {

                    $first = floor($number / 26);

                    $second = $number % 26;
                }

                $column = self::$letters[$first].self::$letters[$second];
            } else {

                if ($number % 676 < 27) {

                    $compensation = floor($number / 26) - 26;

                    $column = self::$letters[(int) floor($number / 702)].self::convertNumberToExcelCol((int) ($number % 702 + ($compensation % 26 === 0 ? $compensation : $compensation - 1)));
                } else {
                    $column = self::$letters[(int) floor($number / 676)].self::convertNumberToExcelCol((int) ($number % 676));
                }
            }
        }

        return $column;
    }
}
