<?php
namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
class PrognosisAssetSheet1 implements FromView
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

        #print_r($this->name);
        #dd($this->asset);

        return view('exports.prognosis-asset-sheet', [
            'name' => $this->name,
            'asset' => $this->asset,
        ]);
    }

    public function title(): string
    {
        return $this->name . "test";
    }
}
