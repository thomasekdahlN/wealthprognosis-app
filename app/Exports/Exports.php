<?php
namespace App\Exports;

use App\Models\Prognosis;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class Exports implements WithMultipleSheets
{
    use Exportable;

    public function __construct()
    {
        return collect([
            [1, 2, 3],
            [4, 5, 6]
        ]);
        $this->sheets();
        //return (new Prognosis(storage_path() . "/wealth.json"))->collections;
    }

    public function sheets(): array
    {
        $sheets = [];

        $test = collect([
            [1, 2, 3],
            [4, 5, 6]
        ]);

        $prognosis = (new Prognosis(storage_path() . "/wealth.json"))->collections;

        foreach($prognosis as $year) {
            print_r($test);
            $sheets[] = $test;
        }

        return $sheets;
    }

    public function title(): string
    {
        return 'Javla bra kode  ';
    }
}