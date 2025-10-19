<?php

namespace Database\Seeders;

use App\Models\TaxProperty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TaxPropertySeeder extends Seeder
{
    public function run(): void
    {
        $base = config_path('tax');
        if (! File::exists($base)) {
            return;
        }

        $countries = File::directories($base);
        foreach ($countries as $countryDir) {
            $country = basename($countryDir);
            $files = File::files($countryDir);
            foreach ($files as $file) {
                $name = $file->getFilename();
                if (! str_contains($name, 'property-')) {
                    continue;
                }
                $year = (int) preg_replace('/\D+/', '', $name) ?: null;
                if (! $year) {
                    continue;
                }

                $json = File::json($file->getPathname());
                foreach ($json as $code => $conf) {
                    TaxProperty::updateOrCreate(
                        [
                            'country_code' => strtolower($country),
                            'year' => $year,
                            'code' => $code,
                        ],
                        [
                            // Norway (per user spec)
                            'municipality' => $conf['municipality'] ?? ($conf['municipalityName'] ?? ucfirst($code)),
                            'has_tax_on_homes' => (bool) ($conf['hasTaxOnHomes'] ?? $conf['hasPropertyTax'] ?? false),
                            'has_tax_on_companies' => (bool) ($conf['hasTaxOnCompanies'] ?? false),
                            'tax_home_permill' => (($v = ($conf['taxHomePermill'] ?? $conf['taxHomePercent'] ?? $conf['taxHome'] ?? $conf['averageRatePromille'] ?? null)) === null || (float) $v == 0.0 ? null : (float) $v),
                            'tax_company_permill' => (($v2 = ($conf['taxCompanyPermill'] ?? $conf['taxCompanyPercent'] ?? $conf['taxCompany'] ?? null)) === null || (float) $v2 == 0.0 ? null : (float) $v2),
                            'deduction' => (float) ($conf['deduction'] ?? $conf['standardDeduction'] ?? 0),
                            'fortune_taxable_percent' => (($v3 = ($conf['fortune'] ?? $conf['fortuneTaxablePercent'] ?? null)) === null || (float) $v3 == 0.0 ? null : (float) $v3),
                            'is_active' => $conf['is_active'] ?? true,
                        ]
                    );
                }
            }
        }
    }
}
