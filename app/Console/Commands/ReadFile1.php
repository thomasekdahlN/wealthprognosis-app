<?php

namespace App\Console\Commands;

use App\Exports\PrognosisExport1;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ReadFile1 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ReadFile1 {configfile} {exportfile}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads json config file using maatwebsite/excel';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //new PrognosisExport($this->argument('configfile'));

        Excel::store(new PrognosisExport1($this->argument('configfile')), $this->argument('exportfile'));
    }
}
