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

use App\Exports\PrognosisExport2;
use Illuminate\Console\Command;

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
        ini_set('memory_limit', '512M');

        $exportfile = dirname($this->argument('configfile')).'/'.basename($this->argument('configfile'), '.json').'_'.$this->argument('prognosis').'.xlsx';

        new PrognosisExport2($this->argument('configfile'), $exportfile, $this->argument('prognosis'), $this->argument('generate'));
    }
}
