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

class AssetSpreadSheet
{
    private Spreadsheet $spreadsheet;

    public Worksheet $worksheet;

    public int $columns = 26;

    public int $rows = 6;

    public int $rowHeader = 5;

    public int $groups = 1;

    public int $letter = 1;

    /** @var array<int, string> */
    public array $alphabet = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];

    /**
     * @param  array<string, mixed>  $statistics
     */
    public function __construct(Spreadsheet $spreadsheet, array $statistics)
    {
        $this->spreadsheet = $spreadsheet;
        $this->worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($this->spreadsheet, 'Statistics');

        $start_letter = 1;
        $end_letter = 10;

        foreach ($statistics as $year => $typeH) {

            $this->worksheet->setCellValue($this->alphabet[0].$this->rows, $year);

            foreach ($typeH as $typename => $data) {

                if ($this->rows == 6) {
                    // Lag header
                    // echo $this->alphabet[$this->letter];
                    // $this->worksheet->setCellValue($this->alphabet[$this->letter].'5', $typename);
                }

                if ($typeH['total']['amount'] > 0) {

                    if (Arr::get($statistics, "$year.$typename.decimal") != 0) {
                        $this->worksheet->setCellValue($this->alphabet[$this->letter].$this->rows, Arr::get($statistics, "$year.$typename.decimal"));
                    }

                }
                $this->letter++;
            }
            $this->letter = 1;
            $this->rows++;
        }
    }
}
