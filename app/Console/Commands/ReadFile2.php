<?php

namespace App\Console\Commands;

use App\Exports\PrognosisExport2;
use App\Models\Prognosis;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ReadFile2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ReadFile2 {configfile} {prognosis} {generate : All | Private | Company}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads json config file using phpexcel';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $exportfile  = dirname($this->argument('configfile')) . '/' . basename($this->argument('configfile'), '.json') . '_'. $this->argument('prognosis') . ".xlsx";

        new PrognosisExport2($this->argument('configfile'), $exportfile, $this->argument('prognosis'), $this->argument('generate'));
    }
}
