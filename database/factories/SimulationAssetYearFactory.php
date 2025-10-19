<?php

namespace Database\Factories;

use App\Models\AssetConfiguration;
use App\Models\SimulationAsset;
use App\Models\SimulationAssetYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SimulationAssetYear>
 */
class SimulationAssetYearFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SimulationAssetYear::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => SimulationAsset::factory(),
            'asset_configuration_id' => AssetConfiguration::factory(),
            'year' => $this->faker->numberBetween(2020, 2080),
            'asset_market_amount' => $this->faker->randomFloat(2, 10000, 1000000),
            'asset_acquisition_amount' => $this->faker->randomFloat(2, 10000, 500000),
            'asset_equity_amount' => $this->faker->randomFloat(2, 10000, 1000000),
            'asset_paid_amount' => $this->faker->randomFloat(2, 0, 50000),
            'asset_taxable_initial_amount' => $this->faker->randomFloat(2, 0, 100000),
            'income_amount' => $this->faker->randomFloat(2, 0, 50000),
            'income_factor' => $this->faker->randomElement(['monthly', 'yearly']),
            'expence_amount' => $this->faker->randomFloat(2, 0, 20000),
            'expence_factor' => $this->faker->randomElement(['monthly', 'yearly']),
            'asset_changerate' => $this->faker->randomElement(['cash', 'equity', 'bond', 'real_estate']),
            'asset_changerate_percent' => $this->faker->randomFloat(2, 0, 15),
            'user_id' => User::factory(),
            'team_id' => null,
            'created_by' => function (array $attributes) {
                return $attributes['user_id'];
            },
            'updated_by' => function (array $attributes) {
                return $attributes['user_id'];
            },
            'created_checksum' => $this->faker->sha256(),
            'updated_checksum' => $this->faker->sha256(),
        ];
    }

    /**
     * Set a specific year.
     */
    public function year(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'year' => $year,
        ]);
    }

    /**
     * Set specific financial amounts.
     */
    public function withAmounts(float $market, float $income = 0, float $expense = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_market_amount' => $market,
            'asset_acquisition_amount' => $market * 0.8,
            'asset_equity_amount' => $market,
            'income_amount' => $income,
            'expence_amount' => $expense,
        ]);
    }

    /**
     * Set income factor.
     */
    public function incomeFactor(string $factor): static
    {
        return $this->state(fn (array $attributes) => [
            'income_factor' => $factor,
        ]);
    }

    /**
     * Set expense factor.
     */
    public function expenseFactor(string $factor): static
    {
        return $this->state(fn (array $attributes) => [
            'expence_factor' => $factor,
        ]);
    }
}
