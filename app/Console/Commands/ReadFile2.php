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
    protected $signature = 'ReadFile2 {configfile} {exportfile}';

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
        #new PrognosisExport($this->argument('configfile'));

        new PrognosisExport2($this->argument('configfile'), $this->argument('exportfile'));
    }
}
