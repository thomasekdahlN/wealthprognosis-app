<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds tax configurations without throwing and skips unknown tax types with a helpful message', function (): void {
    // Run the specific seeder
    $result = $this->artisan('db:seed', ['--class' => Database\Seeders\TaxConfigurationSeeder::class]);

    // Should complete successfully
    $result->assertExitCode(0);

    // And the output should include a helpful skip message for unknown FK tax types
    // e.g. property_holmestrand not present in tax_types.json
    $result->expectsOutputToContain('Skipping tax configuration');

    // Ensure we did not create a config with an unknown tax type
    expect(\Illuminate\Support\Facades\DB::table('tax_configurations')
        ->where('tax_type', 'property_holmestrand')
        ->exists())->toBeFalse();
});
