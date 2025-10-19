<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssetConfiguration>
 */
class AssetConfigurationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = \App\Models\User::factory()->create();

        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->sentence(),
            'birth_year' => $this->faker->numberBetween(1950, 1990),
            'prognose_age' => $this->faker->numberBetween(40, 60),
            'pension_official_age' => 67,
            'pension_wish_age' => $this->faker->numberBetween(60, 67),
            'expected_death_age' => $this->faker->numberBetween(75, 90),
            'export_start_age' => now()->year - 1,
            'risk_tolerance' => $this->faker->randomElement(['conservative', 'moderate_conservative', 'moderate', 'moderate_aggressive', 'aggressive']),
            'public' => false,
            'icon' => 'heroicon-o-user',
            'color' => $this->faker->hexColor(),
            'tags' => [$this->faker->word(), $this->faker->word()],
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => $this->faker->sha256(),
            'updated_checksum' => $this->faker->sha256(),
        ];
    }
}
