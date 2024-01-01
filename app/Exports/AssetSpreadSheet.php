<?php
namespace App\Exports;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\FromView;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetSpreadSheet
{
    private $name;
    private $asset;
    private $meta;
    private $spreadsheet;
    public $worksheet;

    public int $columns = 26;
    public int $rows = 6;
    public int $rowHeader = 5;

    public int $groups = 1;

    public $letter = 1;

    public $alphabet = ['A','B','C','D','E','F','G','H','I','J','K','L'];

    public function __construct($spreadsheet, $statistics)
    {
        $this->spreadsheet = $spreadsheet;
        $this->worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($this->spreadsheet, 'Statistics');

        $start_letter = 1;
        $end_letter = 10;

        foreach ($statistics as $year => $typeH) {

            $this->worksheet->setCellValue($this->alphabet[0] . $this->rows, $year);

            foreach ($typeH as $typename => $data) {

                if($this->rows == 6) {
                    #Lag header
                    print $this->alphabet[$this->letter];
                    $this->worksheet->setCellValue($this->alphabet[$this->letter] . "5", $typename);
                }

                if ($typeH['total']['amount'] > 0) {

                    if (Arr::get($statistics, "$year.$typename.decimal") != 0) {
                        $this->worksheet->setCellValue($this->alphabet[$this->letter] . $this->rows, Arr::get($statistics, "$year.$typename.decimal"));
                    }

                }
                $this->letter++;
            }
            $this->letter = 1;
            $this->rows++;
        }
    }
}
