<?php

use App\Models\TaxProperty;
use App\Models\User;
use Filament\Facades\Filament;

it('can display tax property edit form with calculation example', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);
    Filament::setCurrentPanel('system');

    $taxProperty = TaxProperty::create([
        'country_code' => 'no',
        'year' => 2025,
        'code' => 'test-municipality',
        'municipality' => 'Test Municipality',
        'has_tax_on_homes' => true,
        'has_tax_on_companies' => true,
        'tax_home_permill' => 2.5,
        'tax_company_permill' => 4.0,
        'deduction' => 500000,
        'taxable_percent' => 80.0,
        'is_active' => true,
    ]);

    $response = $this->get("/system/tax-property/tax-properties/{$taxProperty->id}/edit");

    $response->assertSuccessful();
    $response->assertSee('Test Municipality');
    $response->assertSee('Property Tax Calculation Example');
    $response->assertSee('How Property Tax is Calculated');
});

it('displays correct calculation example for ringerike', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);
    Filament::setCurrentPanel('system');

    $ringerike = TaxProperty::where('code', 'ringerike')->first();

    if (! $ringerike) {
        $ringerike = TaxProperty::create([
            'country_code' => 'no',
            'year' => 2025,
            'code' => 'ringerike',
            'municipality' => 'Ringerike',
            'has_tax_on_homes' => true,
            'has_tax_on_companies' => true,
            'tax_home_permill' => 2.4,
            'tax_company_permill' => 3.7,
            'deduction' => 400000,
            'taxable_percent' => 70.0,
            'is_active' => true,
        ]);
    }

    $response = $this->get("/system/tax-property/tax-properties/{$ringerike->id}/edit");

    $response->assertSuccessful();
    $response->assertSee('Ringerike');
    $response->assertSee('4 080 NOK'); // Expected home tax for 3M property
    $response->assertSee('6 290 NOK'); // Expected company tax for 3M property
    $response->assertSee('2.4‰'); // Home rate
    $response->assertSee('3.7‰'); // Company rate
    $response->assertSee('Property Tax Calculation Example'); // Section title
});
