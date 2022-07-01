<?php
namespace App\Exports;

use App\Models\Prognosis;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PrognosisExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        $sheets = [];

        $prognosis = (new Prognosis(storage_path() . "/wealth.json"))->data;

        foreach($prognosis as $assetname => $asset) {
            $sheets[] = new PrognosisSheet($assetname, $asset);
        }

        return $sheets;
    }

    public function title(): string
    {
        return 'Javla bra kode  ';
    }
}