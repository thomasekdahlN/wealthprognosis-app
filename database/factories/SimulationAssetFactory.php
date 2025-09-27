<?php

namespace Database\Factories;

use App\Models\SimulationAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SimulationAsset>
 */
class SimulationAssetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SimulationAsset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $assetTypes = ['cash', 'equity', 'bond', 'real_estate', 'crypto', 'commodity'];
        $groups = ['private', 'business'];

        return [
            'asset_configuration_id' => \App\Models\AssetConfiguration::factory(),
            'code' => $this->faker->unique()->slug(2),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'asset_type' => $this->faker->randomElement($assetTypes),
            'group' => $this->faker->randomElement($groups),
            'tax_country' => 'no',
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
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
     * Indicate that the asset is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the asset is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific asset type.
     */
    public function assetType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_type' => $type,
        ]);
    }

    /**
     * Set a specific group.
     */
    public function group(string $group): static
    {
        return $this->state(fn (array $attributes) => [
            'group' => $group,
        ]);
    }

    /**
     * Set a specific tax type.
     */
    public function taxType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_type' => $type,
        ]);
    }
}
