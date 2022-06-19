<?php

namespace App\Console\Commands;

use App\Exports\Exports;
use App\Models\Prognosis;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ReadFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ReadFile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads json config file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Excel::store(new Exports, 'prognosis.xlsx');
    }
}