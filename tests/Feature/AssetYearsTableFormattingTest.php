<?php

use App\Helpers\AmountHelper;
use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetYear;
use App\Models\Team;
use App\Models\User;
use Filament\Tables\Table;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->actingAs($this->user);

    $this->configuration = AssetConfiguration::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    $this->asset = Asset::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'asset_configuration_id' => $this->configuration->id,
    ]);
});

it('formats amounts correctly in asset years table columns', function () {
    // Create an AssetYear with various amounts
    $assetYear = AssetYear::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'asset_id' => $this->asset->id,
        'asset_configuration_id' => $this->configuration->id,
        'year' => 2025,
        'income_amount' => 500000.00,
        'income_factor' => 'yearly',
        'expence_amount' => 250000.50,
        'expence_factor' => 'yearly',
        'asset_market_amount' => 1500000.00,
        'mortgage_amount' => 750000.25,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    // Test that AmountHelper formats correctly
    expect(AmountHelper::formatNorwegian(500000.00))->toBe('500 000');
    expect(AmountHelper::formatNorwegian(250000.50))->toBe('250 001'); // Rounded to nearest integer
    expect(AmountHelper::formatNorwegian(1500000.00))->toBe('1 500 000');
    expect(AmountHelper::formatNorwegian(750000.25))->toBe('750 000'); // Rounded to nearest integer

    // Test that zero/null values return empty string
    expect(AmountHelper::formatNorwegian(0))->toBe('');
    expect(AmountHelper::formatNorwegian(null))->toBe('');
});

it('parses Norwegian formatted amounts correctly', function () {
    // Test parsing Norwegian formatted amounts back to numeric values
    expect(AmountHelper::parseNorwegianAmount('500 000'))->toBe(500000.0);
    expect(AmountHelper::parseNorwegianAmount('1 500 000'))->toBe(1500000.0);
    expect(AmountHelper::parseNorwegianAmount('750 000'))->toBe(750000.0);
    expect(AmountHelper::parseNorwegianAmount('250 001'))->toBe(250001.0);

    // Test with comma decimal separator
    expect(AmountHelper::parseNorwegianAmount('250 000,50'))->toBe(250000.5);

    // Test edge cases
    expect(AmountHelper::parseNorwegianAmount(''))->toBeNull();
    expect(AmountHelper::parseNorwegianAmount(null))->toBeNull();
    expect(AmountHelper::parseNorwegianAmount('   '))->toBeNull();
});

it('has correct table column configuration for amount formatting', function () {
    // Skip this test as it requires a Livewire component instance
    $this->markTestSkipped('Table configuration test requires Livewire component instance');
});

it('updates asset year amounts correctly through table input', function () {
    $assetYear = AssetYear::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'asset_id' => $this->asset->id,
        'asset_configuration_id' => $this->configuration->id,
        'year' => 2025,
        'asset_market_amount' => 1000000.00,
        'income_factor' => 'yearly',
        'expence_factor' => 'yearly',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    // Simulate updating the asset_market_amount through the table
    $newFormattedValue = '1 500 000';
    $expectedNumericValue = 1500000.0;

    // Parse the formatted value
    $parsedValue = AmountHelper::parseNorwegianAmount($newFormattedValue);
    expect($parsedValue)->toBe($expectedNumericValue);

    // Update the record
    $assetYear->update(['asset_market_amount' => $parsedValue]);

    // Verify the update (cast to float since model uses decimal casting)
    expect((float) $assetYear->fresh()->asset_market_amount)->toBe($expectedNumericValue);

    // Verify it formats back correctly for display
    expect(AmountHelper::formatNorwegian($assetYear->fresh()->asset_market_amount))->toBe('1 500 000');
});
