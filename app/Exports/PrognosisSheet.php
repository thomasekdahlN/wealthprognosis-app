<?php
namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
class PrognosisSheet implements FromView
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
        return view('exports.prognosis-sheet', [
            'name' => $this->name,
            'asset' => $this->asset,
        ]);
    }
}