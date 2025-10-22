<?php

use App\Models\AssetType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\TestablePrognosisShowStatistics;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->actingAs($this->user);

    // Seed two AssetTypes with different show_statistics flags
    AssetType::factory()->create([
        'type' => 'equityfund',
        'name' => 'Equity Fund',
        'show_statistics' => true,
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    AssetType::factory()->create([
        'type' => 'spouse',
        'name' => 'Spouse',
        'show_statistics' => false,
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);
});

it('uses asset_types.show_statistics to control visibility in statistics', function () {
    // Minimal config with only meta so constructor does not iterate assets
    $config = [
        'meta' => [
            'birthYear' => 1990,
            'deathYear' => 90,
        ],
    ];

    // Create minimal stub objects for tax services not used in this test
    $taxIncome = new class {};
    $taxFortune = new class
    {
        public function calculatefortunetax(bool $debug, int $year, string $group, float $taxableAmount, float $mortgageAmount, bool $aggregate)
        {
            return [0, 0.0, $taxableAmount, 'stubbed'];
        }
    };
    $taxRealization = new class {};
    $changerate = new class {};

    $prognosis = new TestablePrognosisShowStatistics($config, $taxIncome, $taxFortune, $taxRealization, $changerate);

    expect($prognosis->isShownInStatisticsPublic('equityfund'))->toBeTrue();
    expect($prognosis->isShownInStatisticsPublic('spouse'))->toBeFalse();

    // Non-existent types should default to false
    expect($prognosis->isShownInStatisticsPublic('nonexistent'))->toBeFalse();
});
