<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = \App\Models\User::factory()->create();
        $assetConfiguration = \App\Models\AssetConfiguration::factory()->create(['user_id' => $user->id]);

        return [
            'asset_configuration_id' => $assetConfiguration->id,
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'asset_type' => $this->faker->randomElement(['house', 'car', 'boat', 'fund', 'stock']),
            'group' => $this->faker->randomElement(['private', 'company']),
            'tax_type' => $this->faker->randomElement(['house', 'fund', 'stock']),
            'tax_property' => null,
            'tax_country' => 'no',
            'is_active' => true,
            'sort_order' => 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => $this->faker->sha256(),
            'updated_checksum' => $this->faker->sha256(),
        ];
    }
}
