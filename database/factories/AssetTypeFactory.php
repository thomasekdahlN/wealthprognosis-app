<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssetType>
 */
class AssetTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        return [
            'type' => $this->faker->unique()->slug(2).'_'.$this->faker->randomNumber(4),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement(['Investment Funds', 'Securities', 'Real Assets', 'Alternative Investments', 'Cash & Deposits']),
            'icon' => 'heroicon-o-'.$this->faker->randomElement(['star', 'heart', 'home', 'user', 'cog']),
            'color' => $this->faker->randomElement(['gray', 'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose']),
            'is_active' => $this->faker->boolean(80),
            'is_private' => $this->faker->boolean(50),
            'is_company' => $this->faker->boolean(30),
            'is_tax_optimized' => $this->faker->boolean(40),
            'is_fire_sellable' => $this->faker->boolean(60),
            'can_generate_income' => $this->faker->boolean(50),
            'can_generate_expenses' => $this->faker->boolean(30),
            'can_have_mortgage' => $this->faker->boolean(20),
            'can_have_market_value' => $this->faker->boolean(70),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'user_id' => $user->id,
            'team_id' => $team->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => $this->faker->sha256(),
            'updated_checksum' => $this->faker->sha256(),
        ];
    }
}
