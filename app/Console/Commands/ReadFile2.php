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
     */
    public function handle(): int
    {
        ini_set('memory_limit', '512M');

        $configFile = $this->argument('configfile');
        $prognosis = $this->argument('prognosis');
        $generate = $this->argument('generate');

        // Validate file exists before processing
        if (! file_exists($configFile)) {
            $this->error("Configuration file not found: {$configFile}");
            $this->newLine();
            $this->warn('Please check that the file path is correct and the file exists.');

            return self::FAILURE;
        }

        $exportfile = dirname($configFile).'/'.basename($configFile, '.json').'_'.$prognosis.'.xlsx';

        try {
            new PrognosisExport2($configFile, $exportfile, $prognosis, $generate);

            $this->newLine();
            $this->info('✅ Export completed successfully!');
            $this->line("   Output: {$exportfile}");
            $this->newLine();

            return self::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            // JSON validation errors or file not found (already displayed by PrognosisExport2)
            $this->newLine();
            $this->error('Export failed due to validation error.');
            $this->newLine();

            return self::FAILURE;
        } catch (\RuntimeException $e) {
            // File read errors (already displayed by PrognosisExport2)
            $this->newLine();
            $this->error('Export failed due to file access error.');
            $this->newLine();

            return self::FAILURE;
        } catch (\Exception $e) {
            // Unexpected errors
            $this->newLine();
            $this->error('Export failed with unexpected error:');
            $this->error($e->getMessage());
            $this->newLine();

            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }
}
