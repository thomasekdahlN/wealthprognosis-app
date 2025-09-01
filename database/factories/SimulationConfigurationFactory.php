<?php

namespace Database\Factories;

use App\Models\SimulationConfiguration;
use App\Models\AssetConfiguration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SimulationConfiguration>
 */
class SimulationConfigurationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SimulationConfiguration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . ' Simulation',
            'description' => $this->faker->sentence(),
            'birth_year' => $this->faker->numberBetween(1970, 1990),
            'prognose_age' => $this->faker->numberBetween(40, 60),
            'pension_official_age' => 67,
            'pension_wish_age' => $this->faker->numberBetween(60, 67),
            'death_age' => $this->faker->numberBetween(80, 95),
            'export_start_age' => $this->faker->numberBetween(25, 35),
            'public' => false,
            'icon' => 'heroicon-o-calculator',
            'image' => null,
            'color' => $this->faker->hexColor(),
            'tags' => json_encode([$this->faker->word(), $this->faker->word()]),
            'risk_tolerance' => $this->faker->randomElement(['conservative', 'moderate_conservative', 'moderate', 'moderate_aggressive', 'aggressive']),
            'user_id' => User::factory(),
            'team_id' => null,
            'asset_configuration_id' => AssetConfiguration::factory(),
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
     * Indicate that the simulation is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'public' => true,
        ]);
    }

    /**
     * Indicate that the simulation is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'public' => false,
        ]);
    }

    /**
     * Set a specific risk tolerance.
     */
    public function riskTolerance(string $tolerance): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_tolerance' => $tolerance,
        ]);
    }

    /**
     * Set specific age parameters.
     */
    public function withAges(int $birthYear, int $deathAge, int $pensionAge = 67): static
    {
        return $this->state(fn (array $attributes) => [
            'birth_year' => $birthYear,
            'death_age' => $deathAge,
            'pension_official_age' => $pensionAge,
            'pension_wish_age' => $pensionAge,
        ]);
    }
}
