<?php

/* Copyright (C) 2024 Thomas Ekdahl
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

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
        // new PrognosisExport($this->argument('configfile'));

        Excel::store(new PrognosisExport1($this->argument('configfile')), $this->argument('exportfile'));
    }
}
