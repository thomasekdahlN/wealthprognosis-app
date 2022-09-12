<?php
namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class PrognosisTotalSheet1 implements FromView
{
    public $name;
    public $asset;
    public function __construct($name, $asset)
    {
        $this->name = $name;
        $this->asset = $asset;
    }
    public function view(): View
    {
        return view('exports.prognosis-total-sheet', [
            'name' => $this->name,
            'asset' => $this->asset,
        ]);
    }

    public function title(): string
    {
        return "Total";
        return $this->name . "Total";
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_YYYY,
            'B' => NumberFormat::TYPE_STRING,
            'C' => NumberFormat::TYPE_STRING,
            'D' => NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE,
            'E' => NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE,
            'F' => NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE,
        ];
    }
}
