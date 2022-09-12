<?php
namespace App\Exports;

use App\Models\Prognosis;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithBackgroundColor;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class PrognosisExport1 implements WithMultipleSheets, ShouldAutoSize, WithStyles, WithBackgroundColor
{
    use Exportable;
    public $configfile;

    public function __construct($configfile)
    {
        $this->configfile = $configfile;
    }

    public function sheets(): array
    {
        $sheets = [];

        $prognosis = (new Prognosis($this->configfile));

        #dd($prognosis);

        $sheets[] = new PrognosisTotalSheet1("Total", $prognosis->totalH);
        #$sheets[] = new PrognosisTypeSheet("Type", $prognosis->typeH);

        foreach($prognosis->dataH as $assetname => $asset) {
            $sheets[] = new PrognosisAssetSheet1($assetname, $asset);
        }

        return $sheets;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['size' => 20, 'bold' => true]],

            // Styling an entire column.
            'A'  => ['font' => ['size' => 16]],
        ];
    }

    public function backgroundColor()
    {
        // Return a Color instance. The fill type will automatically be set to "solid"
        return new Color(Color::COLOR_YELLOW);
    }
}
