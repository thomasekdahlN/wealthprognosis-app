<?php

declare(strict_types=1);

use Illuminate\Support\Collection;

it('tax_types.json contains all types defined in no-tax-2025.json', function (): void {
    $taxTypesPath = base_path('config/tax/tax_types.json');
    $noTax2025Path = base_path('config/tax/no/no-tax-2025.json');

    expect(file_exists($taxTypesPath))->toBeTrue();
    expect(file_exists($noTax2025Path))->toBeTrue();

    $taxTypesRaw = json_decode((string) file_get_contents($taxTypesPath), true, 512, JSON_THROW_ON_ERROR);
    $noTax2025Raw = json_decode((string) file_get_contents($noTax2025Path), true, 512, JSON_THROW_ON_ERROR);

    // Collect the list of known tax types from tax_types.json
    $knownTypes = collect($taxTypesRaw)
        ->pluck('type')
        ->filter()
        ->values();

    // Collect the top-level keys from no-tax-2025.json
    $types2025 = collect(array_keys($noTax2025Raw))
        ->filter()
        ->values();

    // Calculate which 2025 types are missing from the known list
    /** @var Collection<int, string> $missing */
    $missing = $types2025->diff($knownTypes)->values();

    expect($missing->all())
        ->toBeEmpty('Missing tax types in config/tax/tax_types.json: '.implode(', ', $missing->all()));
});
