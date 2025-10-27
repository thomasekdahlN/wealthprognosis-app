<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ScrapeSsbNorwegianPropertyTax extends Command
{
    protected $signature = 'tax:scrape-no-property {year=2025} {--output=config/tax/no/property-:year.json} {--url=https://www.ssb.no/offentlig-sektor/kommunale-finanser/artikler/kommuner-med-eiendomsskatt}';

    protected $description = 'Scrape SSB HTML page and generate config/tax/no/property-<year>.json for Norwegian municipalities property tax';

    public function handle(): int
    {
        $year = (int) $this->argument('year');
        $output = str_replace(':year', (string) $year, (string) $this->option('output'));
        $url = (string) $this->option('url');

        $resp = Http::get($url);
        if (! $resp->ok()) {
            $this->error('Failed to fetch URL: '.$url.' (status '.$resp->status().')');

            return self::FAILURE;
        }
        $html = $resp->body();

        // Improved HTML scraping: detect header columns and map municipality, promille and bunnfradrag; merge across multiple tables
        $rows = [];

        // Normalize whitespace but keep tags
        $html = preg_replace('/\s+/', ' ', (string) $html);

        // Helper: parse numbers with Norwegian formatting
        $parseNumber = function (string $s): float {
            $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5);
            // Remove non-breaking spaces & spaces
            $s = str_replace(["\xC2\xA0", '\u00a0', ' '], '', $s);
            // Remove thousands separators
            $s = str_replace(['.', "\t"], '', $s);
            // Use dot as decimal separator
            $s = str_replace(',', '.', $s);
            if (preg_match('/-?[0-9]+(?:\.[0-9]+)?/', $s, $m)) {
                return (float) $m[0];
            }

            return 0.0;
        };

        if (preg_match_all('/<table[^>]*>(.*?)<\/table>/', $html, $tableMatches)) {
            foreach ($tableMatches[1] as $tableHtml) {
                if (! preg_match_all('/<tr[^>]*>(.*?)<\/tr>/', $tableHtml, $trMatches)) {
                    continue;
                }

                $headerMap = [
                    'municipality' => null,
                    'home_rate' => null,
                    'home_has' => null,
                    'company_rate' => null,
                    'company_has' => null,
                    'deduction' => null,
                ];

                foreach ($trMatches[1] as $i => $trHtml) {
                    if (! preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/', $trHtml, $cellMatches)) {
                        continue;
                    }
                    $cols = array_map(fn ($v) => trim(strip_tags($v)), $cellMatches[1]);
                    if (empty($cols)) {
                        continue;
                    }

                    if ($i === 0) {
                        // Detect headers (lowercased Norwegian text)
                        foreach ($cols as $idx => $h) {
                            $hNorm = Str::lower(trim($h));
                            if ($headerMap['municipality'] === null && (Str::contains($hNorm, 'kommune') || Str::contains($hNorm, 'municipality') || Str::contains($hNorm, 'kommunenavn'))) {
                                $headerMap['municipality'] = $idx;
                            }
                            if ($headerMap['deduction'] === null && (Str::contains($hNorm, 'bunnfradrag') || Str::contains($hNorm, 'fradrag') || Str::contains($hNorm, 'deduction'))) {
                                $headerMap['deduction'] = $idx;
                            }
                            if ($headerMap['home_rate'] === null && ((Str::contains($hNorm, 'bolig') && (Str::contains($hNorm, 'sats') || Str::contains($hNorm, 'promille') || Str::contains($hNorm, 'skattesats'))) || Str::contains($hNorm, 'boligsats'))) {
                                $headerMap['home_rate'] = $idx;
                            }
                            if ($headerMap['company_rate'] === null && ((Str::contains($hNorm, 'næring') || Str::contains($hNorm, 'verk og bruk')) && (Str::contains($hNorm, 'sats') || Str::contains($hNorm, 'promille') || Str::contains($hNorm, 'skattesats')))) {
                                $headerMap['company_rate'] = $idx;
                            }
                            if ($headerMap['home_has'] === null && (Str::contains($hNorm, 'har') && Str::contains($hNorm, 'eiendomsskatt'))) {
                                $headerMap['home_has'] = $idx;
                            }
                            if ($headerMap['company_has'] === null && (Str::contains($hNorm, 'næring') && (Str::contains($hNorm, 'har') || Str::contains($hNorm, 'ja')))) {
                                $headerMap['company_has'] = $idx;
                            }
                        }

                        continue;
                    }

                    // Determine municipality
                    $munIdx = $headerMap['municipality'] ?? 0;
                    $municipality = $cols[$munIdx] ?? null;
                    if (! $municipality || Str::length($municipality) < 2) {
                        continue;
                    }
                    $code = Str::slug($municipality);

                    // Initialize record if needed
                    if (! isset($rows[$code])) {
                        $rows[$code] = [
                            'municipality' => $municipality,
                            'hasTaxOnHomes' => false,
                            'hasTaxOnCompanies' => false,
                            'taxHomePermill' => 0.0,
                            'taxCompanyPermill' => 0.0,
                            'deduction' => 0.0,
                        ];
                    }

                    // Apply columns
                    if ($headerMap['home_rate'] !== null && isset($cols[$headerMap['home_rate']])) {
                        $num = $parseNumber($cols[$headerMap['home_rate']]);
                        if ($num > 0) {
                            $rows[$code]['taxHomePermill'] = $num;
                            $rows[$code]['hasTaxOnHomes'] = true;
                        }
                    }
                    if ($headerMap['home_has'] !== null && isset($cols[$headerMap['home_has']])) {
                        $val = Str::lower($cols[$headerMap['home_has']]);
                        if (Str::contains($val, ['ja', 'yes', 'true'])) {
                            $rows[$code]['hasTaxOnHomes'] = true;
                        } elseif (Str::contains($val, ['nei', 'no', 'false'])) {
                            $rows[$code]['hasTaxOnHomes'] = false;
                        }
                    }
                    if ($headerMap['company_rate'] !== null && isset($cols[$headerMap['company_rate']])) {
                        $num = $parseNumber($cols[$headerMap['company_rate']]);
                        if ($num > 0) {
                            $rows[$code]['taxCompanyPermill'] = $num;
                            $rows[$code]['hasTaxOnCompanies'] = true;
                        }
                    }
                    if ($headerMap['company_has'] !== null && isset($cols[$headerMap['company_has']])) {
                        $val = Str::lower($cols[$headerMap['company_has']]);
                        if (Str::contains($val, ['ja', 'yes', 'true'])) {
                            $rows[$code]['hasTaxOnCompanies'] = true;
                        } elseif (Str::contains($val, ['nei', 'no', 'false'])) {
                            $rows[$code]['hasTaxOnCompanies'] = false;
                        }
                    }
                    if ($headerMap['deduction'] !== null && isset($cols[$headerMap['deduction']])) {
                        $num = $parseNumber($cols[$headerMap['deduction']]);
                        if ($num > 0) {
                            $rows[$code]['deduction'] = $num;
                        }
                    }
                }
            }
        }

        if (empty($rows)) {
            $this->error('Could not parse municipality data from the page. The page may be JS-rendered; consider providing a CSV export or enabling API fetch.');

            return self::FAILURE;
        }

        // Apply business rule defaults
        foreach ($rows as $code => $data) {
            if (! $data['hasTaxOnCompanies']) {
                $rows[$code]['hasTaxOnCompanies'] = false;
                $rows[$code]['taxCompanyPermill'] = 0.0;
            }
            if ($data['taxHomePermill'] > 0 && $data['hasTaxOnHomes'] === false) {
                $rows[$code]['hasTaxOnHomes'] = true;
            }
        }

        if (! File::exists(dirname($output))) {
            File::makeDirectory(dirname($output), 0755, true);
        }
        File::put($output, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('Wrote '.count($rows).' entries to '.$output);

        return self::SUCCESS;
    }
}
