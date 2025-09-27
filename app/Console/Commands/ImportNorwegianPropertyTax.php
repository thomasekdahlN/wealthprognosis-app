<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportNorwegianPropertyTax extends Command
{
    protected $signature = 'tax:import-no-property {year=2025} {--output=config/tax/no/property-:year.json}';

    protected $description = 'Generate config/tax/no/property-<year>.json from a local CSV/JSON export (provide file path when prompted).';

    public function handle(): int
    {
        $year = (int) $this->argument('year');
        $output = str_replace(':year', (string) $year, (string) $this->option('output'));

        $path = $this->ask('Provide path to SSB export (CSV or JSON)');
        if (! $path || ! File::exists($path)) {
            $this->error('File not found: '.$path);

            return self::FAILURE;
        }

        $rows = [];
        if (Str::endsWith($path, '.json')) {
            $rows = File::json($path);
        } elseif (Str::endsWith($path, '.csv')) {
            $rows = array_map('str_getcsv', file($path));
        } else {
            $this->error('Unsupported file type. Use JSON or CSV.');

            return self::FAILURE;
        }

        // Transform rows to key => config array using user-specified schema
        $result = [];
        foreach ($rows as $row) {
            if (is_array($row) && array_is_list($row)) {
                // CSV example: [municipality, hasTaxOnHomes, hasTaxOnCompanies, taxHome, taxCompany, deduction]
                [$municipality, $hasHomes, $hasCompanies, $taxHome, $taxCompany, $deduction] = array_pad($row, 6, null);
                $code = Str::slug((string) $municipality);
                $result[$code] = [
                    'municipality' => (string) $municipality,
                    'hasTaxOnHomes' => filter_var($hasHomes, FILTER_VALIDATE_BOOLEAN),
                    'hasTaxOnCompanies' => filter_var($hasCompanies, FILTER_VALIDATE_BOOLEAN),
                    'taxHomePermill' => (float) $taxHome,
                    'taxCompanyPermill' => (float) $taxCompany,
                    'deduction' => (float) $deduction,
                ];
            } elseif (is_array($row)) {
                $municipality = $row['municipality'] ?? $row['name'] ?? null;
                if (! $municipality) {
                    continue;
                }
                $code = Str::slug((string) $municipality);
                $result[$code] = [
                    'municipality' => (string) $municipality,
                    'hasTaxOnHomes' => (bool) ($row['hasTaxOnHomes'] ?? false),
                    'hasTaxOnCompanies' => (bool) ($row['hasTaxOnCompanies'] ?? false),
                    'taxHomePermill' => (float) ($row['taxHome'] ?? $row['taxHomePermill'] ?? $row['taxHomePercent'] ?? 0),
                    'taxCompanyPermill' => (float) ($row['taxCompany'] ?? $row['taxCompanyPermill'] ?? $row['taxCompanyPercent'] ?? 0),
                    'deduction' => (float) ($row['deduction'] ?? 0),
                ];
            }
        }

        if (! File::exists(dirname($output))) {
            File::makeDirectory(dirname($output), 0755, true);
        }

        File::put($output, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('Wrote '.count($result).' entries to '.$output);

        return self::SUCCESS;
    }
}
